<?php

namespace App\Filament\Resources\ReturnTransactions\Pages;

use App\Filament\Resources\ReturnTransactions\ReturnTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnTransaction extends CreateRecord
{
    protected static string $resource = ReturnTransactionResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Transaksi Retur';
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
