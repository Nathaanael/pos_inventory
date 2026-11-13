<?php

namespace App\Filament\Resources\Outbounds\Pages;

use App\Filament\Concerns\InteractsWithPosCart;
use App\Filament\Resources\Outbounds\OutboundResource;
use Filament\Resources\Pages\Page;

/**
 * POS di panel admin (Barang Keluar > Tambah). Dipakai saat darurat;
 * kasir sehari-hari memakai portal kasir (App\Filament\Kasir\Pages\Pos).
 */
class CreateOutboundPos extends Page
{
    use InteractsWithPosCart;

    protected static string $resource = OutboundResource::class;

    protected string $view = 'filament.resources.outbounds.create-outbound-pos';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Barang Keluar';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah (POS)';
    }

    protected function afterPosSaved(): void
    {
        $this->redirect(OutboundResource::getUrl('index'));
    }

    public function getPosCancelUrl(): string
    {
        return OutboundResource::getUrl('index');
    }
}
