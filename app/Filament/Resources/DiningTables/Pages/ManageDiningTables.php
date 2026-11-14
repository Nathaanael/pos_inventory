<?php

namespace App\Filament\Resources\DiningTables\Pages;

use App\Filament\Resources\DiningTables\DiningTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDiningTables extends ManageRecords
{
    protected static string $resource = DiningTableResource::class;

    public function getTitle(): string
    {
        return 'Master Meja';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Meja'),
        ];
    }
}
