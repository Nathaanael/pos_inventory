<?php

namespace App\Filament\Resources\StockOpnames\Pages;

use App\Filament\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditStockOpname extends EditRecord
{
    protected static string $resource = StockOpnameResource::class;

    public string $submitMode = 'selesai';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Lanjutkan Stock Opname';
    }

    public function getBreadcrumb(): string
    {
        return 'Lanjutkan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn ($record) => route('stock-opname.pdf', $record))
                ->openUrlInNewTab(),
            // DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Selesaikan'),
            Action::make('saveDraft')
                ->label('Simpan Konsep')
                ->color('warning')
                ->action(function () {
                    $this->submitMode = 'draft';
                    
                    if (is_array($this->data['items'] ?? null)) {
                        $this->data['items'] = array_filter($this->data['items'], function ($item) {
                            return !empty($item['item_id']);
                        });
                    }

                    $this->save();
                }),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Berhasil diperbarui';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = $this->submitMode;

        $items = $this->data['items'] ?? [];
        $total_scanned = 0;
        $total_match = 0;
        $total_diff = 0;

        foreach ($items as $item) {
            if (empty($item['item_id'])) continue;
            
            $physical = (int)($item['physical_stock'] ?? 0);
            $total_scanned += $physical;
            
            $diff = (int)($item['difference'] ?? 0);
            $total_diff += $diff;
            
            if ($diff === 0) {
                $total_match++;
            }
        }

        $data['total_items_scanned'] = $total_scanned;
        $data['total_match'] = $total_match;
        $data['total_difference'] = $total_diff;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
