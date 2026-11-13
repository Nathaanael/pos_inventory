<?php

namespace App\Filament\Kasir\Pages;

use App\Enums\TransactionStatus;
use App\Models\Outbound;
use App\Services\ThermalPrintService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Riwayat transaksi milik kasir yang sedang login.
 */
class RiwayatTransaksi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static ?string $title = 'Riwayat Transaksi Saya';

    protected static ?string $slug = 'riwayat';

    protected static ?int $navigationSort = 2;

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Outbound::query()
                    ->where('created_by', auth()->id())
                    ->with(['items.item.unit', 'createdBy'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_code')
                    ->label('Kode')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta'),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Jenis Barang')
                    ->counts('items'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state) => match ($state) {
                        TransactionStatus::PENDING => 'Menunggu Persetujuan',
                        TransactionStatus::COMPLETED => 'Selesai',
                        TransactionStatus::VOIDED => 'Dibatalkan',
                        default => ucfirst($state->value),
                    })
                    ->color(fn (TransactionStatus $state) => match ($state) {
                        TransactionStatus::PENDING => 'warning',
                        TransactionStatus::COMPLETED => 'success',
                        TransactionStatus::VOIDED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('hari_ini')
                    ->label('Hanya hari ini')
                    ->default()
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->recordActions([
                Action::make('print_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Nota')
                    ->modalDescription('Cetak nota langsung ke printer thermal?')
                    ->modalSubmitActionLabel('Cetak')
                    ->action(function (Outbound $record) {
                        try {
                            app(ThermalPrintService::class)->printOutboundReceipt($record);
                            Notification::make()->title('Nota berhasil dicetak!')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal cetak nota')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (Outbound $record) => $record->status === TransactionStatus::COMPLETED),
                Action::make('print_browser')
                    ->label('Lihat Nota')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Outbound $record) => route('outbound.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Outbound $record) => $record->status === TransactionStatus::COMPLETED),
            ])
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Transaksi yang Anda simpan di halaman Kasir akan muncul di sini.');
    }
}
