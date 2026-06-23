<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalAlignment('center'),
        ];
    }
}
