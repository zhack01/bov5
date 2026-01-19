<?php

namespace App\Filament\Resources\GameReports;

use App\Filament\Resources\GameReports\Pages\CreateGameReport;
use App\Filament\Resources\GameReports\Pages\EditGameReport;
use App\Filament\Resources\GameReports\Pages\ListGameReports;
use App\Filament\Resources\GameReports\Pages\ViewGameReport;
use App\Filament\Resources\GameReports\Schemas\GameReportForm;
use App\Filament\Resources\GameReports\Schemas\GameReportInfolist;
use App\Filament\Resources\GameReports\Tables\GameReportsTable;
use App\Models\GameReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GameReportResource extends Resource
{
    protected static ?string $model = GameReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    
    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return GameReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameReportsTable::configure($table);
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
            'index' => ListGameReports::route('/'),
            // 'create' => CreateGameReport::route('/create'),
            // 'view' => ViewGameReport::route('/{record}'),
            // 'edit' => EditGameReport::route('/{record}/edit'),
        ];
    }
}
