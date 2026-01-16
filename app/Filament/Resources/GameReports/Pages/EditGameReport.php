<?php

namespace App\Filament\Resources\GameReports\Pages;

use App\Filament\Resources\GameReports\GameReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameReport extends EditRecord
{
    protected static string $resource = GameReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
