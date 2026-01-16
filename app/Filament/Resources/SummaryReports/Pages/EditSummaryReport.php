<?php

namespace App\Filament\Resources\SummaryReports\Pages;

use App\Filament\Resources\SummaryReports\SummaryReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSummaryReport extends EditRecord
{
    protected static string $resource = SummaryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
