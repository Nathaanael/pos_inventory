<?php

namespace App\Filament\Resources\Inbounds\Pages;

use App\Filament\Resources\Inbounds\InboundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInbounds extends ListRecords
{
    protected static string $resource = InboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Barang Masuk'),
        ];
    }
}
