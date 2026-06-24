<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Outbound;
use App\Models\OutboundItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAndProfitChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Trend Penjualan & Laba';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $filter = $this->filters['period'] ?? '30';

        $days = match ($filter) {
            'today' => 1,
            '7' => 7,
            '30' => 30,
            'month' => Carbon::now()->day,
            'year' => Carbon::now()->dayOfYear,
            default => 30,
        };
        
        $startDate = match ($filter) {
            'today' => Carbon::today(),
            '7' => Carbon::now()->subDays(6)->startOfDay(),
            '30' => Carbon::now()->subDays(29)->startOfDay(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };
        
        $salesData = Outbound::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
        ->where('type', 'penjualan')
        ->where('status', 'selesai')
        ->where('created_at', '>=', $startDate)
        ->groupByRaw('DATE(created_at)')
        ->pluck('total', 'date');

        $profitData = OutboundItem::select(
            DB::raw('DATE(outbounds.created_at) as date'),
            DB::raw('SUM(outbound_items.profit) as total')
        )
        ->join('outbounds', 'outbound_items.outbound_id', '=', 'outbounds.id')
        ->where('outbounds.type', 'penjualan')
        ->where('outbounds.status', 'selesai')
        ->where('outbounds.created_at', '>=', $startDate)
        ->groupByRaw('DATE(outbounds.created_at)')
        ->pluck('total', 'date');

        $labels = [];
        $sales = [];
        $profits = [];

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - 1 - $i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d M');
            $sales[] = $salesData->get($date, 0);
            $profits[] = $profitData->get($date, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $sales,
                    'borderColor' => '#f59e0b', // Amber (Primary)
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Laba Kotor',
                    'data' => $profits,
                    'borderColor' => '#10b981', // Emerald (Success)
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
