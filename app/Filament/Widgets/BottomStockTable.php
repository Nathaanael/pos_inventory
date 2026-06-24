<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BottomStockTable extends BaseWidget
{
    protected static ?string $heading = 'Barang Stok Menipis';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->whereColumn('current_stock', '<=', 'min_stock')
                    ->where('min_stock', '>', 0)
                    ->orderByRaw('(min_stock - current_stock) DESC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deficit')
                    ->label('Kurang')
                    ->state(fn (Item $record): int => max(0, $record->min_stock - $record->current_stock))
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
