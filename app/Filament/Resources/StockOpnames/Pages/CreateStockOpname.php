<?php

namespace App\Filament\Resources\StockOpnames\Pages;

use App\Filament\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    public string $submitMode = 'approved';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Selesaikan (Completed)'),
            Action::make('saveDraft')
                ->label('Lanjutkan Nanti (Draft)')
                ->color('warning')
                ->action(function () {
                    $this->submitMode = 'draft';
                    
                    if (is_array($this->data['items'] ?? null)) {
                        $this->data['items'] = array_filter($this->data['items'], function ($item) {
                            return !empty($item['item_id']);
                        });
                    }

                    $this->create();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
