<?php

namespace App\Filament\Resources\SummaryReports\Pages;

use App\Filament\Resources\SummaryReports\SummaryReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSummaryReports extends ListRecords
{
    protected static string $resource = SummaryReportResource::class;

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
        // Generating a unique ID from the grouped data
        return md5($record->operator . $record->client . $record->game_name . $record->currency);
    }

    /**
     * Prevents the GroupBy from breaking the row count.
     */
    protected function paginateTableQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Contracts\Pagination\Paginator
    {
        return $query->simplePaginate($this->getTableRecordsPerPage());
    }
}
