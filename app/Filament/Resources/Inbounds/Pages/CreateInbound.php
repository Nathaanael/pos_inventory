<?php

namespace App\Filament\Resources\Inbounds\Pages;

use App\Filament\Resources\Inbounds\InboundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInbound extends CreateRecord
{
    protected static string $resource = InboundResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
