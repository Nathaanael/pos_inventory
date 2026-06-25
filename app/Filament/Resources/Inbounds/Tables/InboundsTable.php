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
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(InboundViewForm::schema())
                    ->modalAutofocus(false),
                EditAction::make()
                    ->visible(fn (Inbound $record) => $record->status->value === 'pending'),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Inbound')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi ini? Stok barang akan bertambah secara otomatis.')
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
                            ->title('Transaksi Berhasil di-Approve')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Inbound $record) => $record->status->value === 'pending'),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('void_reason')
                            ->required()
                            ->label('Alasan Void'),
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
                                    'notes'          => 'Void Inbound: ' . $data['void_reason'],
                                    'created_by'     => auth()->id() ?? 1,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Transaksi Berhasil di-Void')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Inbound $record) => $record->status->value === 'diterima' && ! $record->voided_at),
            ])
            ->columnManager(false);
    }
}