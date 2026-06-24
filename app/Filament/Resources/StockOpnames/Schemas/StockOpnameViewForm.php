<?php

namespace App\Filament\Resources\StockOpnames\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;

class StockOpnameViewForm
{
    public static function schema(): array
    {
        return [
            Section::make('Informasi Sesi')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Sesi'),
                    TextInput::make('session_name')
                        ->label('Nama Sesi'),
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->native(false)
                        ->displayFormat('d F Y'),
                    \Filament\Forms\Components\Placeholder::make('status')
                        ->label('Status')
                        ->content(function ($record) {
                            $label = match ($record->status) {
                                'approved' => 'Selesai',
                                'draft' => 'Draft',
                                default => $record->status,
                            };
                            $color = match ($record->status) {
                                'approved' => 'background-color: #10b981; color: white;',
                                'draft' => 'background-color: #f59e0b; color: white;',
                                default => 'background-color: #6b7280; color: white;',
                            };
                            return new \Illuminate\Support\HtmlString(
                                "<span style=\"{$color} padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;\">{$label}</span>"
                            );
                        }),
                    Textarea::make('notes')
                        ->columnSpanFull(),
                ]),

            Section::make('Detail Opname')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('item_id')
                                ->relationship('item', 'name'),
                            TextInput::make('system_stock')
                                ->label('Sistem')
                                ->numeric(),
                            TextInput::make('physical_stock')
                                ->label('Fisik')
                                ->numeric(),
                            TextInput::make('difference')
                                ->label('Selisih')
                                ->numeric(),
                            Select::make('status')
                                ->options([
                                    'cocok' => 'Cocok',
                                    'kurang' => 'Kurang',
                                    'lebih' => 'Lebih',
                                ]),
                            TextInput::make('notes')
                                ->label('Catatan'),
                        ])
                        ->columns(6)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ]),
        ];
    }
}
