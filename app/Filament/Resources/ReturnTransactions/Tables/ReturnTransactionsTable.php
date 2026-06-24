<?php

namespace App\Filament\Resources\ReturnTransactions\Tables;

use App\Filament\Resources\ReturnTransactions\Schemas\ReturnTransactionViewForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReturnTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction('view')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('return_code')
                    ->label('Kode Retur')
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
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'pending' => 'Pending',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Harga Retur')
                    ->numeric()
                    ->prefix('Rp ')
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
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->form(ReturnTransactionViewForm::schema())
                    ->modalAutofocus(false),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Retur')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui retur ini?')
                    ->hidden(fn ($record) => $record->status !== 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Retur')
                    ->modalDescription('Apakah Anda yakin ingin menolak retur ini?')
                    ->hidden(fn ($record) => $record->status !== 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'ditolak']);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Retur Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui semua retur yang dipilih?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn ($record) => $record->update(['status' => 'approved']));
                        })
                        ->deselectRecordsAfterCompletion(),
                    \Filament\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Retur Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menolak semua retur yang dipilih?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn ($record) => $record->update(['status' => 'ditolak']));
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
