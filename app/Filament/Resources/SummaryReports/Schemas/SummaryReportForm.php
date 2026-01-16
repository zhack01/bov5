<?php

namespace App\Filament\Resources\SummaryReports\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SummaryReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('operator_id')
                    ->required()
                    ->numeric(),
                TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                TextInput::make('player_id')
                    ->required()
                    ->numeric(),
                TextInput::make('provider_id')
                    ->required()
                    ->numeric(),
                TextInput::make('game_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bet')
                    ->required()
                    ->numeric(),
                TextInput::make('win')
                    ->required()
                    ->numeric(),
                TextInput::make('ggr')
                    ->required()
                    ->numeric(),
                TextInput::make('total_rounds')
                    ->numeric(),
            ]);
    }
}
