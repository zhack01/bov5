<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure($form) 
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->columns(1) // Changed to 2 for better layout
                    ->schema([
                        TextInput::make('name')
                            ->required(),

                        TextInput::make('username') // Added the username field
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrated(true),

                        TextInput::make('email')
                            ->label('Email address')
                            ->required()
                            ->email() 
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true),

                        TextInput::make('phone')
                            ->tel()
                            ->default(null),

                        Hidden::make('user_type')
                            ->default('staff') 
                            ->dehydrated(fn ($context) => $context === 'create'),
                        Hidden::make('password_string'),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->live(onBlur: true) // Updates the hidden field when you finish typing
                            ->required(fn (string $context): bool => $context === 'create')
                            
                            // Sync the plain text to the hidden 'password_string' field
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('password_string', $state);
                            })

                            // Load existing plain text password into the input when editing
                            ->afterStateHydrated(function (TextInput $component, $record) {
                                if ($record) {
                                    $component->state($record->password_string);
                                }
                            })

                            // Hash the password for the standard 'password' column before saving
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state)),

                        TextInput::make('allowed_ip')
                            ->label('Authorized IP Address')
                            ->placeholder('e.g. 192.168.1.1')
                            ->helperText('Leave blank to allow login from any IP.'),

                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
