<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use App\Models\Supplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Schemas\Components\Section;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Group::make()->columnSpan(['default' => 3, 'lg' => 2])->schema([
                    Section::make('Informasi Produk')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama')
                                ->required()
                                ->autofocus()
                                ->columnSpanFull(),
                            TextInput::make('code')
                                ->label('Kode')
                                ->required()
                                ->default(fn () => static::generateCode())
                                ->unique(ignoreRecord: true)
                                ->readOnly()
                                ->dehydrated()
                                ->maxLength(20),
                            BarcodeInput::make('barcode')
                                ->label('Barcode')
                                ->placeholder('Scan atau ketik barcode...')
                                ->unique(ignoreRecord: true)
                                ->validationMessages([
                                    'unique' => 'barcode ini sudah ada',
                                ]),
                            Textarea::make('description')
                                ->label('Deskripsi')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Harga Jual & Pengaturan Grosir')
                        ->description('Harga jual dan pengaturan grosir berlaku sama untuk semua supplier')
                        ->columns(3)
                        ->schema([
                            TextInput::make('harga_jual_retail')
                                ->label('Harga Jual Retail')
                                ->prefix('Rp')
                                ->required()
                                ->numeric()
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(','),
                            TextInput::make('harga_jual_grosir')
                                ->label('Harga Jual Grosir')
                                ->prefix('Rp')
                                ->required()
                                ->numeric()
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(','),
                            TextInput::make('min_grosir_qty')
                                ->label('Min Qty Grosir')
                                ->required()
                                ->numeric(),
                        ]),

                    Section::make('Supplier & Harga Beli')
                        ->description('Satu barang bisa disuplai oleh banyak supplier dengan harga beli yang berbeda')
                        ->schema([
                            Repeater::make('itemSuppliers')
                                ->label('')
                                ->relationship('itemSuppliers')
                                ->addActionLabel('Tambah Supplier')
                                ->schema([
                                    Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->options(fn () => Supplier::query()->where('is_active', true)->pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(2),
                                    TextInput::make('harga_beli')
                                        ->label('Harga Beli')
                                        ->prefix('Rp')
                                        ->required()
                                        ->numeric()
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->columnSpan(1),
                                    TextInput::make('supplier_sku')
                                        ->label('SKU Supplier')
                                        ->placeholder('Kode internal supplier')
                                        ->columnSpan(1),
                                    TextInput::make('current_stock')
                                        ->label('Stok Supplier')
                                        ->numeric()
                                        ->default(0)
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Dikelola via transaksi')
                                        ->columnSpan(2),
                                ])
                                ->columns(2)
                                ->columnSpanFull()
                                ->minItems(1)
                                ->defaultItems(1)
                                ->reorderable(false),
                        ]),
                ]),

                Group::make()->columnSpan(['default' => 3, 'lg' => 1])->schema([
                    Section::make('Status & Gambar')
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->required(),
                            FileUpload::make('image')
                                ->label('Gambar')
                                ->image(),
                        ]),

                    Section::make('Klasifikasi')
                        ->schema([
                            Select::make('brand_id')
                                ->label('Merk')
                                ->relationship('brand', 'name')
                                ->preload()
                                ->searchable(),
                            Select::make('category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name')
                                ->required(),
                            Select::make('unit_id')
                                ->label('Satuan')
                                ->relationship('unit', 'name')
                                ->required(),
                        ]),

                    Section::make('Stok & Lokasi')
                        ->schema([
                            TextInput::make('current_stock')
                                ->label('Total Stok')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->helperText('Agregat dari semua supplier'),
                            TextInput::make('min_stock')
                                ->label('Min Stok')
                                ->required()
                                ->numeric(),
                            TextInput::make('rack_location')
                                ->label('Lokasi Rak'),
                        ]),
                ]),
            ]),
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