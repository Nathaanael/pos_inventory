<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Filament\Kasir\Pages\Pos;
use App\Models\Category;
use App\Models\Item;
use App\Models\Outbound;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KasirPosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('kasir'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR]));
    }

    private function makeItem(int $stock, float $retail = 10000, float $grosir = 8000): Item
    {
        return Item::create([
            'code' => 'BRG-' . fake()->unique()->numerify('####'),
            'name' => 'Barang ' . fake()->unique()->word(),
            'category_id' => Category::firstOrCreate(['name' => 'Umum'], ['slug' => 'umum'])->id,
            'unit_id' => Unit::firstOrCreate(['name' => 'pcs'])->id,
            'harga_beli' => 5000,
            'harga_jual_retail' => $retail,
            'harga_jual_grosir' => $grosir,
            'current_stock' => $stock,
        ]);
    }

    public function test_halaman_kasir_tampil_dengan_katalog(): void
    {
        $item = $this->makeItem(5);

        Livewire::test(Pos::class)
            ->assertOk()
            ->assertSee('Pesanan Baru')
            ->assertSee($item->name);
    }

    public function test_checkout_tunai_menyimpan_transaksi_dengan_harga_dari_server(): void
    {
        $a = $this->makeItem(10, 10000);
        $b = $this->makeItem(10, 2500);

        $result = Livewire::test(Pos::class)
            ->call('checkout', [['id' => $a->id, 'qty' => 2], ['id' => $b->id, 'qty' => 1], ['id' => $a->id, 'qty' => 1]], 'retail', 'tunai', 50000, 'Budi')
            ->effects['returns'][0];

        $this->assertTrue($result['ok'], $result['message']);

        $outbound = Outbound::with('items')->sole();
        $this->assertSame(TransactionStatus::COMPLETED, $outbound->status);
        $this->assertEquals(32500, $outbound->total_amount);   // 3 × 10.000 + 1 × 2.500
        $this->assertSame('tunai', $outbound->payment_method);
        $this->assertEquals(50000, $outbound->paid_amount);
        $this->assertSame('Budi', $outbound->customer_name);
        $this->assertSame(auth()->id(), $outbound->created_by);
        $this->assertMatchesRegularExpression('/^OUT-\d{4}-\d{4}-\d{4}$/', $outbound->transaction_code);
        $this->assertCount(2, $outbound->items); // baris barang yang sama digabung
        // Stok langsung berkurang, tanpa persetujuan admin.
        $this->assertSame(7, $a->fresh()->current_stock);
        $this->assertSame(9, $b->fresh()->current_stock);
    }

    public function test_checkout_ditolak_jika_stok_tidak_cukup(): void
    {
        $item = $this->makeItem(2);

        $result = Livewire::test(Pos::class)
            ->call('checkout', [['id' => $item->id, 'qty' => 3]], 'retail', 'debit')
            ->effects['returns'][0];

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('tidak cukup', $result['message']);
        $this->assertSame(0, Outbound::count());
    }

    public function test_checkout_tunai_ditolak_jika_uang_kurang(): void
    {
        $item = $this->makeItem(5, 10000);

        $result = Livewire::test(Pos::class)
            ->call('checkout', [['id' => $item->id, 'qty' => 2]], 'retail', 'tunai', 15000)
            ->effects['returns'][0];

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Outbound::count());
    }

    public function test_harga_grosir_dipakai_dan_kembali_ke_eceran_jika_grosir_kosong(): void
    {
        $withGrosir = $this->makeItem(10, 10000, 8000);
        $withoutGrosir = $this->makeItem(10, 4000, 0);

        $result = Livewire::test(Pos::class)
            ->call('checkout', [['id' => $withGrosir->id, 'qty' => 1], ['id' => $withoutGrosir->id, 'qty' => 1]], 'grosir', 'qris')
            ->effects['returns'][0];

        $this->assertTrue($result['ok'], $result['message']);
        $outbound = Outbound::sole();
        $this->assertEquals(12000, $outbound->total_amount);
        $this->assertEquals(12000, $outbound->paid_amount); // non-tunai: dibayar pas
    }

    public function test_penjualan_yang_sama_tidak_tersimpan_dua_kali(): void
    {
        $item = $this->makeItem(10, 10000);
        $page = Livewire::test(Pos::class);
        $args = [[['id' => $item->id, 'qty' => 1]], 'retail', 'qris', null, null, null, 'sale-abc'];

        $first = $page->call('checkout', ...$args)->effects['returns'][0];
        $second = $page->call('checkout', ...$args)->effects['returns'][0];

        $this->assertTrue($first['ok']);
        $this->assertFalse($second['ok']);
        $this->assertSame(1, Outbound::count());
    }

    public function test_penjualan_yang_gagal_boleh_dikirim_ulang_dengan_id_sama(): void
    {
        $item = $this->makeItem(1, 10000);
        $page = Livewire::test(Pos::class);

        $failed = $page->call('checkout', [['id' => $item->id, 'qty' => 2]], 'retail', 'qris', null, null, null, 'sale-xyz')->effects['returns'][0];
        $retry = $page->call('checkout', [['id' => $item->id, 'qty' => 1]], 'retail', 'qris', null, null, null, 'sale-xyz')->effects['returns'][0];

        $this->assertFalse($failed['ok']);
        $this->assertTrue($retry['ok'], $retry['message']);
        $this->assertSame(1, Outbound::count());
    }

    public function test_keranjang_kosong_ditolak(): void
    {
        $result = Livewire::test(Pos::class)
            ->call('checkout', [], 'retail', 'tunai', 0)
            ->effects['returns'][0];

        $this->assertFalse($result['ok']);
    }
}
