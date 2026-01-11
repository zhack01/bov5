<?php

namespace App\Filament\Resources\Operators\Schemas;

use App\Models\Brand;
use App\Models\Currency;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class OperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Operator Details')
                ->tabs([
                    self::getGeneralInfoTab(),
                    self::getBrandsTab(),
                    self::getUnassignedTab(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * TAB 1: General Info
     */
    protected static function getGeneralInfoTab(): Tabs\Tab
    {
        return Tabs\Tab::make('General Information')
            ->icon('heroicon-m-information-circle')
            ->schema([
                Section::make('Core Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('operator_id')
                            ->label('System ID')
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
                            ->password()
                            ->revealable()
                            ->suffixAction(self::generateTokenAction('client_api_key', 32)),

                        TextInput::make('client_secret')
                            ->label('Operator Secret')
                            ->default(fn () => Str::random(40))
                            ->password()
                            ->revealable()
                            ->suffixAction(self::generateTokenAction('client_secret', 40)),
                    ]),

                Grid::make(2)->schema([
                    Section::make('Operator Credentials')
                        ->columnSpan(1)
                        ->schema(self::getUserCredentialFields('operator', false)),

                    Section::make('Status & Wallet')
                        ->columnSpan(1)
                        ->schema([
                            ToggleButtons::make('status_id')
                                ->label('Account Status')
                                ->options(['1' => 'Active', '2' => 'Disabled'])
                                ->colors(['1' => 'success', '2' => 'danger'])
                                ->default('1')
                                ->grouped(),

                            ToggleButtons::make('wallet_type')
                                ->options(['0' => 'Seamless', '1' => 'Transfer'])
                                ->colors(['0' => 'success', '1' => 'info'])
                                ->default('0')
                                ->grouped(),
                        ]),
                ]),
            ]);
    }

    /**
     * TAB 2: Brands & Automated Client Management
     */
    protected static function getBrandsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Brands')
            ->icon('heroicon-m-rectangle-group')
            ->schema([
                Repeater::make('brands')
                    ->relationship()
                    ->collapsible()
                    ->itemLabel(fn ($state) => $state['brand_name'] ?? 'New Brand')
                    ->schema([
                        Section::make('Brand Template (Apply to Clients)')
                            ->description('These fields are not saved in the Brand table. They are used to auto-generate or update the Clients below.')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('brand_name')
                                        ->required()
                                        ->live(onBlur: true),
                                    
                                    // "selected_currencies" is dehydrated(true) so the Page hook can read it
                                    Select::make('selected_currencies')
                                        ->label('Generate Clients for Currencies')
                                        ->multiple()
                                        ->options(Currency::all()->pluck('code', 'code'))
                                        ->searchable()
                                        ->dehydrated(true), 
                                ]),

                                Grid::make(3)->schema([
                                    TextInput::make('player_details_url')
                                        ->label('Template Player Details URL')
                                        ->url()
                                        ->dehydrated(true),

                                    TextInput::make('fund_transfer_url')
                                        ->label('Template Fund Transfer URL')
                                        ->url()
                                        ->dehydrated(true),

                                    TextInput::make('transaction_checker_url')
                                        ->label('Template Transaction Checker URL')
                                        ->url()
                                        ->dehydrated(true),
                                ]),
                            ]),
                        
                        Section::make('Active Clients for this Brand')
                            ->headerActions([
                                // Optional visual hint
                                Action::make('hint')
                                    ->label('Auto-managed by Template')
                                    ->icon('heroicon-m-sparkles')
                                    ->color('gray'),
                            ])
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Repeater::make('clients')
                                    ->relationship()
                                    ->disabled()
                                    ->addable(false)
                                    ->deletable(false)
                                    ->schema(self::getClientFields()), 
                            ]),
                    ]),
            ]);
    }

    /**
     * TAB 3: Unassigned Clients
     */
    protected static function getUnassignedTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Unassigned Clients')
            ->icon('heroicon-m-exclamation-triangle')
            ->visible(fn ($record) => 
                $record && $record->clients()
                    ->where(fn($q) => $q->whereNull('brand_id')->orWhere('brand_id', ''))
                    ->exists()
            )
            ->schema([
                Section::make('Legacy Client Management')
                    ->description('Assign these orphaned clients to a brand to move them out of this list.')
                    ->schema([
                        Repeater::make('unassigned_clients')
                            ->relationship('clients', fn ($query) => 
                                $query->where(fn($q) => $q->whereNull('brand_id')->orWhere('brand_id', ''))
                            )
                            ->itemLabel(fn ($state) => $state['client_name'] ?? 'Unassigned Client')
                            ->collapsible()
                            ->schema(self::getClientFields(true)),
                    ]),
            ]);
    }

    /**
     * Reusable Client Fields
     */
    protected static function getClientFields(bool $isUnassigned = false): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('client_id')->label('ID')->disabled(),
                TextInput::make('client_name')->label('Name')->required(),
                TextInput::make('default_currency')->label('Currency'),
            ]),

            Section::make('Assignment')
                ->visible($isUnassigned)
                ->schema([
                    Select::make('brand_id')
                        ->label('Assign to Brand')
                        ->options(function ($get) {
                            $operatorId = $get('../../operator_id');
                            return Brand::where('operator_id', $operatorId)->pluck('brand_name', 'brand_id');
                        })
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Client API Configurations')
                ->columns(3)
                ->schema([
                    TextInput::make('player_details_url')->url(),
                    TextInput::make('fund_transfer_url')->url(),
                    TextInput::make('transaction_checker_url')->url(),
                ]),
        ];
    }

    /**
     * Helper: Credentials logic
     */
    protected static function getUserCredentialFields(string $type, bool $required = true): array
    {
        return [
            TextInput::make('email')
                ->email()
                ->required($required)
                ->afterStateHydrated(fn ($component, $record) => $component->state(self::fetchUser($record, $type)?->email)),
            
            TextInput::make('username')
                ->required($required)
                ->afterStateHydrated(fn ($component, $record) => $component->state(self::fetchUser($record, $type)?->username)),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn ($record) => $required && !$record)
                ->afterStateHydrated(fn ($component, $record) => $component->state(self::fetchUser($record, $type)?->password_string))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),
        ];
    }

    protected static function fetchUser($record, $type)
    {
        if (!$record) return null;
        $query = User::where('user_type', $type);
        return match($type) {
            'operator' => $query->where('operator_id', $record->operator_id)->first(),
            'brand'    => $query->where('brand_id', $record->brand_id)->first(),
            'agent'    => $query->where('client_id', $record->client_id)->first(),
            default    => null,
        };
    }

    protected static function generateTokenAction(string $field, int $length): Action
    {
        return Action::make('generate_' . $field)
            ->icon('heroicon-m-arrow-path')
            ->action(fn ($set) => $set($field, Str::random($length)));
    }
}