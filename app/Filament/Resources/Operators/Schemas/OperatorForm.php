<?php

namespace App\Filament\Resources\Operators\Schemas;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Currency;
use App\Models\OAuthClients;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;

// Filament v4 Unified Schema Imports
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

// Filament v4 Form Component Imports
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperatorForm
{
    /**
     * In Filament v4, the entry point for custom schema classes 
     * typically uses the base Schema class.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Operator Management')
                ->tabs([
                    self::getGeneralInfoTab(),
                    self::getBrandsTab(),
                    self::getUnassignedTab(),
                ])->columnSpanFull(),
        ]);
    }

    protected static function getGeneralInfoTab(): Tab
    {
        return Tab::make('General Information')
            ->icon('heroicon-m-information-circle')
            ->schema([
                Section::make('Operator Core')
                    ->columns(2)
                    ->schema([
                        TextInput::make('operator_id')->label('System ID')->disabled(),
                        TextInput::make('client_name')->label('Operator Name')->required(),
                        
                        TextInput::make('client_code')
                            ->label('Operator Code')
                            ->password()
                            ->revealable()
                            ->required(),

                            TextInput::make('client_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->default(fn () => Str::random(32))
                            ->suffixAction(
                                Action::make('refreshApiKey')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('warning')
                                    ->action(fn ($set) => $set('client_api_key', Str::random(32)))
                            ),
    
                        TextInput::make('client_access_token')
                            ->label('Access Token')
                            ->password()
                            ->revealable()
                            ->default(fn () => Str::random(40))
                            ->suffixAction(
                                Action::make('refreshAccessToken')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('warning')
                                    ->action(fn ($set) => $set('client_access_token', Str::random(40)))
                            ),
    
                        TextInput::make('oauth_secret')
                            ->label('Shared OAuth Secret')
                            ->password()
                            ->revealable()
                            ->default(fn () => Str::random(40))
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(true)
                            ->afterStateHydrated(function ($state, $record, $set) {
                                if ($record) {
                                    $set('oauth_secret', $record->oauthClient?->client_secret);
                                }
                            })
                            ->suffixAction(
                                Action::make('refreshSecret')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('warning')
                                    ->hidden(fn ($record) => $record !== null)
                                    ->action(fn ($set) => $set('oauth_secret', Str::random(40)))
                            ),
                    ]),

                Grid::make(2)->schema([
                    Section::make('Credentials')->schema(self::getUserFields('operator'))->columnSpan(1),
                    Section::make('Settings')->schema([
                        ToggleButtons::make('status_id')
                            ->grouped()
                            ->dehydrated(true)
                            ->default('1')
                            ->options(['1' => 'Active', '2' => 'Disabled'])
                            ->colors(['1' => 'success', '2' => 'danger'])->inline(),
                        ToggleButtons::make('wallet_type')
                            ->grouped()
                            ->default('0')
                            ->dehydrated(true)
                            ->options(['0' => 'Seamless', '1' => 'Transfer'])
                            ->colors(['0' => 'success', '1' => 'info'])->inline(),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    protected static function getBrandsTab(): Tab
    {
        return Tab::make('Brands')
            ->icon('heroicon-m-rectangle-group')
            ->schema([
                Repeater::make('brands')
                    ->relationship()
                    ->live()
                    // Layout enhancements
                    ->collapsible() 
                    ->collapsed(fn ($record) => $record !== null) // Collapse by default on Edit
                    ->itemLabel(fn (array $state): ?string => 
                        ($state['brand_name'] ?? 'New Brand') . 
                        (isset($state['brand_id']) ? " (ID: {$state['brand_id']})" : "")
                    )
                    ->saveRelationshipsUsing(static function ($component, $state, $record) {
                        foreach ($state as $brandData) {
                            $brandName = strtoupper($brandData['brand_name'] ?? '');

                            // Update existing or create new Brand
                            $brand = $record->brands()->updateOrCreate(
                                ['brand_id' => $brandData['brand_id'] ?? null],
                                [
                                    'brand_name' => $brandName,
                                    'operator_id' => $record->operator_id,
                                ]
                            );

                            // Automation: Run only if brand is new (no clients yet)
                            if ($brand->clients()->count() === 0 && !empty($brandData['temp_currencies'])) {
                                
                                $sharedSecret = DB::table('oauth_clients')
                                    ->join('clients', 'oauth_clients.client_id', '=', 'clients.client_id')
                                    ->where('clients.operator_id', $record->operator_id)
                                    ->value('client_secret') 
                                    ?? Str::random(40);

                                if (!empty($brandData['brand_email'])) {
                                    User::create([
                                        'email'           => $brandData['brand_email'],
                                        'username'        => $brandData['brand_username'],
                                        'password'        => bcrypt($brandData['brand_password'] ?? 'password'),
                                        'password_string' => $brandData['brand_password'],
                                        'user_type'       => 'brand',
                                        'operator_id'     => $record->operator_id,
                                        'brand_id'        => $brand->brand_id,
                                    ]);
                                }

                                foreach ($brandData['temp_currencies'] as $code) {
                                    $client = Client::create([
                                        'operator_id'               => $record->operator_id,
                                        'brand_id'                  => $brand->brand_id,
                                        'client_name'               => strtoupper($brandName . '_' . $code),
                                        'default_currency'          => $code,
                                        'status_id'                 => 1,
                                        'api_ver'                   => '2.0',
                                        'player_details_url'        => $brandData['temp_player_url'] ?? 'https://default.com',
                                        'fund_transfer_url'         => $brandData['temp_fund_url'] ?? 'https://default.com',
                                        'transaction_checker_url'   => $brandData['temp_check_url'] ?? 'https://default.com',
                                        'balance_url'               => $brandData['temp_player_url'] ?? 'https://default.com',
                                        'debit_credit_transfer_url' => $brandData['temp_fund_url'] ?? 'https://default.com',
                                    ]);

                                    OAuthClients::create([
                                        'client_id'     => $client->client_id,
                                        'client_secret' => $sharedSecret,
                                    ]);
                                }
                            }
                        }
                    })
                    ->schema([
                        Grid::make(2) 
                            ->schema([
                                Group::make([
                                    Section::make('Brand Identity')
                                        ->schema([
                                            Grid::make(3)->schema([
                                                TextInput::make('brand_id')
                                                    ->label('Brand ID')
                                                    ->disabled()
                                                    ->dehydrated(true),
                                                
                                                TextInput::make('brand_name')
                                                    ->required()
                                                    ->columnSpan(2)
                                                    ->extraInputAttributes(['onInput' => 'this.value = this.value.toUpperCase()'])
                                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                            ]),
                                        ]),
                                ])->columnSpan(1),
                        
                                Group::make([
                                    Section::make('Brand User Credentials')
                                        ->description('Manage login access for this brand')
                                        ->schema(self::getUserFields('brand'))
                                        ->compact(),
                                ])->columnSpan(1),
                            ]),
                        Section::make('Template: Auto-Generate Clients')
                            ->description('Fill this to auto-create clients for new brands.')
                            ->visible(fn ($get) => $get('brand_id') === null) 
                            ->schema([
                                Select::make('temp_currencies')
                                    ->multiple()
                                    ->options(Currency::pluck('code', 'code'))
                                    ->dehydrated(true),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('temp_player_url')->placeholder('Player API URL')->dehydrated(true),
                                    TextInput::make('temp_fund_url')->placeholder('Fund API URL')->dehydrated(true),
                                    TextInput::make('temp_check_url')->placeholder('Check API URL')->dehydrated(true),
                                ]),
                            ]),

                        Repeater::make('clients')
                            ->relationship()
                            ->addable(false)
                            ->collapsible() 
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['client_name'] ?? 'New Client') . 
                                (isset($state['client_id']) ? " (ID: {$state['client_id']})" : "")
                            )
                            ->schema(self::getClientFields(false))
                            ->hidden(fn ($get) => $get('brand_id') === null),
                    ]),
            ]);
    }

    protected static function getUnassignedTab(): Tab
    {
        return Tab::make('Unassigned Clients')
            ->icon('heroicon-m-exclamation-triangle')
            // 1. Only show the tab if the operator actually has unassigned clients
            ->visible(fn ($record) => 
                $record && $record->clients()
                    ->where('operator_id', $record->operator_id) // Match parent operator
                    ->where(fn ($query) => $query->whereNull('brand_id')->orWhere('brand_id', 0))
                    ->exists()
            )
            ->schema([
                Repeater::make('unassigned_clients')
                    ->relationship('clients', function ($query, $record) {
                        if (!$record) {
                            return $query->whereRaw('1 = 0');
                        }
                    
                        return $query->where('operator_id', $record->operator_id)
                            ->where(fn ($q) => $q->whereNull('brand_id')->orWhere('brand_id', 0));
                    })
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->schema(self::getClientFields(true))
            ]);
    }

    protected static function getClientFields(bool $showAssignment): array
    {
        return [
            // Row 1: Identification & Assignment
            Grid::make(4)->schema([
                TextInput::make('client_id')
                    ->label('ID')
                    ->disabled(),
                TextInput::make('client_name')
                    ->label('Client Name')
                    ->required(),
                TextInput::make('default_currency')
                    ->label('Currency')
                    ->disabled(),
                Select::make('brand_id')
                    ->label('Assign Brand')
                    ->options(fn($get) => Brand::where('operator_id', $get('../../operator_id'))->pluck('brand_name', 'brand_id'))
                    ->visible($showAssignment)
                    ->searchable()
                    ->preload(),
            ]),

            // Row 2: Logic & Status
            Grid::make(3)->schema([
                Select::make('client_line')
                    ->options(['row' => 'ROW', 'asia' => 'Asia']),
                Select::make('api_ver')
                    ->label('API Version')
                    ->options(['2.0' => '2.0', '2.1' => '2.1']),
                ToggleButtons::make('status_id')
                    ->label('Status')
                    ->options(['1' => 'Active', '2' => 'Disabled'])
                    ->colors(['1' => 'success', '2' => 'danger'])
                    ->inline(),
            ]),

            // Row 3: API Endpoint URLs
            Grid::make(3)->schema([
                TextInput::make('player_details_url')
                    ->label('Player Details URL')
                    ->url()
                    ->placeholder('https://...'),
                TextInput::make('fund_transfer_url')
                    ->label('Fund Transfer URL')
                    ->url()
                    ->placeholder('https://...'),
                TextInput::make('transaction_checker_url')
                    ->label('Transaction Checker URL')
                    ->url()
                    ->placeholder('https://...'),
            ]),

            Section::make('Agent Credentials')
                ->description('Optional: Create/Update login for this specific client line')
                ->collapsible()
                ->collapsed() // Keep it hidden by default since it's not required
                ->schema(self::getUserFields('agent')) 
                ->compact(),
        ];
    }

    protected static function getUserFields($type): array
    {
        return [
            TextInput::make($type . '_email')
                ->email()
                ->label('Email')
                ->afterStateHydrated(function ($set, $record) use ($type) {
                    if (!$record) return;

                    $user = \App\Models\User::where('user_type', $type)
                        ->when($type === 'operator', fn($q) => $q->where('operator_id', $record->operator_id))
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->when($type === 'agent', fn($q) => $q->where('client_id', $record->client_id))
                        ->first();
                    
                    if ($user) $set($type . '_email', $user->email);
                }),

            TextInput::make($type . '_username')
                ->label('Username')
                ->afterStateHydrated(function ($set, $record) use ($type) {
                    if (!$record) return;
                    $user = \App\Models\User::where('user_type', $type)
                        ->when($type === 'operator', fn($q) => $q->where('operator_id', $record->operator_id))
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->when($type === 'agent', fn($q) => $q->where('client_id', $record->client_id))
                        ->first();
                    if ($user) $set($type . '_username', $user->username);
                }),

            TextInput::make($type . '_password')
                ->password()
                ->revealable()
                ->label('Password')
                ->afterStateHydrated(function ($set, $record) use ($type) {
                    if (!$record) return;
                    $user = \App\Models\User::where('user_type', $type)
                        ->when($type === 'operator', fn($q) => $q->where('operator_id', $record->operator_id))
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->when($type === 'agent', fn($q) => $q->where('client_id', $record->client_id))
                        ->first();
                    if ($user) $set($type . '_password', $user->password_string);
                }),
        ];
    }
}