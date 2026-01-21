<?php

namespace App\Filament\Resources\TransactionMonitorings;

use App\Filament\Resources\TransactionMonitorings\Pages\ListTransactionMonitorings;
use App\Filament\Resources\TransactionMonitorings\Schemas\TransactionMonitoringForm;
use App\Filament\Resources\TransactionMonitorings\Tables\TransactionMonitoringsTable;
use App\Models\GameReport as TransactionMonitoring;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionMonitoringResource extends Resource
{
    protected static ?string $model = TransactionMonitoring::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ComputerDesktop;

    protected static ?string $recordTitleAttribute = 'round_id';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 51;

    // 1. Changes the name in the sidebar menu
    protected static ?string $navigationLabel = 'Transactions Monitoring';

    // 2. Changes the title at the top of the list page (e.g., "Sub Providers" -> "Providers")
    protected static ?string $pluralModelLabel = 'Transactions Monitoring';

    // 3. Changes the title for a single record (e.g., "New Sub Provider" -> "New Provider")
    protected static ?string $modelLabel = 'Transactions Monitoring';

    public static function form(Schema $schema): Schema
    {
        return TransactionMonitoringForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionMonitoringsTable::configure($table);
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
            'index' => ListTransactionMonitorings::route('/'),
            // 'create' => CreateTransactionMonitoring::route('/create'),
            // 'edit' => EditTransactionMonitoring::route('/{record}/edit'),
        ];
    }
}
