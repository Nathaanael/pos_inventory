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
        $parse = fn($value) => (float) str_replace(',', '', (string) $value);

        $updateTotal = function (callable $get, callable $set) use ($parse) {
            $items = $get('../../items');
            $totalAmount = 0;
            $totalCogs = 0;
            if (is_array($items)) {
                foreach ($items as $item) {
                    $qty = $parse($item['quantity'] ?? 0);
                    $totalAmount += $parse($item['subtotal'] ?? 0);
                    $totalCogs += $qty * $parse($item['cogs_price'] ?? 0);
                }
            }
            $set('../../total_amount', $totalAmount);
            $set('../../total_cogs', $totalCogs);
        };

        return $schema->components([
            TextInput::make('transaction_code')
                ->default(fn() => 'OUT-' . date('Y') . '-' . rand(1000, 9999))
                ->required()
                ->readOnly(),

            DatePicker::make('date')
                ->default(now())
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
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get) use ($updateTotal) {
                    $items = $get('items');
                    if (is_array($items)) {
                        foreach ($items as $key => $itemData) {
                            if (!empty($itemData['item_id'])) {
                                $item = \App\Models\Item::find($itemData['item_id']);
                                if ($item) {
                                    $newPrice = $state === 'grosir' ? $item->harga_jual_grosir : $item->harga_jual_retail;
                                    $items[$key]['sell_price'] = $newPrice;
                                    $items[$key]['subtotal'] = $newPrice * ($itemData['quantity'] ?: 0);
                                }
                            }
                        }
                        $set('items', $items);
                    }
                    $updateTotal($get, $set);
                }),

            Textarea::make('notes')->columnSpanFull(),

            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan'
                ])
                ->default('selesai')
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->required(),

            Hidden::make('created_by')->default(fn() => auth()->id()),
            Hidden::make('total_amount')->default(0),
            Hidden::make('total_cogs')->default(0),

            Repeater::make('items')
                ->relationship('items')
                ->schema([
                    Select::make('item_id')
                        ->relationship('item', 'name', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) use ($updateTotal) {
                            if (!$state) {
                                $set('cogs_price', null);
                                $set('sell_price', null);
                                $set('subtotal', null);
                                $updateTotal($get, $set);
                                return;
                            }
                            $item = \App\Models\Item::find($state);
                            if (!$item) return;
                            $set('cogs_price', $item->harga_beli);
                            $pt = $livewire->data['price_type_applied'] ?? 'retail';
                            $set('sell_price', $pt == 'grosir' ? $item->harga_jual_grosir : $item->harga_jual_retail);
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, \Filament\Forms\Components\TextInput $component, $livewire) use ($updateTotal) {
                            $livewire->resetValidation($component->getStatePath());
                            $set('subtotal', $state * ($get('sell_price') ?: 0));
                            $updateTotal($get, $set);
                            
                            if ($state) {
                                $item = \App\Models\Item::find($get('item_id'));
                                if ($item && $state > $item->current_stock) {
                                    throw \Illuminate\Validation\ValidationException::withMessages([
                                        $component->getStatePath() => "Stok tidak cukup. Tersedia: {$item->current_stock}",
                                    ]);
                                }
                            }
                        })
                        ->rules([
                            fn (callable $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $item = \App\Models\Item::find($get('item_id'));
                                if ($item && $value > $item->current_stock) {
                                    $fail("Stok tidak cukup. Tersedia: {$item->current_stock}");
                                }
                            },
                        ]),

                    TextInput::make('sell_price')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make('$money($input, \'.\', \',\', 0)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set) use ($updateTotal) {
                            $set('subtotal', $state * ($get('quantity') ?: 0));
                            $updateTotal($get, $set);
                        }),

                    TextInput::make('cogs_price')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make('$money($input, \'.\', \',\', 0)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->readOnly(),
                        
                    TextInput::make('subtotal')
                        ->prefix('Rp')
                        ->mask(\Filament\Support\RawJs::make('$money($input, \'.\', \',\', 0)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->readOnly(),
                ])
                ->columnSpanFull()
                ->columns(5)
        ]);
    }
}