<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function ($record, DeleteAction $action) {
                    if ($record->inbounds()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Gagal menghapus')
                            ->body('Supplier ini sedang digunakan dalam transaksi Inbound.')
                            ->send();
                        
                        $action->cancel();
                    }
                }),
        ];
    }
}
