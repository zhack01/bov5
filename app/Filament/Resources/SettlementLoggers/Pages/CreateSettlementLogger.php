<?php

namespace App\Filament\Resources\SettlementLoggers\Pages;

use App\Filament\Resources\SettlementLoggers\SettlementLoggerResource;
use App\Services\JiraService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class CreateSettlementLogger extends CreateRecord
{
    protected static string $resource = SettlementLoggerResource::class;

    /**
     * This catches the data sent from your Modal URL
     */
    protected function afterFill(): void
    {
        $this->form->fill([
            'raw_id' => request()->query('raw_id'),
            'round_id' => request()->query('round_id'),
            'trans_id' => request()->query('trans_id'),
            'display_id' => request()->query('display_id'),
            'amount' => request()->query('amount'),
            'settle_type' => request()->query('type'),
            'operator_id' => request()->query('operator_id'),
            'client_name' => request()->query('client_name'),
        ]);
    }

    public function mount(): void
    {
        parent::mount();

        $user = Filament::auth()->user();
        $currentIp = request()->ip();
        // dd($currentIp);
        // Check for missing or incorrect IP immediately
        if (blank($user->allowed_ip) || $user->allowed_ip !== $currentIp) {
            Notification::make()
                ->danger()
                ->title('Access Denied')
                ->body("Your IP ({$currentIp}) is not authorized for this action.")
                ->persistent()
                ->send();

            // Redirect away so they can't even see the form
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function beforeCreate(): void
    {
        // Keep this as a secondary 'safety' check, but mount() handles the primary block.
        $user = Filament::auth()->user();
        if ($user->allowed_ip !== request()->ip()) {
            throw ValidationException::withMessages(['ip' => 'Unauthorized IP.']);
        }
    }

    /**
     * This transforms the form data into the actual database columns
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);
        // Now this is safe to run because mount() would have stopped an intruder
        $jira = JiraService::createIssue(
            summary: "Settlement [" . strtoupper($data['settle_type'] ?? 'N/A') . "]: " . ($data['display_id'] ?? 'Manual'),
            description: "Type: " . ($data['settle_type'] ?? 'Not specified') . 
                         "\nReason: " . $data['reason'] . 
                         "\nAmount: " . $data['amount'] . 
                         "\nInternal ID: " . $data['raw_id'] .
                         "\nTransaction ID: " . $data['trans_id'] .
                         "\nRound ID: " . $data['round_id']
        );

        $data['jira_ticket_id'] = $jira['key'] ?? 'FAILED';
        $data['round_id_hash'] = hash('sha256', $data['trans_id'] ?? '');
        $data['encrypted_round_id'] = Crypt::encryptString($data['trans_id'] ?? '');
        $data['user_id'] = Filament::auth()->id();
        $data['status'] = 'pending';
        $data['created_from_ip'] = request()->ip(); // Capture IP here to be sure
        $data['operator_id'] = $data['operator_id'];
        $data['client_name'] = $data['client_name'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Find all users who have the 'Approve:SettlementLogger' permission
        $approvers = \App\Models\User::all()->filter(fn ($user) => 
            $user->can('Approve:SettlementLogger')
        );

        foreach ($approvers as $approver) {
            \Filament\Notifications\Notification::make()
                ->title('Settlement Pending')
                ->body("New request from {$record->user->email} for ID: {$record->jira_id}")
                ->icon('heroicon-o-check-badge')
                ->iconColor('warning')
                // This links the notification directly to the filtered table
                ->actions([
                    Action::make('review')
                        ->button()
                        ->url(SettlementLoggerResource::getUrl('index', [
                            'tableFilters[status][value]' => 'pending'
                        ])),
                ])
                ->sendToDatabase($approver)
                ->broadcast($approver);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
