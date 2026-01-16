<?php

namespace App\Filament\Resources\SummaryReports\Tables;

use App\Models\Operator;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->label('Rounds')
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
                                Select::make('operator')
                                    ->options(Operator::pluck('client_name', 'operator_id'))
                                    ->searchable(),

                                Select::make('dateType')
                                    ->options(['day' => 'Day', 'month' => 'Month'])
                                    ->default('day')
                                    ->live(),

                                DatePicker::make('date')
                                    ->label('Date Selection')
                                    ->displayFormat(fn ($get) => $get('dateType') === 'month' ? 'F Y' : 'Y-m-d'),

                                Select::make('groupBy')
                                    ->options([
                                        'client'   => 'Per Client',
                                        'provider' => 'Per Provider',
                                        'game'     => 'Per Game',
                                    ])
                                    ->default('client'),
                               
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        // This will now show the complex SELECT ... FROM ... JOIN query
                        // echo "Before Filter: " . $query->toSql() . "</br></br>";
                    
                        $query->when($data['operator'] ?? null, fn($q, $val) => $q->where('per_player.operator_id', $val))
                              ->when($data['date'] ?? null, fn($q, $val) => $q->whereDate('per_player.created_at', $val));
                    
                        // This will show the query WITH the where clauses
                        // echo "After Filter: " . $query->toSql() . "</br></br>";
                    
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['operator'] ?? null) {
                            $indicators[] = 'Operator: ' . Operator::find($data['operator'])?->client_name;
                        }
                        if ($data['date'] ?? null) $indicators[] = 'Date: ' . $data['date'];
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
