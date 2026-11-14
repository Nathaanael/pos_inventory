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
                Action::make('void')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Transaksi')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan transaksi ini? Stok barang akan dikembalikan.')
                    ->modalSubmitActionLabel('Batalkan')
                    ->modalCancelActionLabel('Batal')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('void_reason')->required()->label('Alasan Pembatalan')
                    ])
                    ->action(function ($record, array $data) {
                        app(StockService::class)->voidOutbound($record, $data['void_reason']);
                        \Filament\Notifications\Notification::make()->title('Transaksi Berhasil Dibatalkan')->success()->send();
                    })
                    ->visible(fn ($record) => in_array($record->status->value, ['selesai', 'pending'], true)),
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
                ]),
            ])
            ->columnManager(false);
    }
}
