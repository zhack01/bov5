<?php

namespace App\Filament\Resources\TransactionMonitorings\Pages;

use App\Filament\Resources\TransactionMonitorings\TransactionMonitoringResource;
use App\Traits\HasTransactionDetails;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactionMonitorings extends ListRecords
{

    use HasTransactionDetails;

    protected static string $resource = TransactionMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
