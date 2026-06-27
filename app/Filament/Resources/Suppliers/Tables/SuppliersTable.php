<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
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
                EditAction::make()
                    ->label('Ubah')
                    ->modalAlignment('center')
                    ->modalSubmitActionLabel('Simpan')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil diperbarui'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Supplier')
                    ->modalDescription('Apakah Anda yakin ingin menghapus supplier ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil dihapus')
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->inbounds()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body('Supplier ini sedang digunakan dalam transaksi Barang Masuk.')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus Supplier Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus supplier yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Berhasil dihapus')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->inbounds()->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Gagal menghapus')
                                        ->body('Salah satu Supplier yang dipilih sedang digunakan dalam transaksi Barang Masuk.')
                                        ->send();
                                    
                                    $action->cancel();
                                }
                            }
                        }),
                ]),
            ])
            ->columnManager(false);
    }
}
