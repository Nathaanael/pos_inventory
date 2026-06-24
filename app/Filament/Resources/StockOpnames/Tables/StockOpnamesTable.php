<?php

namespace App\Filament\Resources\StockOpnames\Tables;

use App\Filament\Resources\StockOpnames\Schemas\StockOpnameViewForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockOpnamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Sesi')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('session_name')
                    ->label('Nama Sesi')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('total_items_scanned')
                    ->label('Total Scan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_difference')
                    ->label('Total Varian')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_match')
                    ->label('Cocok')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status_item')
                    ->label('Status Item')
                    ->getStateUsing(fn ($record) => $record->total_difference == 0 ? 'Cocok' : ($record->total_difference > 0 ? 'Lebih' : 'Kurang'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Cocok' => 'success',
                        'Kurang' => 'danger',
                        'Lebih' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'approved' => 'Selesai',
                        'draft' => 'Draft',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),
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
                    ->form(StockOpnameViewForm::schema())
                    ->modalAutofocus(false),
                EditAction::make()
                    ->label('Lanjutkan')
                    ->hidden(fn ($record) => $record->status === 'approved'),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
