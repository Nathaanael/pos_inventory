<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('name')
                    ->label('Nama')
                    ->options([
                        'pcs' => 'Pcs',
                        'meter' => 'Meter',
                        'yard' => 'Yard',
                    ])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
