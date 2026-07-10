<?php

namespace App\Filament\Resources\ReturnTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ReturnTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $parse = fn($value) => (float) str_replace(',', '', (string) $value);

        $updateTotal = function (callable $get, callable $set) use ($parse) {
            $items = $get('../../items');
            $total = 0;
            if (is_array($items)) {
                foreach ($items as $item) {
                    $total += $parse($item['subtotal'] ?? 0);
                }
            }
            $set('../../total_amount', $total);
        };

        return $schema->components([
            TextInput::make('return_code')
                ->label('Kode Retur')
                ->default(fn() => 'RET-' . date('Y') . '-' . rand(1000, 9999))
                ->required()
                ->readOnly(),

            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->native(false)
                ->displayFormat('d F Y')
                ->required(),

            Select::make('type')
                ->label('Tipe Retur')
                ->options([
                    'supplier' => 'Retur ke Supplier',
                    'customer' => 'Retur dari Customer',
                ])
                ->default('supplier')
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, $get, $set, $livewire) {
                    $items = $get('items');
                    if (is_array($items)) {
                        $parse = fn($value) => (float) str_replace(',', '', (string) $value);
                        $isSupplier = $state === 'supplier';
                        
                        foreach ($items as $key => $itemData) {
                            $current = $itemData['raw_current_stock'] ?? 0;
                            $supplierCurrent = $itemData['raw_supplier_stock'] ?? 0;
                            $qty = $parse($itemData['quantity'] ?? 0);
                            
                            $overallAfter = $current + ($isSupplier ? -$qty : $qty);
                            $supplierAfter = $supplierCurrent + ($isSupplier ? -$qty : $qty);
                            $items[$key]['stock_after'] = "{$overallAfter} (Supplier: {$supplierAfter})";
                        }
                        $set('items', $items);
                        
                        // We also need to re-trigger validation if switching to supplier to ensure stock is sufficient
                        if ($isSupplier) {
                            $livewire->validateOnly('data.items.*.quantity');
                        }
                    }
                }),

            Select::make('supplier_id')
                ->label(fn ($get) => $get('type') === 'supplier' ? 'Supplier (Tujuan Retur)' : 'Alokasi Stok ke Supplier')
                ->relationship('supplier', 'name')
                ->live()
                ->required(),

            TextInput::make('customer_name')
                ->label('Nama Pelanggan')
                ->required(fn ($get) => $get('type') === 'customer')
                ->visible(fn ($get) => $get('type') === 'customer'),

            TextInput::make('reference_code')
                ->label('No. Referensi (Asal)')
                ->maxLength(30),

            Hidden::make('status')
                ->default('pending'),

            TextInput::make('total_amount')
                ->label('Total Harga Retur')
                ->numeric()
                ->prefix('Rp')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->readOnly()
                ->default(0),

            Textarea::make('reason')
                ->label('Alasan Retur')
                ->required()
                ->columnSpanFull(),

            Hidden::make('created_by')
                ->default(fn() => auth()->id()),

            Repeater::make('items')
                ->label('Barang')
                ->addActionLabel('Tambah Barang')
                ->relationship('items')
                ->deleteAction(
                    fn ($action) => $action->after(function (callable $get, callable $set) use ($updateTotal) {
                        $updateTotal($get, $set);
                    }),
                )
                ->schema([
                    Select::make('item_id')
                        ->label('Barang')
                        ->relationship('item', 'name', function ($query, $get) {
                            $type = $get('../../type');
                            $supplierId = $get('../../supplier_id');
                            
                            if ($type === 'supplier') {
                                if ($supplierId) {
                                    $query->whereHas('suppliers', function($q) use ($supplierId) {
                                        $q->where('suppliers.id', $supplierId);
                                    });
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->placeholder(fn ($get) => $get('../../type') === 'supplier' && !$get('../../supplier_id') ? 'Pilih supplier dulu' : 'Cari barang...')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) use ($updateTotal, $parse) {
                            if ($state) {
                                $item = \App\Models\Item::find($state);
                                
                                $overallStock = $item?->current_stock ?? 0;
                                $supplierStock = 0;
                                
                                $isSupplier = $get('../../type') === 'supplier';
                                $price = $isSupplier ? ($item?->harga_beli ?? 0) : ($item?->harga_jual_retail ?? 0);
                                
                                $supplierId = $get('../../supplier_id');
                                if ($item && $supplierId) {
                                    $pivot = $item->suppliers()->where('suppliers.id', $supplierId)->first()?->pivot;
                                    if ($pivot) {
                                        if ($pivot->harga_beli && $isSupplier) {
                                            $price = $pivot->harga_beli;
                                        }
                                        $supplierStock = $pivot->current_stock;
                                    }
                                }
                                
                                $set('raw_current_stock', $overallStock);
                                $set('raw_supplier_stock', $supplierStock);
                                $set('current_stock', "{$overallStock} (Supplier: {$supplierStock})");
                                
                                $set('price', $price);
                                
                                $qty = $parse($get('quantity') ?: 0);
                                $overallAfter = $overallStock + ($isSupplier ? -$qty : $qty);
                                $supplierAfter = $supplierStock + ($isSupplier ? -$qty : $qty);
                                $set('stock_after', "{$overallAfter} (Supplier: {$supplierAfter})");
                                
                                $set('subtotal', $qty * $price);
                                $updateTotal($get, $set);
                            } else {
                                $set('raw_current_stock', null);
                                $set('raw_supplier_stock', null);
                                $set('current_stock', null);
                                $set('stock_after', null);
                                $set('price', null);
                                $set('subtotal', 0);
                                $updateTotal($get, $set);
                            }
                        })
                        ->required()
                        ->columnSpan(2),

                    Hidden::make('raw_current_stock')->dehydrated(false),
                    Hidden::make('raw_supplier_stock')->dehydrated(false),

                    TextInput::make('current_stock')
                        ->label('Stok')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('quantity')
                        ->label('Kuantitas Retur')
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set, \Filament\Forms\Components\TextInput $component, $livewire) use ($updateTotal, $parse) {
                            $livewire->resetValidation($component->getStatePath());
                            
                            $current = $get('raw_current_stock') ?? 0;
                            $supplierCurrent = $get('raw_supplier_stock') ?? 0;
                            
                            $qty = $parse($state);
                            $isSupplier = $get('../../type') === 'supplier';
                            
                            $overallAfter = $current + ($isSupplier ? -$qty : $qty);
                            $supplierAfter = $supplierCurrent + ($isSupplier ? -$qty : $qty);
                            $set('stock_after', "{$overallAfter} (Supplier: {$supplierAfter})");
                            
                            $set('subtotal', $qty * $parse($get('price') ?: 0));
                            $updateTotal($get, $set);
                            
                            if ($isSupplier && $qty > $current) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    $component->getStatePath() => "Stok tidak cukup. Tersedia: {$current}",
                                ]);
                            }
                            if ($isSupplier && $qty > $supplierCurrent) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    $component->getStatePath() => "Stok dari supplier ini tidak cukup. Tersedia: {$supplierCurrent}",
                                ]);
                            }
                        })
                        ->rules([
                            fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('../../type') === 'supplier') {
                                    $current = $get('raw_current_stock') ?? 0;
                                    $supplierCurrent = $get('raw_supplier_stock') ?? 0;
                                    
                                    if ($value > $current) {
                                        $fail("Stok tidak cukup. Tersedia: {$current}");
                                    }
                                    if ($value > $supplierCurrent) {
                                        $fail("Stok dari supplier ini tidak cukup. Tersedia: {$supplierCurrent}");
                                    }
                                }
                            }
                        ])
                        ->required(),

                    TextInput::make('price')
                        ->label('Harga')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) use ($updateTotal, $parse) {
                            $set('subtotal', $parse($state) * $parse($get('quantity') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->readOnly(),

                    TextInput::make('stock_after')
                        ->label('Sisa Stok')
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('condition')
                        ->label('Kondisi')
                        ->options([
                            'baik' => 'Baik',
                            'rusak' => 'Rusak',
                            'cacat' => 'Cacat'
                        ])
                        ->default('rusak')
                        ->required(),
                ])
                ->columnSpanFull()
                ->columns(4)
        ]);
    }
}