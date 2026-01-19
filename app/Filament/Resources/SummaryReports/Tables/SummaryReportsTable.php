<?php

namespace App\Filament\Resources\SummaryReports\Tables;

use App\Models\Operator;
use App\Models\SummaryReport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Concerns\InteractsWithTable;

class SummaryReportsTable
{
    use InteractsWithTable;

    public bool $isReady = false;

    public function loadReport(): void
    {
        $this->isReady = true;
        $this->resetTable();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->query(function ($livewire) {
                $filters = $livewire->tableFilters['report_filters'] ?? [];
                return static::getSummaryQuery($filters);
            })
            ->columns([
                TextColumn::make('operator')
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'client'),

                TextColumn::make('client')
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'client'),

                TextColumn::make('provider_name')
                    ->label('Partner')
                    ->getStateUsing(fn ($record) => $record->provider_name) 
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'provider' || ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'game' ),

                TextColumn::make('sub_provider_name')
                    ->label('Provider')
                    ->getStateUsing(fn ($record) => $record->sub_provider_name)
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'provider' || ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'game' ),

                TextColumn::make('game_name')
                    ->label('Game')
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'game'),

                TextColumn::make('username')
                    ->label('Player')
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') === 'player')
                    ->description(fn ($record): string => 
                        "ID: {$record->player_id} | CID: {$record->client_player_id}"
                    )
                    ->wrap(),

                TextColumn::make('currency')
                    ->sortable(),
                TextColumn::make('bet')
                    ->label('Bet')
                    ->color('primary')
                    ->alignEnd()
                    ->formatStateUsing(fn ($record) => number_format((float)$record->bet * (float)($record->rate ?: 1), 2)),

                TextColumn::make('win')
                    ->label('Win')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($record) => number_format((float)$record->win * (float)($record->rate ?: 1), 2)),

                TextColumn::make('total_ggr')
                    ->label('GGR')
                    ->alignEnd()
                    ->color(fn ($record) => 
                        (($record->bet - $record->win) >= 0) ? 'success' : 'danger'
                    )
                    ->formatStateUsing(function ($record) {
                        // We calculate GGR on the fly here to be 100% sure it's accurate
                        $ggr = (float)$record->bet - (float)$record->win;
                        $rate = (float)($record->rate ?: 1);
                        
                        return number_format($ggr * $rate, 2);
                    }),
                TextColumn::make('rounds')
                    ->label('Rounds')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('players')
                    ->label('Total Players')
                    ->alignEnd()
                    ->sortable()
                    ->visible(fn ($livewire) => ($livewire->tableFilters['report_filters']['groupBy'] ?? 'client') !== 'player'),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersFormColumns(1)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                Filter::make('report_filters')
                    ->form([
                        Section::make('Report Filters')
                            ->columns(5)
                            ->schema([
                                Select::make('operator_id')
                                    ->options(Operator::pluck('client_name', 'operator_id'))
                                    ->searchable(),
                                
                                Select::make('partner_id')
                                    ->label('Partner')
                                    ->options(fn() => DB::table('mwapiv2_main.providers')->pluck('provider_name', 'provider_id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('sub_provider_id', null))
                                    ->placeholder('All Partners'),

                                Select::make('provider_id')
                                    ->label('Provider')
                                    ->options(function (Get $get) {
                                        $partnerId = $get('partner_id');
                                        $query = DB::table('mwapiv2_main.sub_providers');
                                        
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

                                Select::make('game_id')
                                    ->label('Game')
                                    ->options(function (Get $get) {
                                        $subProviderId = $get('sub_provider_id');
                                        $query = DB::table('mwapiv2_main.games');
                                        
                                        if ($subProviderId) {
                                            $query->where('sub_provider_id', $subProviderId);
                                        }
                                        
                                        return $query->limit(100)->pluck('game_name', 'game_id');
                                    })
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search, Get $get) {
                                        $subProviderId = $get('sub_provider_id');
                                        return DB::table('mwapiv2_main.games')
                                            ->where('game_name', 'like', "%{$search}%")
                                            ->when($subProviderId, fn($q) => $q->where('sub_provider_id', $subProviderId))
                                            ->limit(50)
                                            ->pluck('game_name', 'game_id')
                                            ->toArray();
                                    })
                                    ->placeholder('Search Game...'),

                                Select::make('player_id')
                                    ->label('Player')
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return DB::table('mwapiv2_main.players')
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
                                    ->placeholder('Search by Username, PlayerId, SystemID'),
                                    
                                Select::make('dateType')
                                    ->options([
                                        'day'   => 'Day',
                                        'month' => 'Month',
                                        'range' => 'Range',
                                        'year'  => 'Year',
                                    ])
                                    ->default('day')
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        // Clear all date-related values when the type changes
                                        $set('date', null);
                                        $set('month', null);
                                        $set('year', null);
                                        $set('date_from', null);
                                        $set('date_to', null);
                                    }),

                                DatePicker::make('date')
                                    ->label('Date')
                                    ->visible(fn ($get) => $get('dateType') === 'day')
                                    ->live(),

                                Select::make('month')
                                    ->label('Month')
                                    ->visible(fn($get) => $get('dateType') === 'month')
                                    ->options(function () {
                                        $months = collect();
                                        
                                        // Define the start point: January 2025
                                        $startDate = \Illuminate\Support\Carbon::create(2025, 1, 1)->startOfMonth();
                                        
                                        // Define the end point: The current month
                                        $currentDate = now()->startOfMonth();

                                        // Loop backwards from current month to January 2025
                                        while ($currentDate->greaterThanOrEqualTo($startDate)) {
                                            $months[$currentDate->format('Y-m')] = $currentDate->format('F Y');
                                            $currentDate->subMonth();
                                        }

                                        return $months->toArray();
                                    })
                                    ->searchable()
                                    ->live(),

                                Select::make('year')
                                    ->label('Year')
                                    ->visible(fn($get) => $get('dateType') === 'year')
                                    ->options(function () {
                                        $currentYear = now()->year;
                                        return collect(range($currentYear, 2023)) // descending
                                            ->mapWithKeys(fn($y) => [$y => $y])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->live(),

                                DatePicker::make('date_from')
                                    ->visible(fn ($get) => $get('dateType') === 'range')
                                    ->native(false)
                                    ->live(),

                                DatePicker::make('date_to')
                                    ->visible(fn ($get) => $get('dateType') === 'range')
                                    ->native(false)
                                    ->reactive()
                                    ->rules([
                                        fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                            if (!$get('date_from') || !$value) {
                                                return;
                                            }

                                            if (Carbon::parse($get('date_from'))->diffInDays($value) > 7) {
                                                $fail('Date range cannot exceed 7 days.');
                                            }
                                        },
                                    ]),

                                Select::make('groupBy')
                                    ->options([
                                        'client'    => 'Client',
                                        'provider'  => 'Provider',
                                        'game'      => 'Game',
                                        'player'    => 'Player',
                                        'all'       => 'All',
                                    ])
                                    ->default('client')
                                    ->live(),
                               
                            ]),
                    ])
                    // ->indicateUsing(function (array $data): array {
                    //     $indicators = [];
                    //     if ($data['operator_id'] ?? null) {
                    //         $indicators[] = 'Operator: ' . Operator::find($data['operator_id'])?->client_name;
                    //     }
                    //     if ($data['date'] ?? null) $indicators[] = 'Day: ' . $data['date'];
                    //     return $indicators;
                    // })
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }

    protected static function getSummaryQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $groupBy = $filters['groupBy'] ?? 'client';
            
        // 1. Define specific columns to select based on the group
        $selectColumns = [];
        $groupByColumns = [];

        switch ($groupBy) {
            case 'client':
                $selectColumns = ['o.client_name as operator', 'c.client_name as client', 'c.default_currency as currency'];
                $groupByColumns = ['per_player.operator_id', 'per_player.client_id', 'o.client_name', 'c.client_name', 'c.default_currency'];
                break;

            case 'provider':
                $selectColumns = [
                    'pr.provider_name as provider_name', 
                    'sp.sub_provider_name as sub_provider_name', 
                    'c.default_currency as currency'
                ];
                $groupByColumns = ['pr.provider_id', 'per_player.provider_id', 'pr.provider_name', 'sp.sub_provider_name', 'c.default_currency'];
                break;

            case 'game':
                $selectColumns = [
                    'pr.provider_name as provider_name', 
                    'sp.sub_provider_name as sub_provider_name', 
                    'g.game_name', 
                    'c.default_currency as currency'
                ];
                $groupByColumns = ['per_player.game_id', 'g.game_name', 'pr.provider_name', 'sp.sub_provider_name', 'c.default_currency'];
                break;

            case 'player':
                $selectColumns = [
                    'p.player_id', 
                    'p.username', 
                    'p.client_player_id', 
                    'c.default_currency as currency'
                ];
                $groupByColumns = ['per_player.player_id', 'p.player_id', 'p.username', 'p.client_player_id', 'c.default_currency'];
                break;

            case 'all':
                $selectColumns = ['c.default_currency as currency'];
                $groupByColumns = ['c.default_currency'];
                break;

            default:
                $selectColumns = ['c.default_currency as currency'];
                $groupByColumns = ['c.default_currency'];
                break;
        }

        // 2. Generate the ID based on the grouping keys
        $rowIdSql = match ($groupBy) {
            'client'   => "CONCAT(COALESCE(per_player.operator_id, 0), '_', COALESCE(per_player.client_id, 0))",
            'provider' => "CONCAT(COALESCE(pr.provider_id, 0), '_', COALESCE(per_player.provider_id, 0))",
            'game'     => "CONCAT(COALESCE(per_player.game_id, 0), '_', COALESCE(c.default_currency, 'na'))",
            ''         => "CONCAT(COALESCE(c.default_currency, 0))",
            default    => "CONCAT(COALESCE(c.default_currency, 0))",
        };

        $query = SummaryReport::query()
            ->from('bo_aggreagate.per_player')
            ->leftJoin('mwapiv2_main.operator as o', 'per_player.operator_id', '=', 'o.operator_id')
            ->leftJoin('mwapiv2_main.clients as c', 'per_player.client_id', '=', 'c.client_id')
            ->leftJoin('mwapiv2_main.sub_providers as sp', 'per_player.provider_id', '=', 'sp.sub_provider_id')
            ->leftJoin('mwapiv2_main.providers as pr', 'sp.provider_id', '=', 'pr.provider_id')
            ->leftJoin('mwapiv2_main.games as g', 'per_player.game_id', '=', 'g.game_id')
            ->leftJoin('mwapiv2_main.players as p', 'per_player.player_id', '=', 'p.player_id')
            ->select($selectColumns)
            ->selectRaw("
                {$rowIdSql} as per_player_id, 
                MAX(per_player.created_at) as created_at,
                SUM(per_player.bet) as bet,
                SUM(per_player.win) as win,
                (SUM(per_player.bet) - SUM(per_player.win)) as total_ggr,
                SUM(per_player.total_rounds) as rounds,
                COUNT(DISTINCT per_player.player_id) as players
            ")
            ->groupBy($groupByColumns); 
        $query->groupByRaw($rowIdSql);

        $dateType = $filters['dateType'] ?? 'day';

        // 1. Standard filters
        $query->when($filters['operator_id'] ?? null, fn($q, $val) => $q->where('per_player.operator_id', $val))
            ->when($filters['partner_id'] ?? null, function ($query, $partnerId) {
                $query->whereIn('per_player.provider_id', function ($subQuery) use ($partnerId) {
                    $subQuery->select('sub_provider_id')
                        ->from('mwapiv2_main.sub_providers')
                        ->where('provider_id', $partnerId);
                });
            })
            ->when($filters['provider_id'] ?? null, fn($q, $val) => $q->where('per_player.provider_id', $val))
            ->when($filters['game_id'] ?? null, fn($q, $val) => $q->where('per_player.game_id', $val))
            ->when($filters['player_id'] ?? null, fn($q, $val) => $q->where('per_player.player_id', $val));

        // 2. Conditional Date Filters
        if ($dateType === 'day') {
            $query->when($filters['date'] ?? null, fn($q, $val) => $q->whereDate('per_player.created_at', $val));
        } 
        elseif ($dateType === 'month') {
            $query->when($filters['month'] ?? null, fn($q, $val) => 
                $q->whereRaw("DATE_FORMAT(per_player.created_at, '%Y-%m') = ?", [$val])
            );
        } 
        elseif ($dateType === 'year') {
            $query->when($filters['year'] ?? null, fn($q, $val) => 
                $q->whereYear('per_player.created_at', $val)
            );
        } 
        elseif ($dateType === 'range') {
            $query->when(($filters['date_from'] ?? null) && ($filters['date_to'] ?? null), function ($q) use ($filters) {
                $q->whereBetween('per_player.created_at', [
                    $filters['date_from'],
                    $filters['date_to'],
                ]);
            });
        }

        $query->orderByRaw('SUM(per_player.bet) DESC');

        return $query;
    }
}
