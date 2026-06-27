<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Resources\Brands\BrandResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->before(function ($record, DeleteAction $action) {
                    if ($record->items()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Gagal menghapus')
                            ->body('Merk ini sedang digunakan oleh data Master Barang.')
                            ->send();
                        
                        $action->cancel();
                    }
                }),
        ];
    }
}
