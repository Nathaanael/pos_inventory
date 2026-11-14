<?php

namespace App\Filament\Resources\Inbounds\Tables;

use App\Filament\Resources\Inbounds\Schemas\InboundViewForm;
use App\Models\Inbound;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                        app(StockService::class)->approveInbound($record);

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
                        app(StockService::class)->voidInbound($record, $data['void_reason']);

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

                                app(StockService::class)->approveInbound($record, 'Barang Masuk (Setujui Massal)');
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