<?php

namespace App\Filament\Resources\Outbounds\Pages;

use App\Filament\Resources\Outbounds\OutboundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOutbound extends CreateRecord
{
    protected static string $resource = OutboundResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Barang Keluar';
    }

    public function getBreadcrumb(): string
    {
        return 'Tambah';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Tambah');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->label('Tambah & buat lagi');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Berhasil ditambah';
    }
}
