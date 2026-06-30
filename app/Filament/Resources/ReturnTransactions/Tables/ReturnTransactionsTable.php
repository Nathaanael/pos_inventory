<?php

namespace App\Filament\Resources\ReturnTransactions\Tables;

use App\Filament\Resources\ReturnTransactions\Schemas\ReturnTransactionViewForm;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReturnTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('return_code')
                    ->label('Kode Retur')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'supplier' ? 'Ke Supplier' : 'Dari Customer')
                    ->color(fn ($state) => $state === 'supplier' ? 'warning' : 'info'),
                TextColumn::make('supplier.name')
                    ->label('Supplier / Customer')
                    ->getStateUsing(fn ($record) => $record->type === 'supplier' ? ($record->supplier?->name ?? '-') : ($record->customer_name ?? '-'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'selesai' => 'success',
                        'pending' => 'warning',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'selesai' => 'Disetujui',
                        'pending' => 'Pending',
                        'ditolak' => 'Ditolak',
                        default => ucfirst((string) ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state)),
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Harga Retur')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
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
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'selesai' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('return-transaction.pdf', $record))
                    ->openUrlInNewTab(),
                ViewAction::make()
                    ->label('Lihat')
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(ReturnTransactionViewForm::schema())
                    ->modalAutofocus(false),
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Retur')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui retur ini?')
                    ->modalSubmitActionLabel('Setujui')
                    ->modalCancelActionLabel('Batal')
                    ->hidden(fn ($record) => ($record->status instanceof \App\Enums\TransactionStatus ? $record->status->value : $record->status) !== 'pending')
                    ->action(function ($record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $record->update(['status' => 'selesai']);

                            foreach ($record->items as $model) {
                                $item = \App\Models\Item::lockForUpdate()->find($model->item_id);
                                $stockBefore = $item->current_stock;

                                $isSupplierReturn = ($record->type === 'supplier' || empty($record->type));

                                $supplierId = $record->supplier_id;
                                $itemSupplier = null;
                                
                                if ($supplierId) {
                                    $itemSupplier = \App\Models\ItemSupplier::firstOrCreate(
                                        ['item_id' => $item->id, 'supplier_id' => $supplierId],
                                        ['harga_beli' => $model->price ?? $item->harga_beli, 'current_stock' => 0]
                                    );
                                }

                                if ($isSupplierReturn) {
                                    if ($item->current_stock < $model->quantity) {
                                        throw \Illuminate\Validation\ValidationException::withMessages([
                                            'approve' => "Stok {$item->name} tidak cukup. Tersedia: {$item->current_stock}",
                                        ]);
                                    }
                                    if ($itemSupplier && $itemSupplier->current_stock < $model->quantity) {
                                        throw \Illuminate\Validation\ValidationException::withMessages([
                                            'approve' => "Stok {$item->name} dari supplier ini tidak cukup. Tersedia: {$itemSupplier->current_stock}",
                                        ]);
                                    }

                                    $qty = -$model->quantity;
                                    $movementType = \App\Enums\MovementType::RETURN_OUT->value;
                                    $notes = 'Retur ke Supplier (Approved)';
                                } else {
                                    $qty = $model->quantity;
                                    $movementType = \App\Enums\MovementType::RETURN_IN->value;
                                    $notes = 'Retur dari Customer (Approved)';
                                }

                                $item->current_stock += $qty;
                                $item->save();
                                
                                if ($itemSupplier) {
                                    $itemSupplier->current_stock += $qty;
                                    $itemSupplier->save();
                                }

                                \App\Models\StockMovement::create([
                                    'item_id' => $item->id,
                                    'type' => $movementType,
                                    'reference_id' => $record->id,
                                    'reference_type' => 'returns',
                                    'quantity' => $qty,
                                    'stock_before' => $stockBefore,
                                    'stock_after' => $item->current_stock,
                                    'price' => $model->price ?? 0,
                                    'notes' => $notes,
                                    'created_by' => auth()->id() ?? 1
                                ]);
                            }
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Transaksi Berhasil Disetujui')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Retur')
                    ->modalDescription('Apakah Anda yakin ingin menolak retur ini?')
                    ->modalSubmitActionLabel('Tolak')
                    ->modalCancelActionLabel('Batal')
                    ->hidden(fn ($record) => ($record->status instanceof \App\Enums\TransactionStatus ? $record->status->value : $record->status) !== 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'ditolak']);

                        \Filament\Notifications\Notification::make()
                            ->title('Transaksi Berhasil Ditolak')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->label('Ubah')
                    ->visible(fn ($record) => ($record->status instanceof \App\Enums\TransactionStatus ? $record->status->value : $record->status) === 'pending'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Setujui Terpilih')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Retur Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui semua retur yang dipilih?')
                        ->modalSubmitActionLabel('Setujui')
                        ->modalCancelActionLabel('Batal')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if (($record->status instanceof \App\Enums\TransactionStatus ? $record->status->value : $record->status) !== 'pending') continue;
                                    
                                    $record->update(['status' => 'selesai']);

                                    foreach ($record->items as $model) {
                                        $item = \App\Models\Item::lockForUpdate()->find($model->item_id);
                                        $stockBefore = $item->current_stock;

                                        $isSupplierReturn = ($record->type === 'supplier' || empty($record->type));

                                        $supplierId = $record->supplier_id;
                                        $itemSupplier = null;
                                        
                                        if ($supplierId) {
                                            $itemSupplier = \App\Models\ItemSupplier::firstOrCreate(
                                                ['item_id' => $item->id, 'supplier_id' => $supplierId],
                                                ['harga_beli' => $model->price ?? $item->harga_beli, 'current_stock' => 0]
                                            );
                                        }

                                        if ($isSupplierReturn) {
                                            if ($item->current_stock < $model->quantity) {
                                                throw \Illuminate\Validation\ValidationException::withMessages([
                                                    'bulk_approve' => "Stok {$item->name} tidak cukup. Tersedia: {$item->current_stock}",
                                                ]);
                                            }
                                            if ($itemSupplier && $itemSupplier->current_stock < $model->quantity) {
                                                throw \Illuminate\Validation\ValidationException::withMessages([
                                                    'bulk_approve' => "Stok {$item->name} dari supplier ini tidak cukup. Tersedia: {$itemSupplier->current_stock}",
                                                ]);
                                            }

                                            $qty = -$model->quantity;
                                            $movementType = \App\Enums\MovementType::RETURN_OUT->value;
                                            $notes = 'Retur ke Supplier (Approved)';
                                        } else {
                                            $qty = $model->quantity;
                                            $movementType = \App\Enums\MovementType::RETURN_IN->value;
                                            $notes = 'Retur dari Customer (Approved)';
                                        }

                                        $item->current_stock += $qty;
                                        $item->save();
                                        
                                        if ($itemSupplier) {
                                            $itemSupplier->current_stock += $qty;
                                            $itemSupplier->save();
                                        }

                                        \App\Models\StockMovement::create([
                                            'item_id' => $item->id,
                                            'type' => $movementType,
                                            'reference_id' => $record->id,
                                            'reference_type' => 'returns',
                                            'quantity' => $qty,
                                            'stock_before' => $stockBefore,
                                            'stock_after' => $item->current_stock,
                                            'price' => $model->price ?? 0,
                                            'notes' => $notes,
                                            'created_by' => auth()->id() ?? 1
                                        ]);
                                    }
                                }
                            });

                            $count = $records->count();
                            \Filament\Notifications\Notification::make()
                                ->title("$count Transaksi Berhasil Disetujui")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulk_reject')
                        ->label('Tolak Terpilih')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Retur Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menolak semua retur yang dipilih?')
                        ->modalSubmitActionLabel('Tolak')
                        ->modalCancelActionLabel('Batal')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn ($record) => $record->update(['status' => 'ditolak']));

                            $count = $records->count();
                            \Filament\Notifications\Notification::make()
                                ->title("$count Transaksi Berhasil Ditolak")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->columnManager(false);
    }
}
