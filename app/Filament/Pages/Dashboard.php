<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('period')
                    ->label('Filter Periode Data')
                    ->options([
                        'today' => 'Hari Ini',
                        '7' => '7 Hari Terakhir',
                        '30' => '30 Hari Terakhir',
                        'month' => 'Bulan Ini',
                        'year' => 'Tahun Ini',
                    ])
                    ->default('30')
            ])->columns(3);
    }
}
