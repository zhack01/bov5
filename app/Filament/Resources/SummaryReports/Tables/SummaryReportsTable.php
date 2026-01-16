<?php

namespace App\Filament\Resources\SummaryReports\Tables;

use App\Models\Operator;
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

class SummaryReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operator')
                    ->sortable(),
                TextColumn::make('client')
                    ->sortable(),
                TextColumn::make('partner')
                    ->sortable(),
                TextColumn::make('provider')
                    ->sortable(),
                TextColumn::make('game_name')
                    ->sortable(),
                TextColumn::make('currency')
                    ->sortable(),
                TextColumn::make('bet')
                    ->label('Bet')
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
                    ->label('Players')
                    ->alignEnd()
                    ->sortable(),
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
            ->defaultSort('bet', 'desc')
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
                                    ->live(),

                                DatePicker::make('date')
                                    ->label('Date')
                                    ->visible(fn ($get) => $get('dateType') === 'day'),

                                Select::make('month')
                                    ->label('Month')
                                    ->visible(fn($get) => $get('dateType') === 'month')
                                    ->options(function () {
                                        $months = collect();
                                        $currentYear = now()->year;

                                        // let's say you want 2 years of months
                                        for ($year = $currentYear; $year >= $currentYear - 1; $year--) {
                                            for ($month = 12; $month >= 1; $month--) {
                                                $date = Carbon::create($year, $month, 1);
                                                $months[$date->format('Y-m')] = $date->format('F Y'); // key => value
                                            }
                                        }

                                        return $months->toArray();
                                    })
                                    ->searchable()
                                    ->default(now()->format('Y-m')),

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
                                    ->default(now()->year),

                                DatePicker::make('date_from')
                                    ->visible(fn ($get) => $get('dateType') === 'range')
                                    ->native(false)
                                    ->reactive(),

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
                                        'all'       => 'All',
                                    ])
                                    ->default('client'),
                               
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        // This will now show the complex SELECT ... FROM ... JOIN query
                        // echo "Before Filter: " . $query->toSql() . "</br></br>";
                    //    dd($data);
                        
                        $query->when($data['operator'] ?? null, fn($q, $val) => $q->where('per_player.operator_id', $val))

                            ->when($data['date'] ?? null, fn($q, $val) => $q->whereDate('per_player.created_at', $val))

                            ->when($data['partner_id'] ?? null, function ($query, $partnerId) {
                                $query->whereIn('per_player.provider_id', function ($subQuery) use ($partnerId) {
                                    $subQuery->select('sub_provider_id')
                                        ->from('mwapiv2_main.sub_providers')
                                        ->where('provider_id', $partnerId);
                                });
                            })

                            ->when($data['provider_id'] ?? null, fn($q, $val) => $q->where('per_player.provider_id', $val))

                            ->when($data['game_id'] ?? null, fn($q, $val) => $q->where('per_player.game_id', $val))

                            ->when($data['player_id'] ?? null, fn($q, $val) => $q->where('per_player.player_id', $val))

                            ->when($data['month'] ?? null, fn($q, $val) => 
                                $q->whereRaw("DATE_FORMAT(per_player.created_at, '%Y-%m') = ?", [$val])
                            )

                            ->when($data['year'] ?? null, fn($q, $val) => 
                                $q->whereYear('per_player.created_at', $val)
                            )

                            ->when($data['date_from'] && $data['date_to'], function ($query) use ($data) {
                                $query->whereBetween('per_player.created_at', [
                                    $data['date_from'],
                                    $data['date_to'],
                                ]);
                            });
                    
                        // This will show the query WITH the where clauses
                        // echo "After Filter: " . $query->toSql() . "</br></br>";
                    
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['operator_id'] ?? null) {
                            $indicators[] = 'Operator: ' . Operator::find($data['operator_id'])?->client_name;
                        }
                        if ($data['date'] ?? null) $indicators[] = 'Day: ' . $data['date'];
                        return $indicators;
                    })
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
}
