<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Enums\OrderType;
use App\Filament\Kasir\Pages\Meja;
use App\Filament\Kasir\Pages\Pos;
use App\Filament\Resources\DiningTables\DiningTableResource;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Item;
use App\Models\Outbound;
use App\Models\StoreSetting;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modul FNB: master meja, nomor meja & take away di kasir, pantauan meja.
 */
class FnbTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('kasir'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR, 'is_active' => true]));
        $this->markStoreAsSetUp(null, 'makanan');
    }

    private function item(int $stock = 20): Item
    {
        return Item::create([
            'code' => 'BRG-' . fake()->unique()->numerify('####'),
            'name' => 'Menu ' . fake()->unique()->word(),
            'category_id' => Category::firstOrCreate(['name' => 'Makanan'], ['slug' => 'makanan'])->id,
            'unit_id' => Unit::firstOrCreate(['name' => 'porsi'])->id,
            'harga_beli' => 5000,
            'harga_jual_retail' => 15000,
            'harga_jual_grosir' => 0,
            'current_stock' => $stock,
        ]);
    }

    /** @return array<string, mixed> */
    private function checkout(Item $item, ?string $orderType, ?int $tableId): array
    {
        return Livewire::test(Pos::class)
            ->call('checkout', [['id' => $item->id, 'qty' => 2]], 'retail', 'qris', null, 'Andi', null, null, $orderType, $tableId)
            ->effects['returns'][0];
    }

    public function test_makan_di_tempat_wajib_pilih_meja(): void
    {
        $result = $this->checkout($this->item(), 'dine_in', null);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('nomor meja', $result['message']);
        $this->assertSame(0, Outbound::count());
    }

    public function test_pesanan_di_meja_tersimpan_dan_meja_jadi_terisi(): void
    {
        $table = DiningTable::create(['number' => 'T1', 'name' => 'Teras', 'capacity' => 4]);

        $result = $this->checkout($this->item(), 'dine_in', $table->id);

        $this->assertTrue($result['ok'], $result['message']);
        $outbound = Outbound::sole();
        $this->assertSame(OrderType::DineIn, $outbound->order_type);
        $this->assertSame($table->id, $outbound->dining_table_id);
        $this->assertSame('Meja T1', $outbound->orderLabel());

        // Daftar meja di kasir & halaman pantauan menandai meja terisi.
        $this->assertTrue(collect($result['tables'])->firstWhere('id', $table->id)['occupied']);
        $meja = collect(Livewire::test(Meja::class)->instance()->getTables())->firstWhere('id', $table->id);
        $this->assertTrue($meja['occupied']);
        $this->assertEquals(30000, $meja['total']);
    }

    public function test_take_away_tanpa_meja(): void
    {
        DiningTable::create(['number' => 'T1']);

        $result = $this->checkout($this->item(), 'take_away', null);

        $this->assertTrue($result['ok'], $result['message']);
        $outbound = Outbound::sole();
        $this->assertSame(OrderType::TakeAway, $outbound->order_type);
        $this->assertNull($outbound->dining_table_id);
        $this->assertSame(0, DiningTable::first()->activeOrders()->count());
    }

    public function test_meja_nonaktif_ditolak(): void
    {
        $table = DiningTable::create(['number' => 'T9', 'is_active' => false]);

        $result = $this->checkout($this->item(), 'dine_in', $table->id);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Outbound::count());
    }

    public function test_kosongkan_meja(): void
    {
        $table = DiningTable::create(['number' => 'T2']);
        $this->checkout($this->item(), 'dine_in', $table->id);
        $this->assertSame(1, $table->activeOrders()->count());

        Livewire::test(Meja::class)->call('releaseTable', $table->id);

        $this->assertSame(0, $table->activeOrders()->count());
        $this->assertNotNull(Outbound::sole()->table_released_at);
    }

    public function test_fitur_meja_hanya_untuk_fnb(): void
    {
        // Jenis usaha lain: fitur meja & take away otomatis nonaktif walau dicentang.
        $this->markStoreAsSetUp([Feature::DiningTables, Feature::TakeAway, Feature::Returns], 'umum');

        $this->assertFalse(StoreSetting::hasFeature(Feature::DiningTables));
        $this->assertFalse(StoreSetting::hasFeature(Feature::TakeAway));
        $this->assertTrue(StoreSetting::hasFeature(Feature::Returns));
        $this->assertFalse(Meja::canAccess());
        $this->assertFalse(DiningTableResource::canAccess());

        // Checkout mengabaikan tipe pesanan.
        $result = $this->checkout($this->item(), 'take_away', null);
        $this->assertTrue($result['ok'], $result['message']);
        $this->assertNull(Outbound::sole()->order_type);
    }

    public function test_fnb_tanpa_fitur_meja_tetap_bisa_take_away_dan_meja_tidak_wajib(): void
    {
        $this->markStoreAsSetUp([Feature::TakeAway], 'makanan');

        $result = $this->checkout($this->item(), 'dine_in', null);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(OrderType::DineIn, Outbound::sole()->order_type);
        $this->assertNull(Outbound::sole()->dining_table_id);
    }

    public function test_halaman_meja_dan_kasir_tampil(): void
    {
        DiningTable::create(['number' => 'T1', 'name' => 'Teras depan']);

        $this->get('/kasir/meja')->assertOk()->assertSee('Teras depan');
        $this->get('/kasir/pos')->assertOk()->assertSee('Makan di Tempat');
    }

    public function test_admin_bisa_buka_master_meja(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));

        $this->get(DiningTableResource::getUrl(panel: 'admin'))->assertOk()->assertSee('Master Meja');
    }
}
