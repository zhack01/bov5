<?php

namespace App\Filament\Resources\ComparativeInsights\Pages;

use App\Filament\Resources\ComparativeInsights\ComparativeInsightsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComparativeInsights extends ListRecords
{
    protected static string $resource = ComparativeInsightsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

      /**
     * @param \Illuminate\Database\Eloquent\Model $record
     */
    public function getTableRecordKey($record): string
    {
        // Use the alias from your query
        return (string) $record->per_player_id;
    }

}
