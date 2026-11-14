<?php

namespace App\Filament\Kasir\Pages;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Item;
use App\Models\Outbound;
use App\Models\StoreSetting;
use App\Services\StockService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Layar kasir.
 *
 * Keranjang dikelola sepenuhnya di browser (Alpine.js) supaya klik barang
 * langsung masuk tanpa menunggu server. Server hanya dipanggil satu kali
 * saat transaksi disimpan (checkout), dan di sana harga & stok dihitung ulang
 * dari database, jadi data dari browser tidak dipercaya begitu saja.
 */
class Pos extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $title = 'Kasir';

    protected static ?string $slug = 'pos';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.kasir.pages.pos';

    protected Width|string|null $maxContentWidth = Width::Full;

    public const PAYMENT_METHODS = [
        'tunai' => 'Tunai',
        'debit' => 'Kartu Debit',
        'qris' => 'QRIS / E-Wallet',
    ];

    public function getHeading(): string
    {
        return '';
    }

    /**
     * Seluruh barang aktif, dikirim sekali ke browser.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCatalog(): array
    {
        $showImages = StoreSetting::hasFeature(Feature::ItemImage);

        return Item::query()
            ->where('is_active', true)
            ->with('unit:id,name')
            ->orderBy('name')
            ->get(['id', 'code', 'barcode', 'name', 'category_id', 'unit_id', 'harga_jual_retail', 'harga_jual_grosir', 'current_stock', 'image'])
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'barcode' => $item->barcode,
                'name' => $item->name,
                'category_id' => $item->category_id,
                'unit' => $item->unit?->name,
                'retail' => (float) $item->harga_jual_retail,
                'grosir' => (float) $item->harga_jual_grosir,
                'stock' => (int) $item->current_stock,
                'image' => $showImages && $item->image ? Storage::url($item->image) : null,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    public function getCategoryList(): array
    {
        return Category::orderBy('name')->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /**
     * Transaksi terakhir kasir ini hari ini (strip di bawah daftar barang).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentOrders(): array
    {
        return Outbound::query()
            ->where('created_by', auth()->id())
            ->whereDate('created_at', today())
            ->withCount('items')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'transaction_code', 'customer_name', 'status', 'total_amount', 'created_at'])
            ->map(fn (Outbound $o) => [
                'id' => $o->id,
                'no' => Str::afterLast($o->transaction_code, '-'),
                'customer' => $o->customer_name ?: 'Pelanggan umum',
                'items' => $o->items_count,
                'total' => (float) $o->total_amount,
                'time' => $o->created_at->timezone('Asia/Jakarta')->format('H:i'),
                'status' => $o->status->value,
                'print_url' => $o->status->value === 'selesai' ? route('outbound.print', $o) : null,
            ])
            ->all();
    }

    /**
     * Simpan transaksi. Dipanggil dari browser: $wire.checkout(...)
     *
     * @param  array<int, array{id:int, qty:int}>  $lines
     * @return array{ok:bool, message:string, code?:string, catalog?:array}
     */
    public function checkout(array $lines, string $priceType, string $paymentMethod, ?float $paidAmount = null, ?string $customerName = null, ?string $notes = null, ?string $saleId = null): array
    {
        // Pengaman transaksi dobel: setiap penjualan di browser punya $saleId unik.
        // Kalau id yang sama dikirim lagi (klik ganda, koneksi ulang, tab ganda), tolak.
        $lockKey = $saleId ? 'kasir-checkout:' . auth()->id() . ':' . mb_substr($saleId, 0, 64) : null;
        if ($lockKey && ! Cache::add($lockKey, 'processing', now()->addMinutes(30))) {
            return ['ok' => false, 'duplicate' => true, 'message' => 'Transaksi ini sudah dikirim. Cek Riwayat Transaksi sebelum menyimpan ulang.'];
        }

        $result = $this->processCheckout($lines, $priceType, $paymentMethod, $paidAmount, $customerName, $notes);

        // Gagal validasi (mis. stok kurang): izinkan kasir memperbaiki lalu kirim ulang id yang sama.
        if ($lockKey && ! $result['ok']) {
            Cache::forget($lockKey);
        }

        return $result;
    }

    /**
     * @return array{ok:bool, message:string, code?:string, catalog?:array}
     */
    private function processCheckout(array $lines, string $priceType, string $paymentMethod, ?float $paidAmount, ?string $customerName, ?string $notes): array
    {
        // Harga grosir hanya bila fiturnya aktif (Pengaturan Toko → Fitur).
        $priceType = $priceType === 'grosir' && StoreSetting::hasFeature(Feature::WholesalePrice) ? 'grosir' : 'retail';

        if (! array_key_exists($paymentMethod, self::PAYMENT_METHODS)) {
            return ['ok' => false, 'message' => 'Pilih metode pembayaran.'];
        }

        // Gabungkan baris dengan barang yang sama & buang jumlah tidak valid.
        $quantities = [];
        foreach ($lines as $line) {
            $id = (int) ($line['id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);
            if ($id > 0 && $qty > 0) {
                $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
            }
        }

        if (! $quantities) {
            return ['ok' => false, 'message' => 'Keranjang masih kosong.'];
        }

        $items = Item::query()->where('is_active', true)->whereIn('id', array_keys($quantities))->get()->keyBy('id');

        $serverLines = [];
        foreach ($quantities as $id => $qty) {
            $item = $items->get($id);

            if (! $item) {
                return ['ok' => false, 'message' => 'Ada barang yang sudah tidak aktif. Muat ulang halaman.'];
            }
            if ($item->current_stock < $qty) {
                return ['ok' => false, 'message' => "Stok {$item->name} tidak cukup. Tersedia: {$item->current_stock}."];
            }

            $price = (float) $this->priceFor($item, $priceType);

            $serverLines[] = [
                'item_id' => $item->id,
                'quantity' => $qty,
                'sell_price' => $price,
                'cogs_price' => (float) $item->calculateFifoCogs($qty),
                'subtotal' => $price * $qty,
            ];
        }

        $total = collect($serverLines)->sum('subtotal');

        if ($paymentMethod === 'tunai') {
            if ($paidAmount === null || $paidAmount < $total) {
                return ['ok' => false, 'message' => 'Uang diterima kurang dari total belanja.'];
            }
        } else {
            $paidAmount = $total;
        }

        try {
            $outbound = app(StockService::class)->createOutbound([
                'date' => now()->toDateString(),
                'customer_name' => filled($customerName) ? mb_substr(trim($customerName), 0, 100) : null,
                'price_type_applied' => $priceType,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paidAmount,
                'notes' => filled($notes) ? mb_substr(trim($notes), 0, 500) : null,
            ], $serverLines);
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()];
        }

        return [
            'ok' => true,
            'message' => "Transaksi {$outbound->transaction_code} tersimpan.",
            'code' => $outbound->transaction_code,
            'catalog' => $this->getCatalog(),
            'recent' => $this->getRecentOrders(),
        ];
    }

    /**
     * Harga grosir dipakai bila diisi; jika kosong (0) pakai harga eceran.
     */
    private function priceFor(Item $item, string $priceType): float
    {
        if ($priceType === 'grosir' && (float) $item->harga_jual_grosir > 0) {
            return (float) $item->harga_jual_grosir;
        }

        return (float) $item->harga_jual_retail;
    }
}
