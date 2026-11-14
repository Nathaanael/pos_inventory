<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Logout dari panel mana pun (admin, kasir, portal) selalu kembali ke
 * satu halaman login yang sama.
 */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->route('filament.admin.auth.login');
    }
}
