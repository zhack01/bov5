<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                Textarea::make('username')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('password')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel()
                    ->numeric(),
                Textarea::make('image')
                    ->columnSpanFull(),
                Textarea::make('user_type')
                    ->columnSpanFull(),
                TextInput::make('is_admin')
                    ->default('2'),
                TextInput::make('user_metadata'),
                Textarea::make('password_string')
                    ->columnSpanFull(),
                TextInput::make('operator_id')
                    ->numeric(),
                TextInput::make('client_id')
                    ->numeric()
                    ->default(0),
                TextInput::make('brand_id')
                    ->numeric()
                    ->default(0),
                TextInput::make('status_id')
                    ->numeric(),
                TextInput::make('status')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
