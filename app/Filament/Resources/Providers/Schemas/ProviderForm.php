<?php

namespace App\Filament\Resources\Providers\Schemas;

use Illuminate\Validation\Rules\Unique;
use App\Models\Currency;
use App\Models\SubProviderCode;
use App\Services\ImageSyncService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider Information')
                    ->schema([
                        TextInput::make('sub_provider_name')
                            ->label('Provider Name')
                            ->required()
                            ->maxLength(45)
                            // 1. Standard check for the main sub_providers table
                            ->unique('sub_providers', 'sub_provider_name', ignoreRecord: true)
                            
                            // 2. Manual check for the sub_provider_code table
                            ->unique(
                                table: 'sub_provider_code', 
                                column: 'sub_provider_name', 
                                ignoreRecord: false, // We handle ignoring manually below
                                modifyRuleUsing: function (Unique $rule, Get $get) {
                                    $id = $get('sub_provider_id');
                                    
                                    if ($id) {
                                        // We tell the rule to ignore the record where 'sub_provider_id' 
                                        // (the column in sub_provider_code) matches the current ID.
                                        return $rule->ignore($id, 'sub_provider_id');
                                    }
                                    
                                    return $rule;
                                }
                            ),

                        TextInput::make('prefix')
                            ->label('PREFIX')
                            ->required()
                            ->live() // Crucial: updates the form state as the user types
                            ->maxLength(45),

                        Select::make('provider_id')
                            ->label('Parent Partner')
                            ->relationship('provider', 'provider_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('icon')
                            ->label('Icon URL')
                            ->helperText('This will be updated automatically if you upload a file below')
                            ->live(onBlur: true),

                        FileUpload::make('image_path')
                            ->label('Provider Image')
                            ->disk('public')
                            ->directory('uploads/sub-providers')
                            ->live()
                            // 1. Disable the upload if prefix is empty
                            ->disabled(fn (Get $get) => empty($get('prefix')))
                            // 2. Add helper text to explain why it's disabled
                            ->helperText(fn (Get $get) => empty($get('prefix')) 
                                ? 'Please enter a PREFIX above before uploading an image.' 
                                : 'Image will be named after the prefix.')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                // 3. Use prefix for filename
                                $prefix = $get('prefix');
                                return (string) str($prefix . '.' . $file->getClientOriginalExtension());
                            })
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (!$state) {
                                    $set('icon', null);
                                    return;
                                }
                            
                                // Filament FileUpload state can be an array of strings or TemporaryUploadedFile objects
                                $file = is_array($state) ? array_first($state) : $state;
                            
                                if (! $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    return;
                                }
                            
                                $prefix = $get('prefix');
                                $partner = $get('provider_id');
                                
                                // Use the specific filename you generated in getUploadedFileNameForStorageUsing
                                $extension = $file->getClientOriginalExtension();
                                $finalFilename = "{$prefix}.{$extension}";
                            
                                try {
                                    $syncService = app(ImageSyncService::class);
                                    
                                    // IMPORTANT: We pass the real path of the temp file to the service
                                    $success = $syncService->pullToAssetServer($file->getRealPath(), $prefix, $partner);
                            
                                    if ($success) {
                                        // Use the final storage filename for the URL
                                        $remoteUrl = "https://your-asset-server.com/uploads/sub-providers/{$finalFilename}";
                                        $set('icon', $remoteUrl);
                                        
                                        Notification::make()->title('Synced to Asset Server')->success()->send();
                                    } else {
                                        throw new \Exception('Sync failed');
                                    }
                            
                                } catch (\Exception $e) {
                                    // FALLBACK: Use local storage URL
                                    // We use the storage helper to get the correct public path
                                    $localUrl = asset('storage/uploads/sub-providers/' . $finalFilename);
                                    $set('icon', $localUrl);
                            
                                    Notification::make()
                                        ->title('Using Local Storage')
                                        ->body('Connection failed: ' . $e->getMessage())
                                        ->warning()
                                        ->send();
                                }
                            }),

                        Placeholder::make('preview')
                            ->label('Icon Preview')
                            ->content(function (Get $get) {
                                $url = $get('icon');
                                if (!$url) return 'No image selected';
                                return new HtmlString("<img src='{$url}' class='w-24 h-24 rounded-lg border shadow-sm' />");
                            }),

                        Toggle::make('on_maintenance')
                            ->label('Maintenance Mode')
                            ->default(false),
                    ]),
            ]);
    }
}