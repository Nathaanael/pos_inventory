<?php

namespace App\Providers\Filament;

use App\Filament\Portal\Pages\AppLauncher;
use App\Models\StoreSetting;
use App\Http\Middleware\EnsureStoreIsSetUp;
use App\Http\Middleware\FilamentAuthenticate;
use App\Filament\Pages\Auth\RedirectToMainLogin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ->default()
            ->id('portal')
            ->path('portal')
            ->login(RedirectToMainLogin::class)
            ->brandName(fn () => StoreSetting::storeName())
            ->brandLogo(fn () => StoreSetting::current()->logoUrl())
            ->brandLogoHeight('2.5rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->pages([
                AppLauncher::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\Filament\Portal\Widgets')
            ->widgets([])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#f59e0b">')
            )

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
                FilamentAuthenticate::class,
                EnsureStoreIsSetUp::class,
            ]);
    }
}

