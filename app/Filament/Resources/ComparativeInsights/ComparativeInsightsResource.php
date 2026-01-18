<?php

namespace App\Filament\Resources\ComparativeInsights;

use App\Filament\Resources\ComparativeInsights\Pages\CreateComparativeInsights;
use App\Filament\Resources\ComparativeInsights\Pages\EditComparativeInsights;
use App\Filament\Resources\ComparativeInsights\Pages\ListComparativeInsights;
use App\Filament\Resources\ComparativeInsights\Schemas\ComparativeInsightsForm;
use App\Filament\Resources\ComparativeInsights\Tables\ComparativeInsightsTable;
use App\Models\ComparativeInsights;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComparativeInsightsResource extends Resource
{
    protected static ?string $model = ComparativeInsights::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ComparativeInsightsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComparativeInsightsTable::configure($table);
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
            'index' => ListComparativeInsights::route('/'),
            'create' => CreateComparativeInsights::route('/create'),
            'edit' => EditComparativeInsights::route('/{record}/edit'),
        ];
    }
}
