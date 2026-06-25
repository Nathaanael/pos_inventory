<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                Select::make('parent_id')
                    ->relationship(
                        name: 'parent', 
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, ?\App\Models\Category $record) => 
                            $record ? $query->where('id', '!=', $record->id) : $query
                    )
                    ->label('Kategori Induk')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
            ]);
    }
}
