<?php

namespace App\Filament\Resources\SettlementLoggers\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettlementLoggerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Settlement Details')
                    ->schema([
                        TextInput::make('display_id')
                            ->label('Transaction ID')
                            ->readOnly()
                            ->disabled(),
                            
                        Select::make('settle_type')
                            ->options([
                                'credit' => 'Credit / Win',
                                'debit' => 'Debit / Bet',
                                'rollback' => 'Rollback',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('amount')
                            ->label('Settlement Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$'), // Amount is now editable but pre-filled

                        Textarea::make('reason')
                            ->required()
                            ->columnSpanFull(),
                            
                        Hidden::make('raw_id'),
                        Hidden::make('round_id'),
                        Hidden::make('trans_id'),
                        Hidden::make('operator_id'),
                        Hidden::make('client_name'),
                    ])->columns(3)
            ]);
    }
}
