<?php

namespace App\Filament\Resources\Items\Widgets;

use App\Models\Item;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ItemStatsWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $totalItems = Item::count();
        $totalAssetValue = Item::where('is_active', true)
            ->get()
            ->sum(fn ($item) => $item->current_stock * $item->harga_beli);
            
        $lowStockItems = Item::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();
            
        $movementsToday = StockMovement::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Jenis Barang', $totalItems)
                ->description('Seluruh item terdaftar')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('Estimasi Nilai Aset', 'Rp ' . number_format($totalAssetValue, 0, ',', '.'))
                ->description('Berdasarkan harga beli gudang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Stok Menipis / Habis', $lowStockItems)
                ->description('Perlu segera restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockItems > 0 ? 'danger' : 'success'),
                
            Stat::make('Pergerakan Hari Ini', $movementsToday)
                ->description('Mutasi stok (Barang Masuk/Keluar/dll)')
                ->descriptionIcon('heroicon-m-arrow-path-rounded-square')
                ->color('info'),
        ];
    }
}
