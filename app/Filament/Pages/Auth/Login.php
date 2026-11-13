<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

/**
 * Login panel admin. Kasir tidak boleh masuk panel admin, tapi kalau kasir
 * login dari sini dengan password yang benar, ia langsung dibawa ke portal kasir
 * (alih-alih melihat pesan "kredensial salah" yang membingungkan).
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $exception) {
            $data = $this->form->getState();
            $credentials = $this->getCredentialsFromFormData($data);
            $provider = Filament::auth()->getProvider();
            $user = $provider->retrieveByCredentials($credentials);

            if (
                $user instanceof User
                && $user->isKasir()
                && $user->is_active
                && $provider->validateCredentials($user, $credentials)
            ) {
                Filament::auth()->login($user, $data['remember'] ?? false);
                session()->regenerate();

                return app(LoginResponse::class);
            }

            throw $exception;
        }
    }
}
