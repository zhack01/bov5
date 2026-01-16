<?php

namespace App\Filament\Resources\GameReports\Pages;

use App\Filament\Resources\GameReports\GameReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameReport extends ViewRecord
{
    protected static string $resource = GameReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
