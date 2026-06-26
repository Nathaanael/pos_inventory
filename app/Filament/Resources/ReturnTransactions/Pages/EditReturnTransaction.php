<?php

namespace App\Filament\Resources\ReturnTransactions\Pages;

use App\Filament\Resources\ReturnTransactions\ReturnTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnTransaction extends EditRecord
{
    protected static string $resource = ReturnTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('return-transaction.pdf', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
