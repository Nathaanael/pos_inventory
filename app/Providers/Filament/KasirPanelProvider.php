<?php

namespace App\Providers\Filament;

use App\Models\StoreSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Portal kasir: hanya halaman Kasir (POS) dan Riwayat Transaksi.
 * User dengan role "kasir" hanya bisa masuk ke panel ini (lihat User::canAccessPanel).
 */
class KasirPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('kasir')
            ->path('kasir')
            ->login()
            ->brandName(fn () => StoreSetting::storeName() . ' · Kasir')
            ->brandLogo(fn () => StoreSetting::current()->logoUrl())
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topNavigation()
            ->discoverPages(in: app_path('Filament/Kasir/Pages'), for: 'App\Filament\Kasir\Pages')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
