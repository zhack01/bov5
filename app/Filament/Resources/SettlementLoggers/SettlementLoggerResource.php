<?php

namespace App\Filament\Resources\SettlementLoggers;

use App\Filament\Resources\SettlementLoggers\Pages\CreateSettlementLogger;
use App\Filament\Resources\SettlementLoggers\Pages\EditSettlementLogger;
use App\Filament\Resources\SettlementLoggers\Pages\ListSettlementLoggers;
use App\Filament\Resources\SettlementLoggers\Schemas\SettlementLoggerForm;
use App\Filament\Resources\SettlementLoggers\Tables\SettlementLoggersTable;
use App\Models\SettlementLogger;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettlementLoggerResource extends Resource
{
    protected static ?string $model = SettlementLogger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ReceiptRefund;

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 52;

    // 1. Changes the name in the sidebar menu
    protected static ?string $navigationLabel = 'Settlement';

    // 2. Changes the title at the top of the list page (e.g., "Sub Providers" -> "Providers")
    protected static ?string $pluralModelLabel = 'Settlement';

    // 3. Changes the title for a single record (e.g., "New Sub Provider" -> "New Provider")
    protected static ?string $modelLabel = 'Settlement Form';

    public static function form(Schema $schema): Schema
    {
        return SettlementLoggerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementLoggersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // 1. Check if the logged-in user has the specific custom permission
        if (Filament::auth()->user()->can('Approve:SettlementLogger')) {
            $count = static::getModel()::where('status', 'pending')->count();
            
            return $count > 0 ? (string) $count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; // Makes the badge yellow/amber
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettlementLoggers::route('/'),
            'create' => CreateSettlementLogger::route('/create'),
            'edit' => EditSettlementLogger::route('/{record}/edit'),
        ];
    }
}
