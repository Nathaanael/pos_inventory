<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Halaman login panel kasir/portal. Aplikasi hanya punya satu halaman login
 * (/login); halaman ini cukup meneruskan ke sana. Setelah login, user diarahkan
 * sesuai role (lihat App\Http\Responses\LoginResponse).
 */
class RedirectToMainLogin extends BaseLogin
{
    public function mount(): void
    {
        $this->redirect(route('filament.admin.auth.login'));
    }
}
