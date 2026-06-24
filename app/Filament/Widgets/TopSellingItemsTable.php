<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopSellingItemsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top 10 Barang Terlaris';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $filter = $this->filters['period'] ?? '30';

        $startDate = match ($filter) {
            'today' => Carbon::today(),
            '7' => Carbon::now()->subDays(6)->startOfDay(),
            '30' => Carbon::now()->subDays(29)->startOfDay(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };

        return $table
            ->query(
                Item::query()
                    ->select(
                        'items.id',
                        'items.name as item_name',
                        DB::raw('SUM(outbound_items.quantity) as total_qty'),
                        DB::raw('SUM(outbound_items.subtotal) as total_revenue'),
                        DB::raw('SUM(outbound_items.profit) as total_profit')
                    )
                    ->join('outbound_items', 'items.id', '=', 'outbound_items.item_id')
                    ->join('outbounds', 'outbound_items.outbound_id', '=', 'outbounds.id')
                    ->where('outbounds.type', 'penjualan')
                    ->where('outbounds.status', 'selesai')
                    ->where('outbounds.created_at', '>=', $startDate)
                    ->groupBy('items.id', 'items.name')
                    ->orderByDesc('total_qty')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Nama Barang')
                    ->wrap(),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Qty')
                    ->numeric()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('IDR')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('total_profit')
                    ->label('Laba')
                    ->money('IDR')
                    ->alignRight(),
            ])
            ->paginated(false);
    }
}
