<?php

namespace App\Filament\Portal\Pages;

use Filament\Pages\Page;
use Filament\Panel;

class AppLauncher extends Page
{
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    protected string $view = 'filament.portal.pages.app-launcher';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getTitle(): string
    {
        return 'Pilih Aplikasi';
    }
}

