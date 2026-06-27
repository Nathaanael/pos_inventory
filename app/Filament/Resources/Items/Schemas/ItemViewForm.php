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
            \Filament\Schemas\Components\Group::make()
                ->schema([
                    Grid::make(3)
                        ->columnSpan('full')
                        ->schema([
                    Section::make('Informasi Utama')
                        ->columns(1)
                        ->schema([
                            TextInput::make('code')->label('Kode'),
                            TextInput::make('name')->label('Nama'),
                            Select::make('category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name'),
                            Select::make('unit_id')
                                ->label('Satuan')
                                ->relationship('unit', 'name'),
                            Toggle::make('is_active')->label('Aktif'),
                        ]),

                    Section::make('Harga & Stok')
                        ->columns(1)
                        ->schema([
                            TextInput::make('harga_beli')
                                ->label('Harga Beli')
                                ->prefix('Rp')
                                ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0))),
                            TextInput::make('harga_jual_retail')
                                ->label('Harga Jual Retail')
                                ->prefix('Rp')
                                ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0))),
                            TextInput::make('harga_jual_grosir')
                                ->label('Harga Jual Grosir')
                                ->prefix('Rp')
                                ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0))),
                            TextInput::make('min_grosir_qty')
                                ->label('Min Qty Grosir')
                                ->numeric(),
                            TextInput::make('current_stock')
                                ->label('Stok Saat Ini')
                                ->numeric(),
                            TextInput::make('min_stock')
                                ->label('Min Stok')
                                ->numeric(),
                            TextInput::make('rack_location')
                                ->label('Lokasi Rak'),
                        ]),

                    Section::make('Informasi Tambahan')
                        ->columns(1)
                        ->schema([
                            Textarea::make('description')
                                ->label('Deskripsi'),
                            FileUpload::make('image')
                                ->label('Gambar')
                                ->image(),
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'name')
                                ->preload(),
                        ]),
                ]),
            ])
        ];
    }
}
