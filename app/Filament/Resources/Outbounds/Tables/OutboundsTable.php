<?php

namespace App\Filament\Resources\Outbounds\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use App\Filament\Resources\Outbounds\Schemas\OutboundViewForm;
use App\Services\StockService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->searchable(),
                TextColumn::make('price_type_applied')
                    ->label('Tipe Harga')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'selesai' => 'Selesai',
                        'pending' => 'Pending',
                        'voided' => 'Void',
                        default => ucfirst((string) ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state)),
                    })
                    ->color(fn ($state) => match ($state instanceof \App\Enums\TransactionStatus ? $state->value : $state) {
                        'selesai' => 'success',
                        'pending' => 'warning',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Nominal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_cogs')
                    ->label('Total HPP')
                    ->numeric()
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
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(OutboundViewForm::schema())
                    ->modalAutofocus(false),
                Action::make('print_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Nota')
                    ->modalDescription('Cetak nota langsung ke printer thermal?')
                    ->modalSubmitActionLabel('Cetak')
                    ->modalCancelActionLabel('Batal')
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
                // EditAction::make()
                //     ->label('Ubah')
                //     ->visible(fn ($record) => $record->status->value === 'pending'),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Barang Keluar')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi ini? Stok barang akan berkurang secara otomatis.')
                    ->modalSubmitActionLabel('Setujui')
                    ->modalCancelActionLabel('Batal')
                    ->action(function ($record) {
                        app(StockService::class)->approveOutbound($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Transaksi Berhasil Disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status->value === 'pending'),
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
                        \Filament\Forms\Components\Textarea::make('void_reason')->required()->label('Alasan Pembatalan')
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->voidOutbound($record, $data['void_reason']);
                        \Filament\Notifications\Notification::make()->title('Transaksi Berhasil Dibatalkan')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status->value === 'pending'),
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

                                app(StockService::class)->approveOutbound($record, 'Barang Keluar (Setujui Massal)');
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
