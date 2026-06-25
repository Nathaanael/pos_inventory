<?php

namespace App\Filament\Resources\Outbounds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;

class OutboundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('transaction_code')
                ->default(fn() => 'OUT-' . date('Y') . '-' . rand(1000, 9999))
                ->required()
                ->readOnly(),

            DatePicker::make('date')
                ->default(now())
                ->required(),

            Select::make('type')
                ->options([
                    'penjualan' => 'Penjualan',
                    'penggunaan' => 'Penggunaan',
                    'retur' => 'Retur',
                    'lainnya' => 'Lainnya'
                ])
                ->default('penjualan')
                ->required(),

            TextInput::make('customer_name'),
            TextInput::make('destination'),

            Select::make('price_type_applied')
                ->options([
                    'retail' => 'Retail',
                    'grosir' => 'Grosir'
                ])
                ->default('retail')
                ->required()
                ->live(),

            Textarea::make('notes')->columnSpanFull(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan'
                ])
                ->default('selesai')
                ->required(),

            Hidden::make('created_by')->default(fn() => auth()->id()),

            Repeater::make('items')
                ->relationship('items')
                ->schema([
                    Select::make('item_id')
                        ->relationship('item', 'name')
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                            $item = \App\Models\Item::find($state);
                            if (!$item) return;
                            $set('cogs_price', $item->harga_beli);
                            $pt = $livewire->data['price_type_applied'] ?? 'retail';
                            $set('sell_price', $pt == 'grosir' ? $item->harga_jual_grosir : $item->harga_jual_retail);
                        }),

                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $get, callable $set) => $set('subtotal', $state * ($get('sell_price') ?: 0)))
                        ->rules([
                            fn (callable $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $item = \App\Models\Item::find($get('item_id'));
                                if ($item && $value > $item->current_stock) {
                                    $fail("Stok tidak cukup. Tersedia: {$item->current_stock}");
                                }
                            },
                        ]),

                    TextInput::make('sell_price')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $get, callable $set) => $set('subtotal', $state * ($get('quantity') ?: 0))),

                    TextInput::make('cogs_price')->numeric()->readOnly(),
                    TextInput::make('subtotal')->numeric()->readOnly(),
                ])
                ->columnSpanFull()
                ->columns(5)
        ]);
    }
}