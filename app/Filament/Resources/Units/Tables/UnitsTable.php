<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari...')
            ->columns([
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
                    ->modalHeading('Hapus Satuan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus satuan ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil dihapus')
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->items()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body('Satuan ini sedang digunakan oleh data Master Barang.')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
                RestoreAction::make()
                    ->label('Pulihkan')
                    ->modalSubmitActionLabel('Pulihkan')
                    ->modalCancelActionLabel('Batal')
                    ->visible(fn ($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->modalSubmitActionLabel('Hapus Permanen')
                    ->modalCancelActionLabel('Batal')
                    ->before(function ($record, ForceDeleteAction $action) {
                        if ($record->items()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus permanen')
                                ->body('Satuan ini sedang digunakan oleh data Master Barang.')
                                ->send();
                            
                            $action->cancel();
                        }
                    })
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus Satuan Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus satuan yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Berhasil dihapus')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->items()->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Gagal menghapus')
                                        ->body('Salah satu Satuan yang dipilih sedang digunakan oleh data Master Barang.')
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
