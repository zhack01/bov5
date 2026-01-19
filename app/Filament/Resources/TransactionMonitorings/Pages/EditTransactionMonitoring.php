<?php

namespace App\Filament\Resources\TransactionMonitorings\Pages;

use App\Filament\Resources\TransactionMonitorings\TransactionMonitoringResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransactionMonitoring extends EditRecord
{
    protected static string $resource = TransactionMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
}
