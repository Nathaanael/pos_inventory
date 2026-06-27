<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Kategori Induk')
                    ->badge()
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
                    ->modalHeading('Hapus Kategori')
                    ->modalDescription('Apakah Anda yakin ingin menghapus kategori ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil dihapus')
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->items()->exists() || $record->children()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body('Kategori ini sedang digunakan oleh data Master Barang atau memiliki Sub Kategori.')
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
                        if ($record->items()->exists() || $record->children()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus permanen')
                                ->body('Kategori ini sedang digunakan oleh data Master Barang atau memiliki Sub Kategori.')
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
                        ->modalHeading('Hapus Kategori Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus kategori yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Berhasil dihapus')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->items()->exists() || $record->children()->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Gagal menghapus')
                                        ->body('Salah satu Kategori yang dipilih sedang digunakan oleh data Master Barang atau memiliki Sub Kategori.')
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
