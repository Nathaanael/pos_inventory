<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\OutboundItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesByCategoryChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Penjualan per Kategori';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
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

        $data = OutboundItem::select(
            'categories.name as category_name',
            DB::raw('SUM(outbound_items.subtotal) as total')
        )
        ->join('outbounds', 'outbound_items.outbound_id', '=', 'outbounds.id')
        ->join('items', 'outbound_items.item_id', '=', 'items.id')
        ->join('categories', 'items.category_id', '=', 'categories.id')
        ->where('outbounds.status', 'selesai')
        ->where('outbounds.created_at', '>=', $startDate)
        ->groupBy('categories.id', 'categories.name')
        ->orderByDesc('total')
        ->get();

        $topCategories = $data->take(5);
        $otherCategoriesTotal = $data->skip(5)->sum('total');

        $labels = $topCategories->pluck('category_name')->toArray();
        $totals = $topCategories->pluck('total')->toArray();

        if ($otherCategoriesTotal > 0) {
            $labels[] = 'Lainnya';
            $totals[] = $otherCategoriesTotal;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $totals,
                    'backgroundColor' => [
                        '#f59e0b', // Amber 500
                        '#3b82f6', // Blue 500
                        '#10b981', // Emerald 500
                        '#8b5cf6', // Violet 500
                        '#ec4899', // Pink 500
                        '#64748b', // Slate 500
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
