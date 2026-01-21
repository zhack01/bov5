<?php

namespace App\Filament\Resources\TransactionMonitorings\Tables;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Operator;
use App\Traits\HasTransactionDetails;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\View\Components\TextComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class TransactionMonitoringsTable
{
    use HasTransactionDetails;

    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M j, Y') 
                    ->description(function ($record): HtmlString {
                        return new HtmlString(
                            $record->created_at->format('H:i:s') . ' <br> ' . 
                            '<span class="text-xs font-medium text-primary-600">' . 
                                $record->created_at->diffForHumans(['parts' => 2]) . 
                            '</span>'
                        );
                    })
                    ->html() // Required to render the <br> and <span>
                    ->sortable()
                    ->color('gray')
                    ->alignLeft(),
                TextColumn::make('round_id')
                    ->label('Round ID')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => new \Illuminate\Support\HtmlString(
                        wordwrap($state, 20, "<br>", true)
                    ))
                    ->color('primary')
                    ->weight('bold')
                    ->tooltip(fn ($state) => $state)
                    ->action(
                        Action::make('view_details')
                            ->modalHeading(fn ($record) => "Transaction Details: " . $record->round_id)
                            ->modalWidth('7xl')
                            ->modalSubmitAction(false)
                            ->modalContent(function ($record) {
                                $tableInstance = new self();
                                
                                $extensionData = $tableInstance->fetchExtensionData($record);
                
                                $data = array_merge([
                                    'record' => $record,
                                    'transactions' => $extensionData['transactions'],
                                    'total' => $extensionData['total'],
                                    'syncPayout' => $extensionData['syncPayout'],
                                ]);
                
                                return view('components.transaction-modal-details', $data);
                            })
                    ),
                
                TextColumn::make('operators.client_name')->label('Operator')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clients.client_name')->label('Client')->sortable()->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('partners.provider_name')
                    ->label('Partner')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('providers.sub_provider_name')
                    ->label('Provider')
                    ->sortable()
                    ->toggleable()
                    ->description(function (TextColumn $column, $record): ?string {
                        // Access the table from the column and check if 'Partner' is visible
                        $isPartnerVisible = $column->getTable()->getColumn('partners.provider_name')->getRecord();
                        
                        if (! $isPartnerVisible) {
                            return "PN: " . ($record->partners?->provider_name ?? 'N/A');
                        }
                        
                        return null;
                    }),

                TextColumn::make('games.game_name')
                    ->label('Game')
                    ->sortable()
                    ->description(function (TextColumn $column, $record): ?HtmlString {
                        $table = $column->getTable();
                        
                        $isPartnerVisible = $table->getColumn('partners.provider_name')->getRecord();
                        $isProviderVisible = $table->getColumn('providers.sub_provider_name')->getRecord();
                        
                        if (! $isPartnerVisible && ! $isProviderVisible) {
                            return new HtmlString(
                                "PN: " . ($record->partners?->provider_name ?? 'N/A') . "<br>" .
                                "PV: " . ($record->providers?->sub_provider_name ?? 'N/A')
                            );
                        }

                        if (! $isProviderVisible) {
                            return new HtmlString(
                                "PV: " . ($record->providers?->sub_provider_name ?? 'N/A')
                            );
                        }
                
                        return null;
                    }),
                TextColumn::make('players.player_id')
                    ->label('Player')
                    ->sortable()
                    ->description(function ($record): HtmlString {
                        return new HtmlString(
                            'User: ' . ($record->players?->username ?? 'N/A') . '<br>' . 
                            'CP ID: ' . $record->players?->client_player_id
                        );
                    }),


                TextColumn::make('clients.default_currency')->label('Currency'),

                TextColumn::make('bet')
                    ->label('Bet')
                    ->alignEnd()
                    ->formatStateUsing(fn ($record) => number_format((float)$record->bet * (float)($record->rate ?: 1), 2)),

                TextColumn::make('win')
                    ->label('Win')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($record) => number_format((float)$record->win * (float)($record->rate ?: 1), 2)),

                TextColumn::make('outcome')
                    ->label('Outcome')
                    ->badge()
                    ->formatStateUsing(fn($state) => match((int)$state) {
                        1 => 'Lose', 2 => 'Win', 3 => 'Progressing', 4 => 'Refund', 5 => 'Failed', default => 'Unknown'
                    })
                    ->color(fn ($state): string => match ((int)$state) {
                        2 => 'success', 1 => 'danger', 3 => 'warning', default => 'gray',
                    }),
                TextColumn::make('operator_id')->visible(false),
                TextColumn::make('operators.client_name')->visible(false)
            ])
            ->deferFilters() 
            ->filtersFormColumns(1)
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                Filter::make('report_filter')
                    ->form([
                        Section::make('Transaction Filters')
                            ->schema([
                                Select::make('operator_id')
                                    ->label('Operator')
                                    ->options(Operator::pluck('client_name', 'operator_id'))
                                    ->required()
                                    ->searchable()
                                    ->live(),

                                Select::make('client_id')
                                    ->label('Client')
                                    ->options(Client::pluck('client_name', 'client_id'))
                                    ->searchable()
                                    ->placeholder('All Clients'),

                            // PARTNER FILTER (Providers Table)
                                Select::make('provider_id')
                                ->label('Partner')
                                ->options(fn() => DB::connection('bo_aggreagate')->table('mwapiv2_main.providers')->pluck('provider_name', 'provider_id'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('sub_provider_id', null))
                                ->placeholder('All Partners'),

                                // SUB PROVIDER FILTER (Sub Providers Table)
                                Select::make('sub_provider_id')
                                ->label('Provider')
                                ->options(function (Get $get) {
                                    $partnerId = $get('provider_id');
                                    $query = DB::connection('bo_aggreagate')->table('mwapiv2_main.sub_providers');
                                    
                                    // If partner selected, filter. Otherwise show all.
                                    if ($partnerId) {
                                        $query->where('provider_id', $partnerId);
                                    }
                                    
                                    return $query->pluck('sub_provider_name', 'sub_provider_id');
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('game_id', null))
                                ->placeholder('All Providers'),

                                // GAME FILTER
                                Select::make('game_id')
                                ->label('Game')
                                ->options(function (Get $get) {
                                    $subProviderId = $get('sub_provider_id');
                                    $query = DB::connection('bo_aggreagate')->table('mwapiv2_main.games');
                                    
                                    if ($subProviderId) {
                                        $query->where('sub_provider_id', $subProviderId);
                                    }
                                    
                                    return $query->limit(100)->pluck('game_name', 'game_id');
                                })
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search, Get $get) {
                                    $subProviderId = $get('sub_provider_id');
                                    return DB::connection('bo_aggreagate')->table('mwapiv2_main.games')
                                        ->where('game_name', 'like', "%{$search}%")
                                        ->when($subProviderId, fn($q) => $q->where('sub_provider_id', $subProviderId))
                                        ->limit(50)
                                        ->pluck('game_name', 'game_id')
                                        ->toArray();
                                })
                                ->placeholder('Search Game...'),

                                // PLAYER FILTER (Multi-column search)
                                Select::make('player_id')
                                ->label('Player')
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    return DB::connection('bo_aggreagate')->table('mwapiv2_main.players')
                                        ->where(function ($q) use ($search) {
                                            $q->where('username', 'like', "%{$search}%")
                                            ->orWhere('player_id', 'like', "%{$search}%")
                                            ->orWhere('client_player_id', 'like', "%{$search}%");
                                        })
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [$p->player_id => "{$p->username} ({$p->client_player_id})"])
                                        ->toArray();
                                })
                                ->placeholder('Search by Name, ID, or Client ID...'),

                                DatePicker::make('date_start')
                                    ->label('From')
                                    ->required()
                                    ->live() // Essential: tells Filament to watch for changes
                                    ->afterStateUpdated(function ($state, $set) {
                                        // $state is the new value of date_start
                                        // $set allows us to update other fields in the form
                                        if ($state) {
                                            $set('date_end', $state);
                                        }
                                    })
                                    // Default to today's date instead of just the day number
                                    ->default(fn () => now()->toDateString()),
                                
                                DatePicker::make('date_end')
                                    ->label('To')
                                    ->default(fn () => now()->toDateString()),

                                Select::make('outcome')
                                    ->options([
                                        'all' => 'All',
                                        '3' => 'Progressing',
                                        '4' => 'Refund',
                                        '5' => 'Failed',
                                    ])->default('3'),

                                Select::make('currency')
                                    ->label('Convert To')
                                    ->options(Currency::pluck('code', 'code'))
                                    ->placeholder('Default Currency'),

                                TextInput::make('search_round_id')
                                    ->label('Round ID')
                                    ->placeholder('Enter specific Round ID...'),

                            ])->columns(4)
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['search_round_id'])) {
                            return $query->where('round_id', $data['search_round_id']);
                        }

                        return $query
                            ->whereRaw("outcome <> 1")
                            ->whereRaw("outcome <> 2")
                            ->when($data['operator_id'], fn($q) => $q->where('operator_id', $data['operator_id']))
                            ->when($data['client_id'], fn($q) => $q->where('client_id', $data['client_id']))
                            ->when($data['provider_id'], fn($q) => $q->where('provider_id', $data['provider_id']))
                            ->when($data['sub_provider_id'], fn($q) => $q->where('sub_provider_id', $data['sub_provider_id']))
                            ->when($data['game_id'], fn($q) => $q->where('game_id', $data['game_id']))
                            ->when($data['player_id'], fn($q) => $q->where('player_id', $data['player_id']))
                            // Handle Date Range
                            ->when($data['date_start'], fn($q) => $q->whereDate('date', '>=', $data['date_start']))
                            ->when($data['date_end'], fn($q) => $q->whereDate('date', '<=', $data['date_end']))
                            // Handle Outcome (skip if 'all')
                            ->when($data['outcome'] !== 'all', fn($q) => $q->where('outcome', $data['outcome']));
                    })
                   
            ])
            ->filtersApplyAction(
                fn (Action $action) => $action
                    ->label('View Report')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('primary')
            )
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
