<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Outbound;
use App\Models\ReturnItem;
use App\Models\ReturnTransaction;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Memastikan semua aturan perubahan stok di StockService bekerja.
 * Jalankan dengan: php artisan test
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    private Supplier $supplierA;

    private Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->stock = app(StockService::class);
        $this->supplierA = Supplier::create(['code' => 'SUP-A', 'name' => 'Supplier A']);
        $this->supplierB = Supplier::create(['code' => 'SUP-B', 'name' => 'Supplier B']);
    }

    /**
     * Barang dengan stok awal per supplier, mis. ['A' => 3, 'B' => 5] → total 8.
     */
    private function makeItem(array $supplierStock = []): Item
    {
        $category = Category::firstOrCreate(['name' => 'Umum'], ['slug' => 'umum']);
        $unit = Unit::firstOrCreate(['name' => 'pcs']);

        $item = Item::create([
            'code' => 'BRG-' . fake()->unique()->numerify('####'),
            'name' => 'Barang Uji ' . fake()->unique()->word(),
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'harga_beli' => 1000,
            'harga_jual_retail' => 1500,
            'current_stock' => array_sum($supplierStock),
        ]);

        foreach ($supplierStock as $key => $qty) {
            ItemSupplier::create([
                'item_id' => $item->id,
                'supplier_id' => $key === 'A' ? $this->supplierA->id : $this->supplierB->id,
                'harga_beli' => 1000,
                'current_stock' => $qty,
            ]);
        }

        return $item;
    }

    private function supplierStock(Item $item, Supplier $supplier): int
    {
        return (int) ItemSupplier::where('item_id', $item->id)->where('supplier_id', $supplier->id)->value('current_stock');
    }

    private function makeOutbound(Item $item, int $qty): Outbound
    {
        return $this->stock->createOutbound(
            ['transaction_code' => 'OUT-' . fake()->unique()->numerify('#####'), 'date' => now()->toDateString(), 'price_type_applied' => 'retail'],
            [['item_id' => $item->id, 'quantity' => $qty, 'sell_price' => 1500, 'cogs_price' => 1000, 'subtotal' => 1500 * $qty]],
        );
    }

    private function makeInbound(Item $item, int $qty, string $status = 'pending'): Inbound
    {
        $inbound = Inbound::create([
            'transaction_code' => 'IN-' . fake()->unique()->numerify('#####'),
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplierA->id,
            'status' => $status,
            'created_by' => auth()->id(),
        ]);

        InboundItem::create(['inbound_id' => $inbound->id, 'item_id' => $item->id, 'quantity' => $qty, 'buy_price' => 1000]);

        return $inbound->fresh();
    }

    private function makeReturn(Item $item, int $qty, string $type): ReturnTransaction
    {
        $return = ReturnTransaction::create([
            'return_code' => 'RET-' . fake()->unique()->numerify('#####'),
            'date' => now()->toDateString(),
            'type' => $type,
            'reason' => 'Barang cacat',
            'supplier_id' => $this->supplierA->id,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        ReturnItem::create(['return_transaction_id' => $return->id, 'item_id' => $item->id, 'quantity' => $qty, 'price' => 1000]);

        return $return->fresh();
    }

    /* ------------------------------ Barang keluar ------------------------------ */

    public function test_simpan_barang_keluar_langsung_selesai_mengurangi_stok_fifo_dan_mencatat_mutasi(): void
    {
        $item = $this->makeItem(['A' => 3, 'B' => 5]);

        $outbound = $this->makeOutbound($item, 4);

        $this->assertSame(TransactionStatus::COMPLETED, $outbound->status);
        $this->assertEquals(6000, $outbound->total_amount);
        $this->assertSame(4, $item->fresh()->current_stock);
        // FIFO: supplier A (pertama) habis dulu, sisanya dari B.
        $this->assertSame(0, $this->supplierStock($item, $this->supplierA));
        $this->assertSame(4, $this->supplierStock($item, $this->supplierB));

        $movement = StockMovement::sole();
        $this->assertSame('outbound', $movement->type);
        $this->assertSame(-4, (int) $movement->quantity);
        $this->assertSame(8, (int) $movement->stock_before);
        $this->assertSame(4, (int) $movement->stock_after);
    }

    public function test_simpan_barang_keluar_gagal_jika_stok_kurang_dan_tidak_ada_yang_tersimpan(): void
    {
        $item = $this->makeItem(['A' => 2]);

        try {
            $this->makeOutbound($item, 5);
            $this->fail('Seharusnya gagal karena stok tidak cukup.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('tidak cukup', $e->getMessage());
        }

        $this->assertSame(0, Outbound::count());
        $this->assertSame(2, $item->fresh()->current_stock);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_batalkan_barang_keluar_mengembalikan_stok(): void
    {
        $item = $this->makeItem(['A' => 5]);
        $outbound = $this->makeOutbound($item, 2);
        $this->assertSame(3, $item->fresh()->current_stock);

        $this->stock->voidOutbound($outbound, 'salah input');

        $outbound->refresh();
        $this->assertSame(TransactionStatus::VOIDED, $outbound->status);
        $this->assertSame('salah input', $outbound->void_reason);
        $this->assertSame(5, $item->fresh()->current_stock);
        $this->assertSame(5, $this->supplierStock($item, $this->supplierA));
        $this->assertSame('void', StockMovement::latest('id')->first()->type);
    }

    public function test_batalkan_barang_keluar_dua_kali_tidak_menambah_stok_dobel(): void
    {
        $item = $this->makeItem(['A' => 5]);
        $outbound = $this->makeOutbound($item, 2);

        $this->stock->voidOutbound($outbound, 'pertama');
        $this->stock->voidOutbound($outbound, 'kedua');

        $this->assertSame(5, $item->fresh()->current_stock);
        $this->assertSame('pertama', $outbound->fresh()->void_reason);
    }

    /* ------------------------------ Barang masuk ------------------------------ */

    public function test_setujui_barang_masuk_menambah_stok_dan_stok_supplier(): void
    {
        $item = $this->makeItem(['A' => 2]);
        $inbound = $this->makeInbound($item, 10);

        $this->stock->approveInbound($inbound);

        $this->assertSame(TransactionStatus::RECEIVED, $inbound->fresh()->status);
        $this->assertSame(12, $item->fresh()->current_stock);
        $this->assertSame(12, $this->supplierStock($item, $this->supplierA));
        $this->assertSame('inbound', StockMovement::sole()->type);
    }

    public function test_barang_masuk_yang_dibuat_langsung_diterima_menambah_stok_sekali(): void
    {
        $item = $this->makeItem(['A' => 2]);

        $this->makeInbound($item, 3, 'diterima');

        $this->assertSame(5, $item->fresh()->current_stock);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_batalkan_barang_masuk_mengembalikan_stok(): void
    {
        $item = $this->makeItem(['A' => 2]);
        $inbound = $this->makeInbound($item, 10);
        $this->stock->approveInbound($inbound);

        $this->stock->voidInbound($inbound->fresh(), 'barang rusak');

        $this->assertSame(TransactionStatus::VOIDED, $inbound->fresh()->status);
        $this->assertSame(2, $item->fresh()->current_stock);
        $this->assertSame(2, $this->supplierStock($item, $this->supplierA));
        $this->assertSame(['inbound', 'void'], StockMovement::orderBy('id')->pluck('type')->all());
    }

    /* --------------------------------- Retur --------------------------------- */

    public function test_retur_ke_supplier_mengurangi_stok(): void
    {
        $item = $this->makeItem(['A' => 5]);
        $return = $this->makeReturn($item, 2, 'supplier');

        $this->stock->approveReturn($return);

        $this->assertSame(TransactionStatus::COMPLETED, $return->fresh()->status);
        $this->assertSame(3, $item->fresh()->current_stock);
        $this->assertSame(3, $this->supplierStock($item, $this->supplierA));
        $this->assertSame('return_out', StockMovement::sole()->type);
    }

    public function test_retur_dari_customer_menambah_stok(): void
    {
        $item = $this->makeItem(['A' => 5]);
        $return = $this->makeReturn($item, 2, 'customer');

        $this->stock->approveReturn($return);

        $this->assertSame(7, $item->fresh()->current_stock);
        $this->assertSame('return_in', StockMovement::sole()->type);
    }

    public function test_retur_ke_supplier_gagal_jika_stok_kurang(): void
    {
        $item = $this->makeItem(['A' => 1]);
        $return = $this->makeReturn($item, 5, 'supplier');

        $this->expectException(ValidationException::class);

        try {
            $this->stock->approveReturn($return);
        } finally {
            $this->assertSame(1, $item->fresh()->current_stock);
            $this->assertSame(TransactionStatus::PENDING, $return->fresh()->status);
        }
    }

    public function test_tolak_retur_tidak_mengubah_stok(): void
    {
        $item = $this->makeItem(['A' => 5]);
        $return = $this->makeReturn($item, 2, 'supplier');

        $this->stock->rejectReturn($return);

        $this->assertSame(TransactionStatus::REJECTED, $return->fresh()->status);
        $this->assertSame(5, $item->fresh()->current_stock);
        $this->assertSame(0, StockMovement::count());
    }
}
