<?php

namespace App\Filament\Resources\Operators\Schemas;

use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Tabs::make('Operator Details')
                ->tabs([
                    // TAB 1: General Info
                    Tabs\Tab::make('General Information')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            Section::make('Core Identity')
                                ->columns(2)
                                ->schema([
                                    // Only shown/disabled based on if record exists
                                    TextInput::make('operator_id')
                                        ->label('System ID')
                                        ->placeholder('Auto-generated')
                                        ->readOnly()
                                        ->disabled()
                                        ->hidden(fn ($record) => $record === null),

                                    TextInput::make('client_name')
                                        ->label('Operator Name')
                                        ->required()
                                        ->readOnly(fn ($record) => $record !== null),

                                    TextInput::make('client_code')
                                        ->label('Operator Code')
                                        ->required()
                                        ->readOnly(fn ($record) => $record !== null),

                                    TextInput::make('client_api_key')
                                        ->label('API Key')
                                        ->default(fn () => Str::random(32))
                                        ->required()
                                        ->password()
                                        ->revealable(),

                                    TextInput::make('client_secret')
                                        ->label('Operator Secret')
                                        ->required()
                                        ->default(fn () => Str::random(40))
                                        ->password()
                                        ->revealable()
                                        ->helperText('40-character alphanumeric secret')
                                        ->afterStateHydrated(function (TextInput $component, $record) {
                                            if (! $record) return;
                                            // Logic to fetch secret from related OAuth client during Edit
                                            $firstSecret = $record->clients()
                                                ->whereHas('oauthClient') 
                                                ->first()
                                                ?->oauthClient
                                                ?->client_secret;
                                            $component->state($firstSecret);
                                        }),
                                ]),

                            Grid::make(2)->schema([
                                Section::make('Operator Credentials')
                                    ->columnSpan(1)
                                    ->schema([
                                        TextInput::make('email')
                                            ->label('Operator Email')
                                            ->email()
                                            ->required()
                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                if (!$record) return;
                                                $user = User::where('operator_id', $record->operator_id)->where('user_type', 'operator')->first();
                                                $component->state($user?->email);
                                            }),

                                        TextInput::make('username')
                                            ->required()
                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                if (!$record) return;
                                                $user = User::where('operator_id', $record->operator_id)->where('user_type', 'operator')->first();
                                                $component->state($user?->username);
                                            }),

                                        TextInput::make('password')
                                            ->password()
                                            ->revealable()
                                            ->required(fn ($record) => $record === null) // Required only on Create
                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                if (!$record) return;
                                                $user = User::where('operator_id', $record->operator_id)->where('user_type', 'operator')->first();
                                                $component->state($user?->password_string);
                                            })
                                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                            ->dehydrated(fn ($state) => filled($state)),
                                    ]),

                                Section::make('Status & Wallet')
                                    ->columnSpan(1)
                                    ->schema([
                                        ToggleButtons::make('status_id')
                                            ->label('Account Status')
                                            ->options(['1' => 'Active', '2' => 'Disabled'])
                                            ->colors(['1'=> 'success', '2'=> 'danger'])
                                            ->default('1')
                                            ->grouped()
                                            ->required(),

                                        ToggleButtons::make('wallet_type')
                                            ->options(['0' => 'Seamless', '1' => 'Transfer'])
                                            ->colors(['0'=> 'success', '1'=> 'info'])
                                            ->default('0')
                                            ->grouped()
                                            ->required(),
                                    ]),
                            ]),
                        ]),

                    Tabs\Tab::make('Brands')
                        ->icon('heroicon-m-rectangle-group')
                        ->schema([
                            Repeater::make('brands')
                                ->relationship()
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['brand_name'] ?? 'New Brand')
                                ->schema([
                                    // --- SECTION 1: Brand Base Details & Currency Logic ---
                                Section::make('Brand Information')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('brand_name')
                                        ->required()
                                        ->live(onBlur: true), // Live so we can use it for labels
                                    
                                    TextInput::make('brand_id')->required(),

                                    Grid::make(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('email')->email()->required()
                                                ->afterStateHydrated(function (TextInput $component, $record) {
                                                    if (!$record) return;
                                                    $user = \App\Models\User::where('brand_id', $record->brand_id)->where('user_type', 'brand')->first();
                                                    $component->state($user?->email);
                                                }),
                                            TextInput::make('username')->required()
                                                ->afterStateHydrated(function (TextInput $component, $record) {
                                                    if (!$record) return;
                                                    $user = \App\Models\User::where('brand_id', $record->brand_id)->where('user_type', 'brand')->first();
                                                    $component->state($user?->username);
                                                }),
                                            TextInput::make('password')->password()->revealable()
                                                ->afterStateHydrated(function (TextInput $component, $record) {
                                                    if (!$record) return;
                                                    $user = \App\Models\User::where('brand_id', $record->brand_id)->where('user_type', 'brand')->first();
                                                    $component->state($user?->password_string);
                                                })
                                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                                ->dehydrated(fn ($state) => filled($state)),
                                        ]),

                                    // --- NEW: Multi-Currency Selection ---
                                    \Filament\Forms\Components\Select::make('selected_currencies')
                                        ->label('Currencies')
                                        ->multiple()
                                        ->options([
                                            'USD' => 'USD',
                                            'JPY' => 'JPY',
                                            'EUR' => 'EUR',
                                            'PHP' => 'PHP',
                                        ])
                                        ->required()
                                        ->columnSpanFull()
                                        ->helperText('Creating/editing a brand with these currencies will auto-generate clients.'),

                                    // --- NEW: URL Configurations ---
                                    TextInput::make('player_details_url')->url()->label('Player Details URL'),
                                    TextInput::make('fund_transfer_url')->url()->label('Fund Transfer URL'),
                                    TextInput::make('transaction_checker_url')->url()->label('Transaction Checker URL')->columnSpanFull(),
                                ]),

                            // --- SECTION 2: Nested Clients Table (Now Read-Only for Auto-Generated Items) ---
                            Section::make('Generated Clients (Agents)')
                                ->description('These are auto-managed based on selected currencies')
                                ->schema([
                                    Repeater::make('clients')
                                        ->relationship()
                                        ->addable(false) // Disable manual add to force the "Currency" logic
                                        ->deletable(false)
                                        ->itemLabel(fn (array $state): ?string => $state['client_name'] ?? 'New Client')
                                        ->collapsible()
                                        ->schema([
                                            Grid::make(3)->schema([
                                                TextInput::make('client_id')->disabled(),
                                                TextInput::make('client_name')->label('Generated Name')->disabled(),
                                                TextInput::make('default_currency')->label('Currency')->disabled(),
                                            ]),
                                        ]),
                                ]),
                                    // --- SECTION 2: Nested Clients Table ---
                                    Section::make('Clients Management')
                                        ->description('Editable client list under this brand')
                                        ->schema([
                                            Repeater::make('clients')
                                                ->relationship()
                                                ->itemLabel(fn (array $state): ?string => $state['client_name'] ?? 'New Client')
                                                ->collapsible()
                                                ->schema([
                                                    // Row 1: Primary Identifiers
                                                    Section::make()
                                                        ->columns(3)
                                                        ->compact()
                                                        ->schema([
                                                            TextInput::make('client_id')
                                                                ->label('Client ID')
                                                                ->disabled()
                                                                ->readOnly(),
                                                            TextInput::make('operator_name_display')
                                                                ->label('Operator Name')
                                                                ->disabled()
                                                                ->readOnly()
                                                                ->afterStateHydrated(function (TextInput $component) {
                                                                    $record = $component->getLivewire()->record;
                                                                    if ($record) {
                                                                        $component->state($record->client_name);
                                                                    }
                                                                })
                                                                ->dehydrated(false),
                                                            TextInput::make('api_ver')
                                                                ->label('API Version')
                                                                ->numeric()
                                                                ->default(5.0),
                                                        ]),
                    
                                                    // Row 2: Credentials & Currency
                                                    Section::make()
                                                        ->columns(2)
                                                        ->compact()
                                                        ->schema([
                                                            TextInput::make('email')->email()
                                                            ->afterStateHydrated(function (TextInput $component, $record) {
                                                                if (! $record) return;
            
                                                                $user = \App\Models\User::where('client_id', $record->client_id)
                                                                    ->where('user_type', 'agent')
                                                                    ->first();
            
                                                                $component->state($user?->email);
                                                            }),
                                                            TextInput::make('password')
                                                                ->label('Password')
                                                                ->password()
                                                                ->revealable()
                                                                // Hydrate with the password_string from DB
                                                                ->afterStateHydrated(function (TextInput $component, $record) {
                                                                    if (! $record) return;

                                                                    $user = \App\Models\User::where('client_id', $record->client_id)
                                                                        ->where('user_type', 'agent')
                                                                        ->first();

                                                                    $component->state($user?->password_string);
                                                                })
                                                                // When saving, you likely want to update that user record
                                                                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                                                ->dehydrated(fn ($state) => filled($state)),
                                                            TextInput::make('default_currency')
                                                                ->label('Currency')
                                                                ->placeholder('e.g. PHP'),
                                                        ]),
                    
                                                    // Row 3: URLs & Logic
                                                    Section::make('API Configurations')
                                                        ->columns(2)
                                                        ->schema([
                                                            TextInput::make('player_details_url')
                                                                ->label('Player Details URL'),
                                                            TextInput::make('fund_transfer_url')
                                                                ->label('Fund Transfer URL'),
                    
                                                            TextInput::make('transaction_checker_url')
                                                                ->label('Transaction Checker URL'),
                                                        ]),
                                                ])
                                                ->addActionLabel('Add Client to Brand'),
                                        ]),
                                ])
                                ->addActionLabel('Add New Brand')
                                ->columnSpanFull(),
                        ]),
                        
                    Tabs\Tab::make('Unassigned Clients')
                        ->icon('heroicon-m-exclamation-triangle')
                        ->visible(function ($record) {
                            // 1. Hide if creating a new operator
                            if (! $record) return false;
                    
                            // 2. Hide if there are zero unassigned clients
                            // This keeps the UI clean for "healthy" operators
                            return $record->clients()
                                ->where(function ($query) {
                                    $query->whereNull('brand_id')
                                          ->orWhere('brand_id', '');
                                })
                                ->exists(); 
                        })
                        ->schema([
                            Section::make('Legacy Client Management')
                                ->description('These clients are not currently linked to a specific Brand.')
                                ->schema([
                                    Repeater::make('unassigned_clients')
                                        ->relationship('clients', function ($query, $record) {
                                            if (! $record) {
                                                return $query->whereRaw('1 = 0');
                                            }
                                            return $query->where('operator_id', $record->operator_id)
                                                ->where(function ($q) {
                                                    $q->whereNull('brand_id')
                                                      ->orWhere('brand_id', '');
                                                });
                                        })
                                        ->itemLabel(fn (array $state): ?string => $state['client_name'] ?? 'New Unassigned Client')
                                        ->collapsible()
                                        ->schema([
                                            // --- Row 1: Primary Identifiers ---
                                            Section::make()
                                                ->columns(3)
                                                ->compact()
                                                ->schema([
                                                    TextInput::make('client_id')
                                                        ->label('Client ID')
                                                        ->disabled()
                                                        ->readOnly(),
                                                    TextInput::make('operator_name_display')
                                                        ->label('Operator Name')
                                                        ->disabled()
                                                        ->readOnly()
                                                        ->afterStateHydrated(function (TextInput $component) {
                                                            $record = $component->getLivewire()->record;
                                                            if ($record) {
                                                                $component->state($record->client_name);
                                                            }
                                                        })
                                                        ->dehydrated(false),
                                                    TextInput::make('api_ver')
                                                        ->label('API Version')
                                                        ->numeric()
                                                        ->default(5.0),
                                                ]),
                    
                                            // --- Row 2: Credentials & Currency ---
                                            Section::make()
                                                ->columns(3)
                                                ->compact()
                                                ->schema([
                                                    TextInput::make('email')
                                                        ->email()
                                                        ->afterStateHydrated(function (TextInput $component, $record) {
                                                            if (! $record) return;
                                                            $user = \App\Models\User::where('client_id', $record->client_id)
                                                                ->where('user_type', 'agent')
                                                                ->first();
                                                            $component->state($user?->email);
                                                        }),
                                                    TextInput::make('password')
                                                        ->label('Password')
                                                        ->password()
                                                        ->revealable()
                                                        ->afterStateHydrated(function (TextInput $component, $record) {
                                                            if (! $record) return;
                                                            $user = \App\Models\User::where('client_id', $record->client_id)
                                                                ->where('user_type', 'agent')
                                                                ->first();
                                                            $component->state($user?->password_string);
                                                        })
                                                        ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                                        ->dehydrated(fn ($state) => filled($state)),
                                                    TextInput::make('default_currency')
                                                        ->label('Currency')
                                                        ->placeholder('e.g. PHP'),
                                                ]),
                    
                                            // --- Row 3: URLs & Logic ---
                                            Section::make('API Configurations')
                                                ->columns(3)
                                                ->schema([
                                                    TextInput::make('player_details_url')
                                                        ->label('Player Details URL'),
                                                    TextInput::make('fund_transfer_url')
                                                        ->label('Fund Transfer URL'),
                                                    TextInput::make('transaction_checker_url')
                                                        ->label('Transaction Checker URL'),
                                                ]),
                                        ])
                                        ->addActionLabel('Add New Unassigned Client'),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
