<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Satu halaman login (/login) untuk semua role; tujuan setelah login sesuai role.
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_di_panel_kasir_diarahkan_ke_login_utama(): void
    {
        $this->get('/kasir')->assertRedirect('/login');
    }

    public function test_halaman_login_kasir_lama_diteruskan_ke_login_utama(): void
    {
        $this->get('/kasir/login')->assertRedirect('/login');
    }

    public function test_logout_dari_panel_kasir_kembali_ke_login_utama(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));

        $this->post('/kasir/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_login_masuk_panel_admin(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true, 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'rahasia123')
            ->call('authenticate')
            ->assertRedirect(url('/'));
    }

    public function test_kasir_login_masuk_panel_kasir(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['role' => User::ROLE_KASIR, 'is_active' => true, 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'rahasia123')
            ->call('authenticate')
            ->assertRedirect(url('/kasir'));
    }
}
