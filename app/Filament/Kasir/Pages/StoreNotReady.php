<?php

namespace App\Filament\Kasir\Pages;

use App\Models\StoreSetting;
use Filament\Pages\Page;

/**
 * Tampil untuk kasir selama admin belum menyelesaikan setup toko
 * (lihat App\Http\Middleware\EnsureStoreIsSetUp).
 */
class StoreNotReady extends Page
{
    protected static ?string $slug = 'belum-siap';

    protected static ?string $title = 'Toko belum disiapkan';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.base';

    protected string $view = 'filament.kasir.pages.store-not-ready';

    public function mount(): void
    {
        // Setup sudah selesai: langsung ke layar kasir.
        if (StoreSetting::current()->isSetUp()) {
            $this->redirect(Pos::getUrl());
        }
    }

    /** Tombol "Cek lagi": muat ulang; bila admin sudah selesai setup, otomatis ke POS. */
    public function recheck(): void
    {
        StoreSetting::flushCache();

        if (StoreSetting::current()->isSetUp()) {
            $this->redirect(Pos::getUrl());
        }
    }
}
