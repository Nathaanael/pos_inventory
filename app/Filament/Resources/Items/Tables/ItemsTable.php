<?php

namespace App\Filament\Resources\Items\Tables;

use App\Filament\Resources\Items\Schemas\ItemViewForm;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('suppliers.name')
                    ->label('Supplier')
                    ->badge()
                    ->searchable(),
                TextColumn::make('harga_beli')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('latestMovement.type')
                    ->label('Aktivitas Terakhir')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $movement = $record->latestMovement;
                        if (!$movement) return null;
                        $hoursDiff = now()->diffInHours($movement->created_at);
                        if ($hoursDiff > 24) return null;
                        return match ($movement->type) {
                            'inbound' => 'Inbound (' . $movement->quantity . ')',
                            'return_out' => 'Retur (-' . abs($movement->quantity) . ')',
                            'adjustment' => 'Adjustment (' . $movement->quantity . ')',
                            default => ucfirst($movement->type),
                        };
                    })
                    ->color(fn ($state) => match (true) {
                        str_contains($state ?? '', 'Inbound') => 'success',
                        str_contains($state ?? '', 'Retur') => 'danger',
                        str_contains($state ?? '', 'Adjustment') => 'info',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                TextColumn::make('rack_location')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(ItemViewForm::schema())
                    ->modalAutofocus(false),
                EditAction::make(),
            ])
            ->columnManager(false);
            // ->toolbarActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //         ForceDeleteBulkAction::make(),
            //         RestoreBulkAction::make(),
            //     ]),
            // ]);
    }
}
