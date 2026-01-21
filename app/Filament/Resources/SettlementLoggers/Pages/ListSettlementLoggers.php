<?php

namespace App\Filament\Resources\SettlementLoggers\Pages;

use App\Filament\Resources\SettlementLoggers\SettlementLoggerResource;
use App\Traits\HasTransactionDetails;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettlementLoggers extends ListRecords
{
    use HasTransactionDetails;

    protected static string $resource = SettlementLoggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
