<?php

namespace App\Filament\Resources\Outbounds;

use App\Filament\Resources\Outbounds\Pages\CreateOutbound;
use App\Filament\Resources\Outbounds\Pages\EditOutbound;
use App\Filament\Resources\Outbounds\Pages\ListOutbounds;
use App\Filament\Resources\Outbounds\Schemas\OutboundForm;
use App\Filament\Resources\Outbounds\Tables\OutboundsTable;
use App\Models\Outbound;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OutboundResource extends Resource
{
    protected static ?string $model = Outbound::class;

    protected static ?string $modelLabel = 'Barang Keluar';
    
    protected static ?string $pluralModelLabel = 'Barang Keluar';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';

    protected static \UnitEnum|string|null $navigationGroup = 'Inventaris';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return OutboundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutboundsTable::configure($table);
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
            'index' => ListOutbounds::route('/'),
            'create' => CreateOutbound::route('/create'),
            'edit' => EditOutbound::route('/{record}/edit'),
        ];
    }
}
