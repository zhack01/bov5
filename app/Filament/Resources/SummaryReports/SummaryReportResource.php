<?php

namespace App\Filament\Resources\SummaryReports;

use App\Filament\Resources\SummaryReports\Pages\CreateSummaryReport;
use App\Filament\Resources\SummaryReports\Pages\EditSummaryReport;
use App\Filament\Resources\SummaryReports\Pages\ListSummaryReports;
use App\Filament\Resources\SummaryReports\Schemas\SummaryReportForm;
use App\Filament\Resources\SummaryReports\Tables\SummaryReportsTable;
use App\Models\SummaryReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SummaryReportResource extends Resource
{
    protected static ?string $model = SummaryReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PresentationChartLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return SummaryReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SummaryReportsTable::configure($table);
    }

    public static function getShieldResourcePermissions(): array
    {
        return [
            'view_any',
            'view',
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSummaryReports::route('/'),
            // 'create' => CreateSummaryReport::route('/create'),
            // 'edit' => EditSummaryReport::route('/{record}/edit'),
        ];
    }
}
