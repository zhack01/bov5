<?php

namespace App\Filament\Resources\Providers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider.provider_id')
                    ->label('PartnerId')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('provider.provider_name')
                    ->label('Partner')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sub_provider_id')
                    ->label('ProviderId')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sub_provider_name')
                    ->label('Provider')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('icon')
                    ->label('Game Icon')
                    ->circular()
                    ->height(40)
                    // IMPORTANT: Tell Filament the 'icon' is already a full URL, not a file path
                    ->state(fn ($record) => $record->icon) 
                    ->url(fn ($record) => $record->icon, shouldOpenInNewTab: true)
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->extraImgAttributes([
                        'onerror' => "this.src='" . url('/images/placeholder.png') . "'; this.onerror=null;",
                    ]),
                ToggleColumn::make('on_maintenance')
                    ->label('Maintenance'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
