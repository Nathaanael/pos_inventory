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

            Select::make('supplier_id')
                ->label('Supplier')
                ->relationship('supplier', 'name')
                ->live()
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Disetujui',
                    'ditolak' => 'Ditolak'
                ])
                ->default('pending')
                ->required(),

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
                            $supplierId = $get('../../supplier_id');
                            if ($supplierId) {
                                $query->whereHas('suppliers', function ($q) use ($supplierId) {
                                    $q->where('suppliers.id', $supplierId);
                                });
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($get) => !$get('../../supplier_id'))
                        ->placeholder(fn ($get) => $get('../../supplier_id') ? 'Cari barang...' : 'Pilih supplier dulu')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) use ($updateTotal, $parse) {
                            if ($state) {
                                $item = \App\Models\Item::find($state);
                                $set('current_stock', $item?->current_stock ?? 0);
                                $set('price', $item?->harga_beli ?? 0);
                                $qty = $parse($get('quantity') ?: 0);
                                $set('stock_after', ($item?->current_stock ?? 0) - $qty);
                                $set('subtotal', $qty * $parse($get('price') ?: 0));
                                $updateTotal($get, $set);
                            } else {
                                $set('current_stock', null);
                                $set('stock_after', null);
                                $set('price', null);
                                $set('subtotal', 0);
                                $updateTotal($get, $set);
                            }
                        })
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('current_stock')
                        ->label('Stok')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('quantity')
                        ->label('Qty Retur')
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $get, $set) use ($updateTotal, $parse) {
                            $current = $get('current_stock') ?? 0;
                            $qty = $parse($state);
                            $set('stock_after', $current - $qty);
                            $set('subtotal', $qty * $parse($get('price') ?: 0));
                            $updateTotal($get, $set);
                        })
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
                        ->numeric()
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