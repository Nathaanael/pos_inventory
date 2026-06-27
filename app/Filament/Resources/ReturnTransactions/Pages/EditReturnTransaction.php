<?php

namespace App\Filament\Resources\ReturnTransactions\Pages;

use App\Filament\Resources\ReturnTransactions\ReturnTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnTransaction extends EditRecord
{
    protected static string $resource = ReturnTransactionResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Ubah Transaksi Retur';
    }

    public function getBreadcrumb(): string
    {
        return 'Ubah';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('return-transaction.pdf', $record))
                ->openUrlInNewTab(),
            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->label('Simpan');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Berhasil diperbarui';
    }
}
