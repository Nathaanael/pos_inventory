<?php

namespace App\Filament\Resources\Outbounds\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\Outbounds\Schemas\OutboundViewForm;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_code')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->searchable(),
                TextColumn::make('destination')
                    ->searchable(),
                TextColumn::make('price_type_applied')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_cogs')
                    ->numeric()
                    ->sortable(),
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
                    ->form(OutboundViewForm::schema())
                    ->modalAutofocus(false),
                Action::make('print_nota')
                    ->label('Print Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Nota')
                    ->modalDescription('Cetak nota langsung ke printer thermal?')
                    ->action(function ($record) {
                        try {
                            $record->load(['items.item.unit', 'createdBy']);
                            app(\App\Services\ThermalPrintService::class)->printOutboundReceipt($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Nota berhasil dicetak!')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal cetak nota')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status->value === 'selesai'),
                // Action::make('preview_nota')
                //     ->label('Preview')
                //     ->icon('heroicon-o-eye')
                //     ->color('gray')
                //     ->url(fn ($record) => route('outbound.print', $record))
                //     ->openUrlInNewTab()
                //     ->visible(fn ($record) => $record->status->value === 'selesai'),
                EditAction::make()
                    ->visible(fn ($record) => $record->status->value === 'pending'),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Outbound')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi ini? Stok barang akan berkurang secara otomatis.')
                    ->action(function ($record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => \App\Enums\TransactionStatus::COMPLETED,
                            ]);

                            foreach ($record->items as $itemModel) {
                                $item = \App\Models\Item::lockForUpdate()->find($itemModel->item_id);
                                $stockBefore = $item->current_stock;
                                
                                if ($item->current_stock < $itemModel->quantity) {
                                    throw \Illuminate\Validation\ValidationException::withMessages([
                                        'approve' => "Stok {$item->name} tidak cukup untuk di-approve. Tersedia: {$item->current_stock}",
                                    ]);
                                }

                                $item->current_stock -= $itemModel->quantity;
                                $item->save();

                                \App\Models\StockMovement::create([
                                    'item_id'        => $item->id,
                                    'type'           => \App\Enums\MovementType::OUTBOUND->value,
                                    'reference_id'   => $record->id,
                                    'reference_type' => 'outbounds',
                                    'quantity'       => -$itemModel->quantity,
                                    'stock_before'   => $stockBefore,
                                    'stock_after'    => $item->current_stock,
                                    'price'          => $itemModel->sell_price,
                                    'notes'          => 'Barang Keluar (Approved)',
                                    'created_by'     => auth()->id() ?? 1,
                                ]);
                            }
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Transaksi Berhasil di-Approve')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status->value === 'pending'),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('void_reason')->required()->label('Alasan Void')
                    ])
                    ->action(function ($record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => \App\Enums\TransactionStatus::VOIDED,
                                'voided_at' => now(),
                                'voided_by' => auth()->id(),
                                'void_reason' => $data['void_reason'],
                            ]);
                            foreach ($record->items as $itemModel) {
                                $item = \App\Models\Item::lockForUpdate()->find($itemModel->item_id);
                                $stockBefore = $item->current_stock;
                                $item->current_stock += $itemModel->quantity;
                                $item->save();
                                \App\Models\StockMovement::create([
                                    'item_id' => $item->id,
                                    'type' => \App\Enums\MovementType::VOID->value,
                                    'reference_id' => $record->id,
                                    'reference_type' => 'outbounds',
                                    'quantity' => $itemModel->quantity,
                                    'stock_before' => $stockBefore,
                                    'stock_after' => $item->current_stock,
                                    'price' => $itemModel->sell_price,
                                    'notes' => 'Void Outbound: ' . $data['void_reason'],
                                    'created_by' => auth()->id() ?? 1
                                ]);
                            }
                        });
                        \Filament\Notifications\Notification::make()->title('Transaksi Berhasil di-Void')->success()->send();
                    })
                    ->visible(fn ($record) => in_array($record->status->value, ['selesai', 'diterima']) && !$record->voided_at),
            ])
            // ->toolbarActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //     ]),
            // ])
            ->columnManager(false);
    }
}
