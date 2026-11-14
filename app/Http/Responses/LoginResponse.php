<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Arahkan sesuai role, dari halaman login mana pun (admin atau kasir):
        // kasir ke portal kasir, admin ke panel admin. Admin tetap bisa membuka
        // /kasir secara manual bila perlu membantu di kasir.
        return redirect()->to($request->user()?->homeUrl() ?? url('/'));
    }
}
