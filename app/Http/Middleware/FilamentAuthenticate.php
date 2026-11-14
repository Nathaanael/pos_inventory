<?php

namespace App\Http\Middleware;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Sama seperti Authenticate bawaan Filament, tetapi kasir yang membuka panel
 * admin/portal dialihkan ke portal kasir alih-alih mendapat halaman 403.
 */
class FilamentAuthenticate extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = Filament::auth()->user();

        if (
            $user instanceof User
            && $user->isKasir()
            && Filament::getCurrentOrDefaultPanel()->getId() !== 'kasir'
        ) {
            throw new HttpResponseException(redirect()->to(url('/kasir')));
        }

        parent::authenticate($request, $guards);
    }

    /**
     * Belum login: selalu ke satu halaman login yang sama, dari panel mana pun.
     */
    protected function redirectTo($request): ?string
    {
        return route('filament.admin.auth.login');
    }
}
