<?php

namespace App\Filament\Resources\Inbounds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

class InboundViewForm
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
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name'),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'diterima' => 'Diterima',
                        ]),
                    TextInput::make('total_amount')
                        ->label('Total Nominal')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(','),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull(),
                ]),

            Section::make('Daftar Barang')
                ->schema([
                    Repeater::make('items')
                        ->label('Barang')
                        ->relationship('items')
                        ->schema([
                            Select::make('item_id')
                                ->label('Barang')
                                ->relationship('item', 'name'),
                            TextInput::make('quantity')
                                ->label('Kuantitas')
                                ->numeric(),
                            TextInput::make('buy_price')
                                ->label('Harga Beli')
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
                        ->columns(4)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),
        ];
    }
}
