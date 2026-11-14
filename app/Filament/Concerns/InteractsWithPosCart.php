<?php

namespace App\Filament\Concerns;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Item;
use App\Models\StoreSetting;
use App\Services\StockService;
use Filament\Notifications\Notification;

/**
 * Logika keranjang POS (pilih barang, keranjang, simpan transaksi barang keluar).
 * Dipakai oleh halaman POS darurat di panel admin dan halaman Kasir di portal kasir.
 * Halaman yang memakai trait ini wajib mengimplementasikan afterPosSaved().
 */
trait InteractsWithPosCart
{
    public string $transaction_code = '';
    public string $date = '';
    public string $customer_name = '';
    public string $destination = '';
    public string $price_type_applied = 'retail';
    public string $notes = '';

    public string $search = '';
    public ?int $selectedCategory = null;

    public array $cartItems = [];

    public function mount(): void
    {
        $this->transaction_code = 'OUT-' . date('Y') . '-' . mt_rand(10000, 99999);
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Get categories for filter pills.
     */
    public function getCategories(): \Illuminate\Support\Collection
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get filtered items for the product grid.
     */
    public function getFilteredItems(): \Illuminate\Database\Eloquent\Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            }))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->with('category', 'unit')
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    /**
     * Add an item to the cart.
     */
    public function addToCart(int $itemId): void
    {
        // Check if already in cart
        foreach ($this->cartItems as $index => $cartItem) {
            if ($cartItem['item_id'] === $itemId) {
                // Increment quantity
                $item = Item::find($itemId);
                $newQty = $cartItem['quantity'] + 1;

                if ($item && $newQty > $item->current_stock) {
                    Notification::make()
                        ->title('Stok tidak cukup')
                        ->body("Tersedia: {$item->current_stock}")
                        ->danger()
                        ->send();
                    return;
                }

                $this->cartItems[$index]['quantity'] = $newQty;
                $this->cartItems[$index]['subtotal'] = $newQty * $cartItem['sell_price'];
                $this->recalculateTotals();
                return;
            }
        }

        $item = Item::find($itemId);
        if (!$item) return;

        if ($item->current_stock <= 0) {
            Notification::make()
                ->title('Stok habis')
                ->body("{$item->name} tidak memiliki stok.")
                ->danger()
                ->send();
            return;
        }

        $sellPrice = $this->price_type_applied === 'grosir'
            ? $item->harga_jual_grosir
            : $item->harga_jual_retail;

        $this->cartItems[] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'image' => $item->image,
            'quantity' => 1,
            'sell_price' => (float) $sellPrice,
            'cogs_price' => (float) $item->calculateFifoCogs(1),
            'subtotal' => (float) $sellPrice,
            'max_stock' => $item->current_stock,
        ];

        $this->recalculateTotals();
    }

    /**
     * Update quantity of a cart item.
     */
    public function updateCartQuantity(int $index, $qty): void
    {
        $qty = (int) $qty;

        if (!isset($this->cartItems[$index])) return;

        if ($qty <= 0) {
            $this->removeFromCart($index);
            return;
        }

        $item = Item::find($this->cartItems[$index]['item_id']);
        if ($item && $qty > $item->current_stock) {
            Notification::make()
                ->title('Stok tidak cukup')
                ->body("Tersedia: {$item->current_stock}")
                ->danger()
                ->send();
            // Reset to max stock
            $qty = $item->current_stock;
        }

        $this->cartItems[$index]['quantity'] = $qty;
        $this->cartItems[$index]['subtotal'] = $qty * $this->cartItems[$index]['sell_price'];
        if ($item) {
            $this->cartItems[$index]['cogs_price'] = (float) $item->calculateFifoCogs($qty);
        }
        $this->recalculateTotals();
    }

    /**
     * Update sell price of a cart item.
     */
    public function updateCartPrice(int $index, $price): void
    {
        $price = (float) $price;
        if (!isset($this->cartItems[$index])) return;

        $this->cartItems[$index]['sell_price'] = $price;
        $this->cartItems[$index]['subtotal'] = $this->cartItems[$index]['quantity'] * $price;
        $this->recalculateTotals();
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
        $this->recalculateTotals();
    }

    /**
     * When price type changes, update all prices in cart.
     */
    public function updatedPriceTypeApplied(): void
    {
        // Harga grosir hanya bila fiturnya aktif (Pengaturan Toko → Fitur).
        if (! StoreSetting::hasFeature(Feature::WholesalePrice)) {
            $this->price_type_applied = 'retail';
        }

        foreach ($this->cartItems as $index => $cartItem) {
            $item = Item::find($cartItem['item_id']);
            if ($item) {
                $newPrice = $this->price_type_applied === 'grosir'
                    ? $item->harga_jual_grosir
                    : $item->harga_jual_retail;

                $this->cartItems[$index]['sell_price'] = (float) $newPrice;
                $this->cartItems[$index]['subtotal'] = $cartItem['quantity'] * (float) $newPrice;
            }
        }
        $this->recalculateTotals();
    }

    /**
     * Recalculate total_amount and total_cogs.
     */
    private function recalculateTotals(): void
    {
        // Totals are computed in the view via getTotalAmount() / getTotalCogs()
    }

    public function getTotalAmount(): float
    {
        return collect($this->cartItems)->sum('subtotal');
    }

    public function getTotalCogs(): float
    {
        return collect($this->cartItems)->sum(fn($item) => $item['cogs_price'] * $item['quantity']);
    }

    /**
     * Clear entire cart.
     */
    public function clearCart(): void
    {
        $this->cartItems = [];
    }

    /**
     * Save the outbound transaction.
     */
    public function save(): void
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->body('Tambahkan minimal 1 barang sebelum menyimpan.')
                ->danger()
                ->send();
            return;
        }

        try {
            app(StockService::class)->createOutbound([
                'transaction_code' => $this->transaction_code,
                'date' => $this->date,
                'customer_name' => $this->customer_name ?: null,
                'destination' => $this->destination ?: null,
                'price_type_applied' => $this->price_type_applied,
                'notes' => $this->notes ?: null,
            ], $this->cartItems);

            Notification::make()
                ->title('Berhasil ditambah')
                ->body('Transaksi barang keluar berhasil disimpan.')
                ->success()
                ->send();

            $this->afterPosSaved();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Dipanggil setelah transaksi tersimpan (mis. redirect ke halaman lain).
     */
    abstract protected function afterPosSaved(): void;

    /**
     * Tujuan tombol "Batal" di layar POS.
     */
    abstract public function getPosCancelUrl(): string;

    /**
     * Tampilkan HPP & margin di ringkasan keranjang? (admin: ya, kasir: tidak)
     */
    public function showCostInfo(): bool
    {
        return true;
    }
}
