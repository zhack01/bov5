<?php

namespace App\Filament\Resources\GameReports\Tables;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Operator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use App\Traits\HasTransactionDetails;

class GameReportsTable
{

    use HasTransactionDetails;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date Time')->sortable(),
                TextColumn::make('round_id')
                    ->label('Round ID')
                    ->searchable()
                    ->limit(10)
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
                
                TextColumn::make('operators.client_name')->label('Operator')->sortable(),
                TextColumn::make('clients.client_name')->label('Client')->sortable(),
                TextColumn::make('partners.provider_name')->label('Partner')->sortable(),
                TextColumn::make('providers.sub_provider_name')->label('Provider')->sortable(),
                TextColumn::make('games.game_name')->label('Game')->sortable(),
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
                                    }),
                                
                                DatePicker::make('date_end')
                                    ->label('To')
                                    ->required(),

                                Select::make('outcome')
                                    ->options([
                                        'all' => 'All',
                                        '1' => 'Lose',
                                        '2' => 'Win',
                                        '3' => 'Progressing',
                                        '4' => 'Refund',
                                        '5' => 'Failed',
                                    ])->default('all'),

                                Select::make('currency')
                                    ->label('Convert To')
                                    ->options(Currency::pluck('code', 'code'))
                                    ->placeholder('Default Currency'),
                            ])->columns(4)
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['operator_id'], fn($q) => $q->where('operator_id', $data['operator_id']))
                            ->when($data['client_id'], fn($q) => $q->where('client_id', $data['client_id']))
                            ->when($data['provider_id'], fn($q) => $q->where('provider_id', $data['provider_id']))
                            ->when($data['sub_provider_id'], fn($q) => $q->where('sub_provider_id', $data['sub_provider_id']))
                            ->when($data['game_id'], fn($q) => $q->where('game_id', $data['game_id']))
                            ->when($data['player_id'], fn($q) => $q->where('player_id', $data['player_id']))
                            // Handle Date Range
                            ->when($data['date_start'], fn($q) => $q->whereDate('created_at', '>=', $data['date_start']))
                            ->when($data['date_end'], fn($q) => $q->whereDate('created_at', '<=', $data['date_end']))
                            // Handle Outcome (skip if 'all')
                            ->when($data['outcome'] !== 'all', fn($q) => $q->where('outcome', $data['outcome']));
                    })
                    // Optional: This indicates the filter is "active" if any data is present
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_start'] ?? null) $indicators[] = 'From: ' . $data['date_start'];
                        if ($data['date_end'] ?? null) $indicators[] = 'To: ' . $data['date_end'];
                        return $indicators;
                    })
            ])
            ->filtersApplyAction(
                fn (Action $action) => $action
                    ->label('View Report')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('primary')
            )
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
