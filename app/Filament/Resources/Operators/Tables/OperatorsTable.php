<?php

namespace App\Filament\Resources\Operators\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OperatorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operator_id')
                    ->label('OperatorId')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('client_name')
                    ->sortable()
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('wallet_type')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'Seamless Wallet',
                        1 => 'Transfer Wallet',
                        default => 'Unknown',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        0 => 'success', // Green
                        1 => 'info',    // Blue
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('client_name', 'asc')
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
