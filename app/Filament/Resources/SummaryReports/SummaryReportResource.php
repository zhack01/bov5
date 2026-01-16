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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SummaryReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SummaryReportsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        // Start fresh
        $query = parent::getEloquentQuery();
        
        // Clear the default "select *" columns and groups
        $query->getQuery()->columns = null;
        $query->getQuery()->groups = null;

        return $query->from('bo_aggreagate.per_player')
            ->leftJoin('mwapiv2_main.operator as o', 'per_player.operator_id', '=', 'o.operator_id')
            ->leftJoin('mwapiv2_main.clients as c', 'per_player.client_id', '=', 'c.client_id')
            ->leftJoin('mwapiv2_main.sub_providers as sp', 'per_player.provider_id', '=', 'sp.sub_provider_id')
            ->leftJoin('mwapiv2_main.providers as pr', 'sp.provider_id', '=', 'pr.provider_id')
            ->leftJoin('mwapiv2_main.games as g', 'per_player.game_id', '=', 'g.game_id')
            ->select([
                // 'per_player.per_player_id',
                'o.client_name as operator',
                'c.client_name as client',
                'pr.provider_name as partner',
                'sp.sub_provider_name as provider',
                'g.game_name',
                'c.default_currency as currency',
            ])
            ->selectRaw("
                MAX(per_player.created_at) as created_at,
                SUM(per_player.bet) as bet,
                SUM(per_player.win) as win,
                SUM(per_player.ggr) as ggr,
                SUM(per_player.total_rounds) as rounds,
                (SUM(per_player.bet) - SUM(per_player.win)) as total_ggr
            ")
            ->groupBy([
                'o.client_name',
                'c.client_name',
                'pr.provider_name',
                'sp.sub_provider_name',
                'g.game_name',
                'c.default_currency',
            ]);

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
