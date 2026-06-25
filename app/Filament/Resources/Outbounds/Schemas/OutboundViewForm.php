<?php

namespace App\Filament\Resources\Outbounds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

class OutboundViewForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Utama')
                ->columns(2)
                ->schema([
                    TextInput::make('transaction_code')
                        ->label('Kode Transaksi'),
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->native(false)
                        ->displayFormat('d F Y'),
                    Select::make('type')
                        ->label('Tipe')
                        ->options([
                            'penjualan' => 'Penjualan',
                            'penggunaan' => 'Penggunaan',
                            'retur' => 'Retur',
                            'lainnya' => 'Lainnya'
                        ]),
                    TextInput::make('customer_name')
                        ->label('Nama Klien / Customer'),
                    TextInput::make('destination')
                        ->label('Tujuan'),
                    Select::make('price_type_applied')
                        ->label('Tipe Harga')
                        ->options([
                            'retail' => 'Retail',
                            'grosir' => 'Grosir'
                        ]),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'selesai' => 'Selesai',
                            'dibatalkan' => 'Dibatalkan',
                            'voided' => 'Voided'
                        ]),
                    TextInput::make('total_amount')
                        ->label('Total Nominal')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(','),
                    TextInput::make('total_cogs')
                        ->label('Total HPP')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(','),
                    Textarea::make('notes')
                        ->columnSpanFull(),
                ]),

            Section::make('Daftar Barang')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('item_id')
                                ->relationship('item', 'name')
                                ->label('Item'),
                            TextInput::make('quantity')
                                ->numeric()
                                ->label('Qty'),
                            TextInput::make('sell_price')
                                ->label('Harga Jual')
                                ->numeric()
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(','),
                            TextInput::make('cogs_price')
                                ->label('HPP')
                                ->numeric()
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(','),
                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(','),
                        ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),
        ];
    }
}
