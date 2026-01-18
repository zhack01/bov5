<?php

namespace App\Filament\Resources\ComparativeInsights\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ComparativeInsightsTable
{
    public static function configure(Table $table): Table
    {
        Tabs::make('Tabs')
        ->tabs([
            Tab::make('Tab 1')
                ->schema([
                    // ...
                ]),
            Tab::make('Tab 2')
                ->schema([
                    // ...
                ]),
            Tab::make('Tab 3')
                ->schema([
                    // ...
                ]),
        ])
        ->vertical();
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
}
