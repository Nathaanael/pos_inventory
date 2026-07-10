<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\Inbound;
use App\Models\Item;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Penjualan Hari Ini
        $salesToday = Outbound::whereDate('created_at', $today)
            ->where('status', 'selesai')
            ->sum('total_amount');
            
        $salesYesterday = Outbound::whereDate('created_at', $yesterday)
            ->where('status', 'selesai')
            ->sum('total_amount');
            
        // Pengurang Penjualan (Retur dari Customer)
        $returnSalesToday = \App\Models\ReturnTransaction::whereDate('created_at', $today)
            ->where('status', 'selesai')
            ->where('type', '!=', 'supplier')
            ->sum('total_amount');
            
        $returnSalesYesterday = \App\Models\ReturnTransaction::whereDate('created_at', $yesterday)
            ->where('status', 'selesai')
            ->where('type', '!=', 'supplier')
            ->sum('total_amount');

        $salesToday -= $returnSalesToday;
        $salesYesterday -= $returnSalesYesterday;
            
        $salesTrend = $salesYesterday > 0 ? (($salesToday - $salesYesterday) / $salesYesterday) * 100 : ($salesToday > 0 ? 100 : 0);
        $salesIcon = $salesTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $salesColor = $salesTrend >= 0 ? 'success' : 'danger';

        // Laba Kotor Hari Ini
        $profitToday = OutboundItem::whereHas('outbound', function($q) use ($today) {
            $q->whereDate('created_at', $today)
              ->where('status', 'selesai');
        })->sum('profit');
        
        $profitYesterday = OutboundItem::whereHas('outbound', function($q) use ($yesterday) {
            $q->whereDate('created_at', $yesterday)
              ->where('status', 'selesai');
        })->sum('profit');

        // Pengurang Laba (Profit yang batal karena diretur)
        $returnCustomerItemsToday = \App\Models\ReturnItem::whereHas('returnTransaction', function($q) use ($today) {
            $q->whereDate('created_at', $today)
              ->where('status', 'selesai')
              ->where('type', '!=', 'supplier');
        })->with('item')->get();

        $profitReturnToday = $returnCustomerItemsToday->sum(function ($returnItem) {
            $cogs = $returnItem->item->harga_beli ?? 0;
            return ($returnItem->price - $cogs) * $returnItem->quantity;
        });

        $returnCustomerItemsYesterday = \App\Models\ReturnItem::whereHas('returnTransaction', function($q) use ($yesterday) {
            $q->whereDate('created_at', $yesterday)
              ->where('status', 'selesai')
              ->where('type', '!=', 'supplier');
        })->with('item')->get();

        $profitReturnYesterday = $returnCustomerItemsYesterday->sum(function ($returnItem) {
            $cogs = $returnItem->item->harga_beli ?? 0;
            return ($returnItem->price - $cogs) * $returnItem->quantity;
        });

        $profitToday -= $profitReturnToday;
        $profitYesterday -= $profitReturnYesterday;

        $profitTrend = $profitYesterday > 0 ? (($profitToday - $profitYesterday) / $profitYesterday) * 100 : ($profitToday > 0 ? 100 : 0);
        $profitIcon = $profitTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $profitColor = $profitTrend >= 0 ? 'success' : 'danger';

        // Modal Masuk Hari Ini
        $inboundToday = Inbound::whereDate('created_at', $today)
            ->where('status', 'diterima')
            ->sum('total_amount');

        // Alert Stok Menipis
        $lowStockCount = Item::whereColumn('current_stock', '<=', 'min_stock')->where('min_stock', '>', 0)->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($salesToday, 0, ',', '.'))
                ->description(abs(round($salesTrend, 1)) . '% dari kemarin')
                ->descriptionIcon($salesIcon)
                ->color($salesColor)
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Laba Kotor Hari Ini', 'Rp ' . number_format($profitToday, 0, ',', '.'))
                ->description(abs(round($profitTrend, 1)) . '% dari kemarin')
                ->descriptionIcon($profitIcon)
                ->color($profitColor)
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Barang Masuk Hari Ini', 'Rp ' . number_format($inboundToday, 0, ',', '.'))
                ->description('Barang diterima hari ini')
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Stok Menipis', $lowStockCount . ' Barang')
                ->description($lowStockCount > 0 ? 'Perlu restock segera' : 'Stok aman')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
