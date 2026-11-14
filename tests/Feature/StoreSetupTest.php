<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Filament\Pages\SetupWizard;
use App\Filament\Resources\Brands\BrandResource;
use App\Models\StoreFeature;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wizard setup toko, halaman "toko belum disiapkan" untuk kasir, dan fitur per toko.
 */
class StoreSetupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR, 'is_active' => true]);
    }

    /* ------------------------------ Belum setup ------------------------------ */

    public function test_admin_diarahkan_ke_wizard_selama_toko_belum_setup(): void
    {
        $this->actingAs($this->admin());

        $this->get('/')->assertRedirect(SetupWizard::getUrl(panel: 'admin'));
        $this->get('/kasir/pos')->assertRedirect(SetupWizard::getUrl(panel: 'admin'));
        $this->get('/setup')->assertOk()->assertSee('Jenis Usaha');
    }

    public function test_kasir_melihat_pesan_toko_belum_disiapkan(): void
    {
        $this->actingAs($this->kasir());

        $this->get('/kasir/pos')->assertRedirect('/kasir/belum-siap');
        $this->get('/kasir/belum-siap')->assertOk()->assertSee('Toko belum disiapkan')->assertSee('Hubungi admin');
    }

    public function test_logout_tetap_bisa_walau_toko_belum_setup(): void
    {
        $this->actingAs($this->kasir());

        $this->post('/kasir/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    /* ------------------------------ Wizard ------------------------------ */

    public function test_pilih_jenis_usaha_mencentang_fitur_bawaan(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(SetupWizard::class)
            ->set('store_name', 'Toko HP Jaya')
            ->call('next')
            ->assertSet('step', 2)
            ->call('selectBusinessType', 'elektronik')
            ->assertSet('features', ['merek', 'gambar_barang', 'retur', 'stock_opname']);
    }

    public function test_tidak_bisa_lanjut_tanpa_nama_toko_dan_jenis_usaha(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(SetupWizard::class)
            ->set('store_name', '')
            ->call('next')
            ->assertHasErrors('store_name')
            ->assertSet('step', 1)
            ->set('store_name', 'Toko A')
            ->call('next')
            ->call('next')
            ->assertHasErrors('businessType')
            ->assertSet('step', 2);
    }

    public function test_selesai_wizard_menyimpan_toko_dan_semua_baris_fitur(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(SetupWizard::class)
            ->set('store_name', 'Warung Bu Sri')
            ->set('phone', '0812')
            ->call('next')
            ->call('selectBusinessType', 'makanan')
            ->call('next')
            ->call('toggleFeature', 'grosir')        // tambah grosir
            ->call('toggleFeature', 'stock_opname')  // matikan stock opname
            ->call('next')
            ->assertSet('step', 4)
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect();

        StoreSetting::flushCache();
        $setting = StoreSetting::current();

        $this->assertTrue($setting->isSetUp());
        $this->assertSame('Warung Bu Sri', $setting->store_name);
        $this->assertSame('makanan', $setting->business_type->value);

        // Satu baris per fitur, termasuk yang nonaktif.
        $this->assertSame(count(Feature::cases()), StoreFeature::count());
        $this->assertEqualsCanonicalizing(
            ['gambar_barang', 'grosir'],
            StoreFeature::where('is_enabled', true)->pluck('feature')->all(),
        );

        // Setelah setup, admin tidak lagi diarahkan ke wizard.
        $this->get('/setup')->assertRedirect();
        $this->get('/')->assertOk();
    }

    /* ------------------------------ Fitur ------------------------------ */

    public function test_fitur_nonaktif_menyembunyikan_menu(): void
    {
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->markStoreAsSetUp([Feature::WholesalePrice]);

        $this->assertFalse(StoreSetting::hasFeature(Feature::Brand));
        $this->assertFalse(BrandResource::canAccess());

        StoreSetting::saveFeatures([Feature::Brand]);

        $this->assertTrue(BrandResource::canAccess());
        $this->assertFalse(StoreSetting::hasFeature(Feature::WholesalePrice));
    }

    public function test_kasir_masuk_pos_setelah_toko_setup(): void
    {
        $this->actingAs($this->kasir());
        $this->markStoreAsSetUp();

        $this->get('/kasir/pos')->assertOk();
        $this->get('/kasir/belum-siap')->assertRedirect('/kasir/pos');
    }
}
