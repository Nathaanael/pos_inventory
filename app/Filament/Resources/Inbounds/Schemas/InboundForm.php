<?php

namespace App\Filament\Resources\Inbounds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use App\Models\Item;
use App\Models\ItemSupplier;
use Illuminate\Database\Eloquent\Builder;

class InboundForm
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
            TextInput::make('transaction_code')
                ->label('Kode Transaksi')
                ->default(fn() => 'IN-' . date('Y') . '-' . rand(1000, 9999))
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
                ->required()
                ->afterStateUpdated(function ($state, callable $get, callable $set) use ($parse) {
                    $items = $get('items');
                    if (!is_array($items)) return;

                    $total = 0;

                    foreach ($items as $key => $item) {
                        $itemId = $item['item_id'] ?? null;
                        if (!$itemId) continue;

                        $itemSupplier = ItemSupplier::where('item_id', $itemId)
                            ->where('supplier_id', $state)
                            ->first();

                        if (!$itemSupplier) {
                            $set("items.{$key}.item_id", null);
                            $set("items.{$key}.buy_price", 0);
                            $set("items.{$key}.subtotal", 0);
                            continue;
                        }

                        $buyPrice = $itemSupplier->harga_beli;
                        $qty = $parse($item['quantity'] ?? 0);
                        $subtotal = $qty * $parse($buyPrice);

                        $set("items.{$key}.buy_price", $buyPrice);
                        $set("items.{$key}.subtotal", $subtotal);

                        $total += $subtotal;
                    }

                    $set('total_amount', $total);
                }),

            TextInput::make('supplier_invoice_no')
                ->label('No. Invoice Supplier')
                ->maxLength(50),

            TextInput::make('delivery_note_no')
                ->label('No. Surat Jalan')
                ->maxLength(50),

            Hidden::make('status')
                ->default('pending'),

            Hidden::make('total_amount')
                ->default(0),

            Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),

            Hidden::make('created_by')
                ->default(fn() => auth()->id()),

            Repeater::make('items')
                ->label('Barang')
                ->relationship('items')
                ->addActionLabel('Tambah Barang')
                ->deleteAction(
                    fn ($action) => $action->after(function (callable $get, callable $set) use ($updateTotal) {
                        $updateTotal($get, $set);
                    }),
                )
                ->schema([
                    Select::make('item_id')
                        ->label('Barang')
                        ->relationship('item', 'name', function (Builder $query, $get) {
                            $supplierId = $get('../../supplier_id');
                            if ($supplierId) {
                                $query->whereHas('itemSuppliers', function ($q) use ($supplierId) {
                                    $q->where('supplier_id', $supplierId);
                                });
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal, $parse) {
                            if (!$state) {
                                $set('buy_price', 0);
                                $set('subtotal', 0);
                                $updateTotal($get, $set);
                                return;
                            }

                            $item = \App\Models\Item::find($state);
                            if (!$item) return;

                            // Get buy price from item_supplier for the specific supplier
                            $supplierId = $get('../../supplier_id');
                            $itemSupplier = \App\Models\ItemSupplier::where('item_id', $state)
                                ->where('supplier_id', $supplierId)
                                ->first();
                            $buyPrice = $itemSupplier ? $itemSupplier->harga_beli : $item->harga_beli;

                            // Store in variable — $get('buy_price') won't reflect $set() in same callback
                            $set('buy_price', $buyPrice);
                            $qty = $parse($get('quantity') ?: 0);
                            $set('subtotal', $qty * $parse($buyPrice));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('quantity')
                        ->label('Kuantitas')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal, $parse) {
                            $set('subtotal', $parse($state) * $parse($get('buy_price') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('buy_price')
                        ->label('Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->required()
                        ->readOnly(),

                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->readOnly(),
                ])
                ->columnSpanFull()
                ->columns(4)
        ]);
    }
}