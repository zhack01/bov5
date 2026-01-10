<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Models\SubProvider;
use App\Services\ImageSyncService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Game Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('game_name')
                            ->required()
                            ->live(onBlur: true) 
                            ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => self::updateUniCode($set, $get)),

                        Select::make('game_type_id') 
                            ->relationship('gameType', 'game_type_name')
                            ->label('Game Type')
                            ->searchable()
                            ->required()
                            ->preload(),

                        TextInput::make('game_code')
                            ->required(),

                        TextInput::make('uni_game_code')
                            ->required()
                            ->live()
                            ->readOnly() 
                            ->helperText('Auto-generated based on Provider Prefix and Game Name'),
                        TextInput::make('secondary_game_code'),
                    ]),
                

                Section::make('Game Providers')
                    ->columns(2)
                    ->schema([
                        Select::make('provider_id')
                            ->relationship('provider', 'provider_name')
                            ->label('Partner')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('sub_provider_id', null)),
                        Select::make('sub_provider_id')
                            // ->relationship('subProvider', 'sub_provider_name')
                            ->relationship('subProvider', 'sub_provider_name', fn($query, Get $get)=> 
                                $query->where('provider_id', $get('provider_id')))
                            ->label('Provider')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateUniCode($set, $get)),
                    ]),
                
                    Section::make('Visuals & Media')
                        ->description('Manage game assets and icons')
                        ->collapsible()
                        ->schema([
                            // 1. Standard URL input (Existing Data)
                            TextInput::make('icon')
                                ->label('Icon URL')
                                ->helperText('This will be updated automatically if you upload a file below')
                                ->live(onBlur: true),

                            FileUpload::make('image_path')
                                ->label('Provider Image')
                                ->disk('public')
                                // Use the ID to find the slug for the directory
                                ->directory(function (Get $get) {
                                    $id = $get('sub_provider_id');
                                    $name = SubProvider::where('sub_provider_id', $id)->value('sub_provider_name') ?? 'unknown';
                                    return 'uploads/' . str($name)->slug();
                                })
                                ->live()
                                // FIX: Check if ID is present instead of the non-existent name field
                                ->disabled(fn (Get $get) => empty($get('uni_game_code')) || empty($get('sub_provider_id')))
                                ->helperText(fn (Get $get) => empty($get('sub_provider_id')) 
                                    ? 'Please select a Provider before uploading.' 
                                    : 'Image will be stored in the provider folder.')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                    $uniGameCode = $get('uni_game_code') ?? 'default';
                                    return (string) str($uniGameCode . '.' . $file->getClientOriginalExtension());
                                })
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if (!$state) {
                                        $set('icon', null);
                                        return;
                                    }
                            
                                    $file = is_array($state) ? array_first($state) : $state;
                                    if (! $file instanceof TemporaryUploadedFile) return;
                            
                                    $uniGameCode = $get('uni_game_code');
                                    
                                    // Fetch provider name for the path
                                    $subProviderId = $get('sub_provider_id');
                                    $providerName = SubProvider::where('sub_provider_id', $subProviderId)->value('sub_provider_name') ?? 'unknown';
                                    $providerSlug = str($providerName)->slug();
                                    
                                    $extension = $file->getClientOriginalExtension();
                                    $finalFilename = "{$uniGameCode}.{$extension}";
                            
                                    try {
                                        $syncService = app(ImageSyncService::class);
                                        $success = $syncService->pullToAssetServer($file->getRealPath(), $uniGameCode, $providerSlug);
                            
                                        if ($success) {
                                            $remoteUrl = "https://your-asset-server.com/uploads/{$providerSlug}/{$finalFilename}";
                                            $set('icon', $remoteUrl);
                                            Notification::make()->title('Synced to Asset Server')->success()->send();
                                        } else {
                                            throw new \Exception('Sync returned false');
                                        }
                                    } catch (\Exception $e) {
                                        // Local Fallback
                                        $localUrl = asset("storage/uploads/{$providerSlug}/{$finalFilename}");
                                        $set('icon', $localUrl);
                                        Notification::make()->title('Using Local Storage')->warning()->send();
                                    }
                                }),
                            
                            Placeholder::make('preview')
                                ->label('Current Preview')
                                ->content(function (Get $get) {
                                    $url = $get('icon');
                                    if (!$url) return 'No image selected';
                                    
                                    return new HtmlString("
                                        <div class='flex flex-col gap-2'>
                                            <img src='{$url}' class='w-32 h-32 rounded-lg object-cover border shadow-sm' 
                                                 onerror=\"this.src='" . url('/images/placeholder.png') . "';\" />
                                            <span class='text-xs text-gray-500 break-all'>{$url}</span>
                                        </div>
                                    ");
                                }),

                            Textarea::make('game_demo')
                                ->columnSpanFull(),
                        ]),
            
                Section::make('Technical Specifications')
                    ->description('Financials, RTP, and Game Rules')
                    ->columns(3) // Increased to 3 columns for better spacing of numeric fields
                    ->schema([
                        TextInput::make('rtp')
                            ->label('RTP (%)')
                            ->placeholder('e.g. 96.5'),
            
                        TextInput::make('license_fee')
                            ->numeric()
                            ->prefix('$'), // Visual helper
            
                        TextInput::make('pay_lines')
                            ->numeric(),
            
                        TextInput::make('min_bet')
                            ->numeric()
                            ->prefix('$'),
            
                        TextInput::make('max_bet')
                            ->numeric()
                            ->prefix('$'),
            
                        TextInput::make('schedule'),
            
                        DateTimePicker::make('release_date')
                            ->native(false), // Nicer UI
            
                        Toggle::make('on_maintenance')
                            ->label('Maintenance Mode')
                            ->onIcon('heroicon-m-wrench-screwdriver')
                            ->offIcon('heroicon-m-check-circle'),
            
                        Toggle::make('is_freespin')
                            ->label('Free Spins Supported'),
            
                        Textarea::make('info')
                            ->label('Technical Info')
                            ->columnSpanFull(),
            
                        Textarea::make('remarks')
                            ->label('Admin Remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
    
    public static function updateUniCode(Set $set, Get $get)
    {
        $gameName = $get('game_name');
        $subProviderId = $get('sub_provider_id'); // Ensure this field is ->live() in the other section
    
        if (empty($gameName) || empty($subProviderId)) {
            return;
        }
    
        // 1. Fetch prefix from SubProvider model
        $prefix = SubProvider::where('sub_provider_id', $subProviderId)->value('prefix') ?? 'UNK';
    
        // 2. Format Game Name: Uppercase, Replace ' with nothing, spaces with underscores
        // Handling "Game's" -> "GAMES"
        $formattedName = Str::of($gameName)
            ->replace("'", "")
            ->upper()
            ->replace(' ', '_')
            ->toString();
    
        // 3. Combine
        $set('uni_game_code', "{$prefix}_{$formattedName}");
    }
}
