<?php

namespace App\Filament\Resources\Games\Tables;

use App\Models\SubProvider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gameType.game_type_name')
                    ->label('Game Type')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('provider.provider_name')
                    ->label('Partner')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subProvider.sub_provider_name')
                    ->label('Provider')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('game_name')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('icon')
                    ->label('Game Icon')
                    ->circular()
                    // ->simpleLightbox(fn ($record) => $record->icon ?? url('/images/placeholder.png'))
                    ->url(fn ($record) => $record->icon, shouldOpenInNewTab: true)
                    ->height(40)
                    // 1. Handles the "Empty" case (null or empty string in database)
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    // 2. Handles the "Broken" case (URL exists but image 404s)
                    ->extraImgAttributes([
                        'onerror' => "this.src='" . url('/images/placeholder.png') . "'; this.onerror=null;",
                    ]),
                TextColumn::make('game_code')
                    ->searchable(),
                TextColumn::make('uni_game_code')
                    ->searchable(),
                TextColumn::make('secondary_game_code')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('license_fee')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('min_bet')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('max_bet')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('pay_lines')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('rtp')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('schedule')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('release_date')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('on_maintenance')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_freespin')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('provider_filter')
                    ->form([
                        // 1. Partner (Provider) Select
                        Select::make('provider_id')
                            ->label('Filter by Partner')
                            ->relationship('provider', 'provider_name')
                            ->searchable()
                            ->preload()
                            ->live() // Now this works!
                            ->afterStateUpdated(fn (Set $set) => $set('sub_provider_id', null)),
            
                        // 2. Provider (SubProvider) Select
                        Select::make('sub_provider_id')
                            ->label('Filter by Provider')
                            ->options(function (Get $get) {
                                $partnerId = $get('provider_id');
            
                                if (!$partnerId) {
                                    return SubProvider::all()->pluck('sub_provider_name', 'id');
                                }
            
                                return SubProvider::where('provider_id', $partnerId)
                                    ->pluck('sub_provider_name', 'sub_provider_id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('provider_id')), // Optional: disable if no partner selected
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // This applies the actual database filtering logic
                        return $query
                            ->when(
                                $data['provider_id'],
                                fn (Builder $query, $id): Builder => $query->where('provider_id', $id)
                            )
                            ->when(
                                $data['sub_provider_id'],
                                fn (Builder $query, $id): Builder => $query->where('sub_provider_id', $id)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        // This shows the active filter badges at the top of the table
                        $indicators = [];
                        if ($data['provider_id'] ?? null) {
                            $indicators[] = 'Partner selected';
                        }
                        if ($data['sub_provider_id'] ?? null) {
                            $indicators[] = 'Provider selected';
                        }
                        return $indicators;
                    })
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
