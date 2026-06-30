<?php

namespace App\Filament\Resources\Items\Tables;

use App\Filament\Resources\Items\Schemas\ItemViewForm;
use App\Models\Supplier;

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
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('supplier_list')
                    ->label('Supplier')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $suppliers = $record->suppliers;
                        if ($suppliers->isEmpty()) return null;
                        
                        if ($suppliers->count() > 1) {
                            return $suppliers->first()->name . ' ... +';
                        }
                        return $suppliers->first()->name;
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('suppliers', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable()
                    ->description(function ($record) {
                        $count = $record->itemSuppliers->count();
                        if ($count > 1) {
                            return $count . ' supplier';
                        }
                        return null;
                    }),
                TextColumn::make('current_stock')
                    ->label('Stok Saat Ini')
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
                            'outbound' => 'Outbound (' . $movement->quantity . ')',
                            'return_in' => 'Return In (' . $movement->quantity . ')',
                            'return_out' => 'Retur (' . $movement->quantity . ')',
                            'adjustment' => 'Adjustment (' . $movement->quantity . ')',
                            default => ucfirst($movement->type) . ' (' . $movement->quantity . ')',
                        };
                    })
                    ->color(fn ($state) => match (true) {
                        str_contains($state ?? '', 'Inbound') || str_contains($state ?? '', 'Return In') => 'success',
                        str_contains($state ?? '', 'Outbound') => 'warning',
                        str_contains($state ?? '', 'Retur') => 'danger',
                        str_contains($state ?? '', 'Adjustment') => 'info',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                // TextColumn::make('rack_location')
                //     ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Dihapus pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('Merk')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('supplier')
                    ->label('Supplier')
                    ->options(fn () => Supplier::where('is_active', true)->pluck('name', 'id'))
                    ->query(fn ($query, array $data) =>
                        !empty($data['value'])
                            ? $query->whereHas('itemSuppliers', fn ($q) => $q->where('supplier_id', $data['value']))
                            : $query
                    )
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('history')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->modalHeading(fn ($record) => 'Histori Aktivitas: ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.resources.items.history', [
                        'movements' => $record->stockMovements()->latest()->take(10)->get()
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                ViewAction::make()
                    ->label('Lihat')
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(ItemViewForm::schema())
                    ->modalAutofocus(false),
                EditAction::make()
                    ->label('Ubah'),
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
