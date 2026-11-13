<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Login dari halaman login kasir: tetap di portal kasir (juga untuk admin).
        if (Filament::getCurrentPanel()?->getId() === 'kasir') {
            return redirect()->to(url('/kasir'));
        }

        // Selain itu arahkan sesuai role: kasir ke portal kasir, lainnya ke panel admin.
        return redirect()->to($request->user()?->homeUrl() ?? url('/'));
    }
}
