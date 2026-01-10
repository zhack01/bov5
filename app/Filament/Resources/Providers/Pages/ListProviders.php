<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Providers\ProviderResource;
use App\Models\Currency;
use App\Models\Provider as Partner; 
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
            // 1. The button to create a NEW PARTNER (Top-level)
            Action::make('createPartner')
                ->label('New Partner')
                ->icon('heroicon-o-building-office')
                ->modalWidth('3xl')
                ->form([
                    TextInput::make('provider_name')
                        ->label('Partner Name')
                        ->required(),
                    
                    KeyValue::make('languages')
                        ->default([
                            'en' => 'en_US',
                            'ja' => 'ja_JP',
                            'ko' => 'Ko_KR',
                        ]),

                    // This is the Select2-style component
                    Select::make('currencies')
                        ->label('Supported Currencies')
                        ->multiple() // Allows multiple selection
                        ->searchable() // Enables Select2-style searching
                        ->preload() // Loads options immediately for a better UI
                        ->options(Currency::all()->pluck('code', 'code')) // Using code for both key and value
                        ->getSearchResultsUsing(fn (string $search): array => Currency::where('code', 'like', "%{$search}%")->limit(50)->pluck('code', 'code')->toArray())
                        ->required()
                        // Format the data into the {"USD": "USD"} structure before saving
                        ->dehydrateStateUsing(fn ($state) => collect($state)->mapWithKeys(fn ($item) => [$item => $item])->toArray()),
                ])
                ->action(function (array $data) {
                    $data['icon'] = '';
                    
                    Partner::create($data);

                    Notification::make()
                        ->title('Partner created successfully')
                        ->success()
                        ->send();
                }),

            // 2. The standard button for NEW PROVIDER (Sub-Provider)
            CreateAction::make()
                ->label('New Provider'),
        ];
    }
}
