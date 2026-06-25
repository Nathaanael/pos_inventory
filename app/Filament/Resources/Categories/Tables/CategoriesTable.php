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
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Kategori Induk')
                    ->badge()
                    ->searchable(),
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
                EditAction::make()
                    ->modalAlignment('center'),
                DeleteAction::make()
                    ->before(function ($record, DeleteAction $action) {
                        if ($record->items()->exists() || $record->children()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body('Kategori ini sedang digunakan oleh data Master Item atau memiliki Sub Kategori.')
                                ->send();
                            
                            $action->cancel();
                        }
                    }),
                RestoreAction::make()
                    ->visible(fn ($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->before(function ($record, ForceDeleteAction $action) {
                        if ($record->items()->exists() || $record->children()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal menghapus permanen')
                                ->body('Kategori ini sedang digunakan oleh data Master Item atau memiliki Sub Kategori.')
                                ->send();
                            
                            $action->cancel();
                        }
                    })
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            foreach ($records as $record) {
                                if ($record->items()->exists() || $record->children()->exists()) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Gagal menghapus')
                                        ->body('Salah satu Kategori yang dipilih sedang digunakan oleh data Master Item atau memiliki Sub Kategori.')
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
