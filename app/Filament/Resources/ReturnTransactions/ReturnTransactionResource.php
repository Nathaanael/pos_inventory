<?php

namespace App\Filament\Resources\ReturnTransactions;

use App\Enums\Feature;
use App\Models\StoreSetting;
use App\Filament\Resources\ReturnTransactions\Pages\CreateReturnTransaction;
use App\Filament\Resources\ReturnTransactions\Pages\EditReturnTransaction;
use App\Filament\Resources\ReturnTransactions\Pages\ListReturnTransactions;
use App\Filament\Resources\ReturnTransactions\Schemas\ReturnTransactionForm;
use App\Filament\Resources\ReturnTransactions\Tables\ReturnTransactionsTable;
use App\Models\ReturnTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReturnTransactionResource extends Resource
{
    protected static ?string $model = ReturnTransaction::class;

    /** Menu & halaman hanya ada bila fitur aktif (Pengaturan Toko → Fitur). */
    public static function canAccess(): bool
    {
        return StoreSetting::hasFeature(Feature::Returns) && parent::canAccess();
    }

    protected static ?string $modelLabel = 'Transaksi Retur';
    
    protected static ?string $pluralModelLabel = 'Transaksi Retur';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return ReturnTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnTransactionsTable::configure($table);
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
            'index' => ListReturnTransactions::route('/'),
            'create' => CreateReturnTransaction::route('/create'),
            'edit' => EditReturnTransaction::route('/{record}/edit'),
        ];
    }
}
