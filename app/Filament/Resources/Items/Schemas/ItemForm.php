<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->required()
                ->default(fn () => static::generateCode())
                ->unique(ignoreRecord: true)
                ->readOnly()
                ->dehydrated()
                ->maxLength(20),
            TextInput::make('barcode'),
            TextInput::make('name')
                ->required()
                ->autofocus(),
            Textarea::make('description')
                ->columnSpanFull(),
            Select::make('category_id')
                ->relationship('category', 'name')
                ->required(),
            Select::make('unit_id')
                ->relationship('unit', 'name')
                ->required(),
            TextInput::make('harga_beli')
                ->prefix('Rp')
                ->required()
                ->numeric()
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(','),
            TextInput::make('harga_jual_retail')
                ->prefix('Rp')
                ->required()
                ->numeric()
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(','),
            TextInput::make('harga_jual_grosir')
                ->prefix('Rp')
                ->required()
                ->numeric()
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(','),
            TextInput::make('min_grosir_qty')
                ->required()
                ->numeric()
                ->default(0),
            TextInput::make('current_stock')
                ->required()
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(),
            TextInput::make('min_stock')
                ->required()
                ->numeric()
                ->default(0),
            TextInput::make('rack_location'),
            FileUpload::make('image')
                ->image(),
            Toggle::make('is_active')
                ->default(true)
                ->required(),
        ]);
    }

    protected static function generateCode(): string
    {
        $lastItem = Item::query()
            ->where('code', 'like', 'ITM-%')
            ->orderByDesc('code')
            ->first();

        if ($lastItem) {
            $lastNumber = (int) str_replace('ITM-', '', $lastItem->code);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'ITM-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}