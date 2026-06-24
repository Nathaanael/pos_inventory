<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ItemViewForm
{
    public static function schema(): array
    {
        return [
            Grid::make(3)
                ->columnSpan('full')
                ->schema([
                    Section::make('Informasi Utama')
                        ->columns(1)
                        ->schema([
                            TextInput::make('code'),
                            TextInput::make('name'),
                            Select::make('category_id')
                                ->relationship('category', 'name'),
                            Select::make('unit_id')
                                ->relationship('unit', 'name'),
                            Toggle::make('is_active'),
                        ]),

                    Section::make('Harga & Stok')
                        ->columns(1)
                        ->schema([
                            TextInput::make('harga_beli')
                                ->prefix('Rp')
                                ->numeric(),
                            TextInput::make('harga_jual_retail')
                                ->prefix('Rp')
                                ->numeric(),
                            TextInput::make('harga_jual_grosir')
                                ->prefix('Rp')
                                ->numeric(),
                            TextInput::make('min_grosir_qty')
                                ->numeric(),
                            TextInput::make('current_stock')
                                ->numeric(),
                            TextInput::make('min_stock')
                                ->numeric(),
                            TextInput::make('rack_location'),
                        ]),

                    Section::make('Informasi Tambahan')
                        ->columns(1)
                        ->schema([
                            Textarea::make('description'),
                            FileUpload::make('image')
                                ->image(),
                        ]),
                ])
        ];
    }
}
