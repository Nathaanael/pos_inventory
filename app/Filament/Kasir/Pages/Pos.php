<?php

namespace App\Filament\Kasir\Pages;

use App\Filament\Concerns\InteractsWithPosCart;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class Pos extends Page
{
    use InteractsWithPosCart;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $title = 'Kasir';

    protected static ?string $slug = 'pos';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.resources.outbounds.create-outbound-pos';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterPosSaved(): void
    {
        // Kosongkan keranjang dan buat kode baru untuk pelanggan berikutnya.
        $this->redirect(static::getUrl());
    }

    public function getPosCancelUrl(): string
    {
        return static::getUrl();
    }

    /**
     * Kasir tidak perlu melihat harga modal (HPP) dan margin.
     */
    public function showCostInfo(): bool
    {
        return false;
    }
}
