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
                ->default(fn() => 'IN-' . date('Y') . '-' . rand(1000, 9999))
                ->required()
                ->readOnly(),

            DatePicker::make('date')
                ->default(now())
                ->native(false)
                ->displayFormat('d F Y')
                ->required(),

            Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->live()
                ->required(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'diterima' => 'Diterima',
                ])
                ->default('diterima')
                ->required(),

            Hidden::make('total_amount')
                ->default(0),

            Textarea::make('notes')
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
                        ->relationship('item', 'name', function (Builder $query, $get) {
                            $supplierId = $get('../../supplier_id');
                            if ($supplierId) {
                                $query->whereHas('suppliers', fn($q) => $q->where('suppliers.id', $supplierId));
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal, $parse) {
                            $set('buy_price', \App\Models\Item::find($state)?->harga_beli ?? 0);
                            $set('subtotal', $parse($get('quantity') ?: 0) * $parse($get('buy_price') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal, $parse) {
                            $set('subtotal', $parse($state) * $parse($get('buy_price') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('buy_price')
                        ->numeric()
                        ->prefix('Rp')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal, $parse) {
                            $set('subtotal', $parse($state) * $parse($get('quantity') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('subtotal')
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