<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function afterCreate(): void
    {
        // Initialize the service and run it
        app(\App\Services\ImageSyncService::class)->pullToAssetServer(
            $this->record->image_path,
            $this->record->uni_game_code,
            $this->record->partner
        );
    }
}
