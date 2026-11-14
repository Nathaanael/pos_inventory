<?php

namespace App\Filament\Resources\StockOpnames;

use App\Enums\Feature;
use App\Models\StoreSetting;
use App\Filament\Resources\StockOpnames\Pages\CreateStockOpname;
use App\Filament\Resources\StockOpnames\Pages\EditStockOpname;
use App\Filament\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Filament\Resources\StockOpnames\Schemas\StockOpnameForm;
use App\Filament\Resources\StockOpnames\Tables\StockOpnamesTable;
use App\Models\StockOpname;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    /** Menu & halaman hanya ada bila fitur aktif (Pengaturan Toko → Fitur). */
    public static function canAccess(): bool
    {
        return StoreSetting::hasFeature(Feature::StockOpname) && parent::canAccess();
    }

    protected static ?string $modelLabel = 'Stock Opname';
    
    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return StockOpnameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockOpnamesTable::configure($table);
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
            'index' => ListStockOpnames::route('/'),
            'create' => CreateStockOpname::route('/create'),
            'edit' => EditStockOpname::route('/{record}/edit'),
        ];
    }
}
