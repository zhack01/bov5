<?php

namespace App\Filament\Resources\SettlementLoggers\Pages;

use App\Filament\Resources\SettlementLoggers\SettlementLoggerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSettlementLogger extends EditRecord
{
    protected static string $resource = SettlementLoggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
