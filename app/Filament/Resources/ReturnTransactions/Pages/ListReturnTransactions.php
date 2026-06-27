<?php

namespace App\Filament\Resources\ReturnTransactions\Pages;

use App\Filament\Resources\ReturnTransactions\ReturnTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnTransactions extends ListRecords
{
    protected static string $resource = ReturnTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Retur'),
        ];
    }
}
