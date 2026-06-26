<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ItemStockExport extends BaseExport implements FromArray, WithTitle
{
    protected function getTitle(): string
    {
        return 'LAPORAN STOK BARANG';
    }

    public function title(): string
    {
        return 'Stok Barang';
    }

    protected function getHeaderRow(): array
    {
        return [
            'No',
            'Kode',
            'Nama Barang',
            'Kategori',
            'Brand',
            'Satuan',
            'Supplier',
            'Harga Beli',
            'Harga Jual Retail',
            'Harga Jual Grosir',
            'Min. Qty Grosir',
            'Stok Saat Ini',
            'Min. Stok',
            'Lokasi Rak',
            'Status',
        ];
    }

    protected function getDataRows(): array
    {
        $items = Item::with(['category', 'brand', 'unit', 'supplier'])
            ->orderBy('name')
            ->get();

        $rows = [];
        $no = 1;
        foreach ($items as $item) {
            $rows[] = [
                $no++,
                $item->code,
                $item->name,
                $item->category?->name ?? '-',
                $item->brand?->name ?? '-',
                $item->unit?->name ?? '-',
                $item->supplier?->name ?? '-',
                $item->harga_beli,
                $item->harga_jual_retail,
                $item->harga_jual_grosir,
                $item->min_grosir_qty,
                $item->current_stock,
                $item->min_stock,
                $item->rack_location ?? '-',
                $item->is_active ? 'Aktif' : 'Nonaktif',
            ];
        }

        return $rows;
    }

    protected function getCurrencyColumns(): array
    {
        return ['H', 'I', 'J']; // Harga Beli, Harga Jual Retail, Harga Jual Grosir
    }

    protected function getColumnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 12,  // Kode
            'C' => 30,  // Nama Barang
            'D' => 15,  // Kategori
            'E' => 15,  // Brand
            'F' => 10,  // Satuan
            'G' => 20,  // Supplier
            'H' => 15,  // Harga Beli
            'I' => 16,  // Harga Jual Retail
            'J' => 16,  // Harga Jual Grosir
            'K' => 14,  // Min Qty Grosir
            'L' => 12,  // Stok Saat Ini
            'M' => 10,  // Min Stok
            'N' => 12,  // Lokasi Rak
            'O' => 10,  // Status
        ];
    }

    public function array(): array
    {
        return $this->buildSheet();
    }
}
