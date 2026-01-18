<?php

namespace App\Filament\Resources\ComparativeInsights\Pages;

use App\Filament\Resources\ComparativeInsights\ComparativeInsightsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComparativeInsights extends EditRecord
{
    protected static string $resource = ComparativeInsightsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
