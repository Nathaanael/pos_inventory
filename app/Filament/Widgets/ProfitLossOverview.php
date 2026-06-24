<?php

namespace App\Filament\Widgets;

use App\Models\Inbound;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\ReturnTransaction;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class ProfitLossOverview extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.profit-loss-overview';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
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

        // Revenue
        $revenue = Outbound::where('type', 'penjualan')
            ->where('status', 'selesai')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');

        // COGS (Total Modal Barang Terjual)
        $cogs = OutboundItem::whereHas('outbound', function($q) use ($startDate) {
            $q->where('type', 'penjualan')
              ->where('status', 'selesai')
              ->where('created_at', '>=', $startDate);
        })->sum(\Illuminate\Support\Facades\DB::raw('cogs_price * quantity'));

        // Gross Profit (Laba Kotor)
        $grossProfit = $revenue - $cogs;

        // Returns (Retur)
        $returns = ReturnTransaction::where('status', 'selesai')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');

        // Net Profit (Laba Bersih - Simply Gross Profit - Returns for now)
        $netProfit = $grossProfit - $returns;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'returns' => $returns,
            'netProfit' => $netProfit,
            'filterLabel' => match ($filter) {
                'today' => 'Hari Ini',
                '7' => '7 Hari Terakhir',
                '30' => '30 Hari Terakhir',
                'month' => 'Bulan Ini',
                'year' => 'Tahun Ini',
                default => '30 Hari Terakhir',
            }
        ];
    }
}
