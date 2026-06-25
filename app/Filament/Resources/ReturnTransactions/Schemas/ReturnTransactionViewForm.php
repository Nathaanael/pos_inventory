<?php

namespace App\Filament\Resources\ReturnTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;

class ReturnTransactionViewForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Retur')
                ->columns(2)
                ->schema([
                    TextInput::make('return_code')
                        ->label('Kode Retur'),
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->native(false)
                        ->displayFormat('d F Y'),
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name'),
                    \Filament\Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(function ($record) {
                            $statusVal = $record->status instanceof \App\Enums\TransactionStatus ? $record->status->value : $record->status;
                            $label = match ($statusVal) {
                                'selesai' => 'Disetujui',
                                'pending' => 'Pending',
                                'ditolak' => 'Ditolak',
                                default => ucfirst((string) $statusVal),
                            };
                            $color = match ($statusVal) {
                                'selesai' => 'background-color: #10b981; color: white;',
                                'pending' => 'background-color: #f59e0b; color: white;',
                                'ditolak' => 'background-color: #ef4444; color: white;',
                                default => 'background-color: #6b7280; color: white;',
                            };
                            return new \Illuminate\Support\HtmlString(
                                "<span style=\"{$color} padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;\">{$label}</span>"
                            );
                        }),
                    TextInput::make('total_amount')
                        ->label('Total Harga Retur')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(','),
                    Textarea::make('reason')
                        ->label('Alasan Retur')
                        ->columnSpanFull(),
                ]),

            Section::make('Daftar Barang Retur')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('item_id')
                                ->label('Barang')
                                ->relationship('item', 'name'),
                            TextInput::make('quantity')
                                ->label('Kuantitas')
                                ->numeric(),
                            TextInput::make('price')
                                ->label('Harga')
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
                            Select::make('condition')
                                ->label('Kondisi')
                                ->options([
                                    'baik' => 'Baik',
                                    'rusak' => 'Rusak',
                                    'cacat' => 'Cacat',
                                ]),
                        ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),
        ];
    }
}
