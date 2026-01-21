<?php

namespace App\Filament\Resources\SettlementLoggers\Tables;

use App\Services\HashService;
use App\Services\JiraService;
use App\Traits\HasTransactionDetails;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class SettlementLoggersTable
{

    use HasTransactionDetails;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jira_ticket_id')
                    ->label('Jira ID')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->action(null) // Prevents any row-level actions from intercepting the click
                    ->url(fn ($record) => "https://".str_replace(['http://', 'https://'], '', config('services.jira.domain'))."/browse/{$record->jira_ticket_id}")
                    ->openUrlInNewTab(),

                TextColumn::make('encrypted_round_id')
                    ->label('Transaction/Round ID')
                    ->formatStateUsing(fn ($state) => str(Crypt::decryptString($state))->mask('*', 4, -4))
                    ->color('info')
                    ->action(
                        Action::make('view_details')
                            ->modalHeading(fn ($record) => "Transaction Details: " . $record->round_id)
                            ->modalWidth('7xl')
                            ->modalSubmitAction(false)
                            ->modalContent(function ($record) {
                                $tableInstance = new self();

                                $extensionData = $tableInstance->fetchByTransactionId($record);

                                $record['player_id'] = $extensionData['player_id'];

                                $data = array_merge([
                                    'record' => $record,
                                    'transactions' => $extensionData['transactions'],
                                    'total' => $extensionData['total'],
                                    'syncPayout' => $extensionData['syncPayout'],
                                ]);
                
                                return view('components.transaction-modal-details', $data);
                            })
                    ),

                TextColumn::make('settle_type')
                    ->sortable(),

                TextColumn::make('amount')
                    ->money()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('user.email')
                    ->label('Requester')
                    ->sortable(),
                
                TextColumn::make('approver.email')
                    ->label('Approved By')
                    ->placeholder('Awaiting Approval')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Requested At'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => 
                        $record->status === 'pending' && 
                        Filament::auth()->user()->can('Approve:SettlementLogger') &&
                        // Hide button if IP doesn't match (optional)
                        (Filament::auth()->user()->allowed_ip === null || Filament::auth()->user()->allowed_ip === request()->ip())
                    )
                    ->action(function ($record) {
                        $payload = [
                            'transaction_id' => Crypt::decryptString($record->encrypted_round_id),
                            'amount'         => $record->amount,
                            'approved_by'    => Filament::auth()->user()->email,
                        ];
                    
                        // Generate the hash using our global helper
                        $payload['hash'] = HashService::generate($payload);

                        $isTestMode = env('SETTLEMENT_TEST_MODE', false);
                        
                        
                        // 1. Determine Endpoint
                        $endpoint = match ($record->settle_type) {
                            'credit'   => 'api/settlement/credit',
                            'debit'    => 'api/settlement/debit',
                            'rollback' => 'api/settlement/rollback',
                            default    => null,
                        };
            
                        if (!$endpoint) {
                            Notification::make()->danger()->title('Invalid Settlement Type')->send();
                            return;
                        }
            
                        $success = $isTestMode ? true : Http::post(config('app.url') . '/' . $endpoint, $payload)->successful();
            
                        if ($success) {
                            // 3. Update Jira
                            JiraService::addCommentAndClose($record->jira_ticket_id, Filament::auth()->user()->email);
            
                            // 4. Update local database
                            $record->update([
                                'status' => 'approved',
                                'approved_by' => Filament::auth()->id(),
                                'approved_at' => now(),
                            ]);
            
                            Notification::make()
                                ->success()
                                ->title($isTestMode ? 'Processed (TEST MODE)' : 'Settlement Approved')
                                ->body($isTestMode 
                                    ? "Bypassed API. Jira Ticket {$record->jira_ticket_id} closed." 
                                    : "API called and Jira Ticket {$record->jira_ticket_id} closed.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('API Error')
                                ->body('The settlement API rejected the request. Jira ticket remains open.')
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-m-x-circle')
                    ->visible(fn ($record) => 
                        $record->status === 'pending' && 
                        Filament::auth()->user()->can('Reject:SettlementLogger') &&
                        (Filament::auth()->user()->allowed_ip === null || Filament::auth()->user()->allowed_ip === request()->ip())
                    )
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->placeholder('Explain why this is being rejected...')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        // Update the record
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'approved_by' => Filament::auth()->id(), // Log who rejected it
                            'approved_at' => now(),
                        ]);

                        JiraService::addCommentAndClose(
                            $record->jira_ticket_id, 
                            Filament::auth()->user()->email, 
                            $data['rejection_reason'] // Pass the text from the modal
                        );

                        // Optional: Send notification to the CS agent who created it
                        Notification::make()
                            ->danger()
                            ->title('Settlement Rejected')
                            ->body("Your request for Jira {$record->jira_ticket_id} was rejected.")
                            ->sendToDatabase($record->user);

                        Notification::make()
                            ->success()
                            ->title('Settlement marked as Rejected')
                            ->send();
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'approved' => 'Success / Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->indicator('Status'),
            ]);
    }
}