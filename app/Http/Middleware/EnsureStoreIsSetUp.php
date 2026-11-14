<?php

namespace App\Http\Middleware;

use App\Filament\Kasir\Pages\StoreNotReady;
use App\Filament\Pages\SetupWizard;
use App\Models\StoreSetting;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Selama toko belum di-setup (store_settings.setup_completed_at kosong):
 *  - admin selalu diarahkan ke wizard setup,
 *  - kasir diarahkan ke halaman "Toko belum disiapkan, hubungi admin".
 *
 * Dipasang di authMiddleware semua panel, setelah middleware login.
 */
class EnsureStoreIsSetUp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user instanceof User
            || $request->routeIs('filament.*.auth.logout')
            || StoreSetting::current()->isSetUp()
        ) {
            return $next($request);
        }

        if ($user->isKasir()) {
            return $request->routeIs(StoreNotReady::getRouteName(Filament::getPanel('kasir')))
                ? $next($request)
                : redirect()->to(StoreNotReady::getUrl(panel: 'kasir'));
        }

        return $request->routeIs(SetupWizard::getRouteName(Filament::getPanel('admin')))
            ? $next($request)
            : redirect()->to(SetupWizard::getUrl(panel: 'admin'));
    }
}
