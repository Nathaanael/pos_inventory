<?php

namespace Tests\Feature;

use App\Filament\Pages\StoreSettings;
use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Logo di Pengaturan Toko langsung tersimpan saat diganti/dihapus, tanpa tombol Simpan.
 */
class StoreLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->markStoreAsSetUp();
    }

    public function test_unggah_logo_langsung_tersimpan(): void
    {
        Livewire::test(StoreSettings::class)
            ->set('data.logo', UploadedFile::fake()->image('logo.png', 400, 100));

        StoreSetting::flushCache();
        $logo = StoreSetting::current()->logo;

        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_hapus_logo_langsung_tersimpan_dan_file_lama_terhapus(): void
    {
        Storage::disk('public')->put('store/lama.png', 'x');
        StoreSetting::current()->update(['logo' => 'store/lama.png']);
        StoreSetting::flushCache();

        Livewire::test(StoreSettings::class)
            ->set('data.logo', null);

        StoreSetting::flushCache();
        $this->assertNull(StoreSetting::current()->logo);
        Storage::disk('public')->assertMissing('store/lama.png');
    }

    public function test_edit_logo_menyisakan_satu_file_dan_simpan_tidak_menduplikasi(): void
    {
        $page = Livewire::test(StoreSettings::class)
            ->set('data.logo', UploadedFile::fake()->image('asli.png', 400, 100))
            // Hasil editor gambar = file baru yang menggantikan file asli.
            ->set('data.logo', UploadedFile::fake()->image('asli-v1.png', 200, 100));

        StoreSetting::flushCache();
        $logo = StoreSetting::current()->logo;

        $this->assertNotNull($logo);
        $this->assertSame([$logo], Storage::disk('public')->files('store'));

        // Tombol "Simpan Pengaturan" tidak boleh menyimpan logo lagi.
        $page->call('save');

        StoreSetting::flushCache();
        $this->assertSame($logo, StoreSetting::current()->logo);
        $this->assertSame([$logo], Storage::disk('public')->files('store'));
    }
}
