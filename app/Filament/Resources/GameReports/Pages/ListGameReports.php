<?php

namespace App\Filament\Resources\GameReports\Pages;

use App\Filament\Resources\GameReports\GameReportResource;
use App\Traits\HasTransactionDetails;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListGameReports extends ListRecords
{

    use HasTransactionDetails;
    
    protected static string $resource = GameReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
    
    // public static function getRecordKey(Model $record): string
    // {
    //     // Combine your grouped columns into a unique string
    //     return md5($record->operator . $record->client . $record->game_name . $record->currency);
    // }

    // protected function paginateTableQuery(Builder $query): Paginator
    // {
    //     // simplePaginate is required for GroupBy reports because it skips 
    //     // the COUNT(*) query that usually returns 0 or 1 on grouped data.
    //     return $query->simplePaginate($this->getTableRecordsPerPage());
    // }
}
