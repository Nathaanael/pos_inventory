<?php

namespace App\Filament\Resources\Inbounds\Tables;

use App\Enums\MovementType;
use App\Enums\TransactionStatus;
use App\Filament\Resources\Inbounds\Schemas\InboundViewForm;
use App\Models\Inbound;
use App\Models\Item;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class InboundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Nominal')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'diterima' => 'Diterima',
                        'pending' => 'Pending',
                        'voided' => 'Void',
                        default => ucfirst((string) ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state)),
                    })
                    ->color(fn ($state) => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'diterima' => 'success',
                        'pending' => 'warning',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
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
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(InboundViewForm::schema())
                    ->modalAutofocus(false),
                EditAction::make()
                    ->label('Ubah')
                    ->visible(fn (Inbound $record) => $record->status->value === 'pending'),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Barang Masuk')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi ini? Stok barang akan bertambah secara otomatis.')
                    ->modalSubmitActionLabel('Setujui')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (Inbound $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => TransactionStatus::RECEIVED,
                            ]);

                            foreach ($record->items as $itemModel) {
                                $item = Item::lockForUpdate()->find($itemModel->item_id);
                                $stockBefore = $item->current_stock;
                                $item->current_stock += $itemModel->quantity;
                                $item->save();

                                StockMovement::create([
                                    'item_id'        => $item->id,
                                    'type'           => MovementType::INBOUND->value,
                                    'reference_id'   => $record->id,
                                    'reference_type' => 'inbounds',
                                    'quantity'       => $itemModel->quantity,
                                    'stock_before'   => $stockBefore,
                                    'stock_after'    => $item->current_stock,
                                    'price'          => $itemModel->buy_price,
                                    'notes'          => 'Barang Masuk (Approved)',
                                    'created_by'     => auth()->id() ?? 1,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Transaksi Berhasil Disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Inbound $record) => $record->status->value === 'pending'),
                Action::make('void')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Transaksi')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan transaksi ini?')
                    ->modalSubmitActionLabel('Batalkan')
                    ->modalCancelActionLabel('Batal')
                    ->form([
                        Textarea::make('void_reason')
                            ->required()
                            ->label('Alasan Pembatalan'),
                    ])
                    ->action(function (Inbound $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status'      => TransactionStatus::VOIDED,
                                'voided_at'   => now(),
                                'voided_by'   => auth()->id(),
                                'void_reason' => $data['void_reason'],
                            ]);

                            foreach ($record->items as $itemModel) {
                                $item = Item::lockForUpdate()->find($itemModel->item_id);
                                $stockBefore = $item->current_stock;
                                $item->current_stock -= $itemModel->quantity;
                                $item->save();

                                StockMovement::create([
                                    'item_id'        => $item->id,
                                    'type'           => MovementType::VOID->value,
                                    'reference_id'   => $record->id,
                                    'reference_type' => 'inbounds',
                                    'quantity'       => -$itemModel->quantity,
                                    'stock_before'   => $stockBefore,
                                    'stock_after'    => $item->current_stock,
                                    'price'          => $itemModel->buy_price,
                                    'notes'          => 'Batal Barang Masuk: ' . $data['void_reason'],
                                    'created_by'     => auth()->id() ?? 1,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Transaksi Berhasil Dibatalkan')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Inbound $record) => $record->status->value === 'diterima' && ! $record->voided_at),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus Transaksi Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus transaksi yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Berhasil dihapus'),
                    \Filament\Actions\BulkAction::make('approve')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi terpilih?')
                        ->modalSubmitActionLabel('Setujui')
                        ->modalCancelActionLabel('Batal')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status->value !== 'pending') continue;

                                \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                                    $record->update([
                                        'status' => \App\Enums\TransactionStatus::RECEIVED,
                                    ]);

                                    foreach ($record->items as $itemModel) {
                                        $item = \App\Models\Item::lockForUpdate()->find($itemModel->item_id);
                                        $stockBefore = $item->current_stock;
                                        $item->current_stock += $itemModel->quantity;
                                        $item->save();

                                        \App\Models\StockMovement::create([
                                            'item_id'        => $item->id,
                                            'type'           => \App\Enums\MovementType::INBOUND->value,
                                            'reference_id'   => $record->id,
                                            'reference_type' => 'inbounds',
                                            'quantity'       => $itemModel->quantity,
                                            'stock_before'   => $stockBefore,
                                            'stock_after'    => $item->current_stock,
                                            'price'          => $itemModel->buy_price,
                                            'notes'          => 'Barang Masuk (Setujui Massal)',
                                            'created_by'     => auth()->id() ?? 1,
                                        ]);
                                    }
                                });
                                $count++;
                            }
                            
                            if ($count > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("$count Transaksi Berhasil Disetujui")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title("Tidak ada transaksi pending yang dipilih")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->columnManager(false);
    }
}