<?php

namespace App\Filament\Resources\Operators\Schemas;

use App\Models\Brand;
use App\Models\Currency;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OperatorForm
{
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

    protected static function getGeneralInfoTab(): Tabs\Tab
    {
        return Tabs\Tab::make('General Information')
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
                            ->default(fn () => Str::random(32)),

                        TextInput::make('client_access_token')
                            ->label('Access Token')
                            ->password()
                            ->revealable()
                            ->default(fn () => Str::random(40)),

                        // Displaying the shared Secret from oauth_clients
                        TextInput::make('oauth_secret')
                            ->label('Shared OAuth Secret')
                            ->placeholder('Auto-generated on save')
                            ->disabled()
                            ->afterStateHydrated(fn ($state, $record, $set) => 
                                $set('oauth_secret', $record?->oauthClient?->client_secret)
                            ),
                    ]),

                Grid::make(2)->schema([
                    Section::make('Credentials')->schema(self::getUserFields('operator'))->columnSpan(1),
                    Section::make('Settings')->schema([
                        ToggleButtons::make('status_id')
                            ->grouped()
                            ->options(['1' => 'Active', '2' => 'Disabled'])
                            ->colors(['1' => 'success', '2' => 'danger'])->inline(),
                        ToggleButtons::make('wallet_type')
                            ->grouped()
                            ->options(['0' => 'Seamless', '1' => 'Transfer'])
                            ->colors(['0' => 'success', '1' => 'info'])->inline(),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    protected static function getBrandsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Brands')
            ->icon('heroicon-m-rectangle-group')
            ->schema([
                Repeater::make('brands')
                    ->relationship()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('brand_name')->required()->extraInputAttributes(['onChange' => 'this.value = this.value.toUpperCase()']),
                            Section::make('Brand User')->schema(self::getUserFields('brand'))->compact(),
                        ]),
                        
                        Section::make('Template: Auto-Generate Clients')
                            ->description('Entering values here will create/update clients for selected currencies.')
                            ->collapsible()
                            // This line ensures it only shows on the "Create" form
                            ->visible(fn ($record) => $record === null) 
                            ->schema([
                                Select::make('temp_currencies')
                                    ->label('Select Currencies')
                                    ->multiple()
                                    ->options(Currency::pluck('code', 'code'))
                                    ->dehydrated(true),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('temp_player_url')
                                        ->label('Player Details URL')
                                        ->dehydrated(true),
                                    TextInput::make('temp_fund_url')
                                        ->label('Fund Transfer URL')
                                        ->dehydrated(true),
                                    TextInput::make('temp_check_url')
                                        ->label('Transaction Checker URL')
                                        ->dehydrated(true),
                                ]),
                            ]),

                        Repeater::make('clients')
                            ->relationship()
                            ->addable(false)
                            ->hidden(fn ($record) => $record === null)
                            ->schema(self::getClientFields(false)),
                    ]),
            ]);
    }

    protected static function getUnassignedTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Unassigned Clients')
            ->schema([
                Repeater::make('unassigned_clients')
                    // Ensure 'clients' is the method name in your Operator model
                    ->relationship('clients', function ($query) {
                        return $query->where(function ($q) {
                            $q->whereNull('brand_id')
                            ->orWhere('brand_id', 0) // Some DBs use 0 instead of NULL
                            ->orWhere('brand_id', '');
                        });
                    })
            ]);
    }

    protected static function getClientFields(bool $showAssignment): array
    {
        return [
            Grid::make(4)->schema([
                TextInput::make('client_id')->disabled(),
                TextInput::make('client_name')->required(),
                TextInput::make('default_currency')->disabled(),
                Select::make('brand_id')
                    ->label('Assign Brand')
                    ->options(fn($get) => Brand::where('operator_id', $get('../../operator_id'))->pluck('brand_name', 'brand_id'))
                    ->visible($showAssignment),
            ]),
            Grid::make(3)->schema([
                Select::make('client_line')->options(['row' => 'ROW', 'asia' => 'Asia']),
                Select::make('api_ver')->options(['2.0' => '2.0', '2.1' => '2.1']),
                ToggleButtons::make('status_id')->options(['1' => 'Active', '2' => 'Disabled'])->inline(),
            ]),
            Grid::make(3)->schema([
                TextInput::make('player_details_url')->url(),
                TextInput::make('fund_transfer_url')->url(),
                TextInput::make('transaction_checker_url')->url(),
            ]),
        ];
    }

    protected static function getUserFields($type): array
    {
        return [
            TextInput::make($type . '_email')
                ->email()
                ->label('Email')
                ->dehydrated(true)
                ->afterStateHydrated(function ($state, $set, $record) use ($type) {
                    if (!$record) return;
                    // Find the user linked to this operator/brand
                    $user = \App\Models\User::where('user_type', $type)
                        ->where('operator_id', $record->operator_id)
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->first();
                    
                    if ($user) $set($type . '_email', $user->email);
                }),

            TextInput::make($type . '_username')
                ->label('Username')
                ->dehydrated(true)
                ->afterStateHydrated(function ($state, $set, $record) use ($type) {
                    if (!$record) return;
                    $user = \App\Models\User::where('user_type', $type)
                        ->where('operator_id', $record->operator_id)
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->first();
                    
                    if ($user) $set($type . '_username', $user->username);
                }),

            TextInput::make($type . '_password')
                ->label('Password')
                ->password()
                ->revealable() // This allows you to click the eye icon to see the raw string
                ->dehydrated(fn ($state) => filled($state))
                ->afterStateHydrated(function ($set, $record) use ($type) {
                    if (!$record) return;
                    $user = \App\Models\User::where('user_type', $type)
                        ->where('operator_id', $record->operator_id)
                        ->when($type === 'brand', fn($q) => $q->where('brand_id', $record->brand_id))
                        ->first();
                    
                    // Set the field to the raw 'password_string' from the database
                    if ($user) $set($type . '_password', $user->password_string);
                }),
        ];
    }
}