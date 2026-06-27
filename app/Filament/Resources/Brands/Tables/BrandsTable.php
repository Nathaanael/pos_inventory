<?php

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari...')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                ImageColumn::make('logo')
                    ->label('Logo'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
                    ->modalHeading('Hapus Merk')
                    ->modalDescription('Apakah Anda yakin ingin menghapus merk ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('Berhasil dihapus')
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->items()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body('Merk ini sedang digunakan oleh data Master Barang.')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus Merk Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus merk yang dipilih?')
                        ->modalSubmitActionLabel('Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('Berhasil dihapus')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->items()->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Gagal menghapus')
                                        ->body('Salah satu Merk yang dipilih sedang digunakan oleh data Master Barang.')
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
