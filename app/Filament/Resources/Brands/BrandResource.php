<?php

namespace App\Filament\Resources\Brands;

use App\Enums\Feature;
use App\Models\StoreSetting;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\Schemas\BrandForm;
use App\Filament\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    /** Menu & halaman hanya ada bila fitur aktif (Pengaturan Toko → Fitur). */
    public static function canAccess(): bool
    {
        return StoreSetting::hasFeature(Feature::Brand) && parent::canAccess();
    }

    protected static ?string $modelLabel = 'Merk';
    
    protected static ?string $pluralModelLabel = 'Merk';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Data';

    // protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
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
            'index' => ListBrands::route('/'),
        ];
    }
}
