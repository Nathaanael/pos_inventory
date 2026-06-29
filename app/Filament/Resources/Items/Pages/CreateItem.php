<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Tambah Master Barang';
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

    /**
     * Set default harga_beli before creating the item record.
     * The actual value will be synced from the primary supplier in afterCreate().
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['harga_beli'] = 0;

        return $data;
    }

    /**
     * After the item and its item_supplier records are created,
     * sync the aggregate fields (supplier_id, harga_beli) on the items table
     * for backward compatibility with existing transaction forms.
     */
    protected function afterCreate(): void
    {
        $this->record->syncAggregateFields();
    }
}
