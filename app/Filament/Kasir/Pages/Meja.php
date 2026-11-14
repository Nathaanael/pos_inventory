<?php

namespace App\Filament\Kasir\Pages;

use App\Enums\Feature;
use App\Models\DiningTable;
use App\Models\Outbound;
use App\Models\StoreSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

/**
 * Pantauan meja untuk kasir (modul FNB): meja kosong/terisi, total tagihan,
 * lama duduk, dan tombol "Kosongkan meja". Hanya ada bila fitur Meja aktif.
 */
class Meja extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Meja';

    protected static ?string $title = 'Meja';

    protected static ?string $slug = 'meja';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.kasir.pages.meja';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        return StoreSetting::hasFeature(Feature::DiningTables) && parent::canAccess();
    }

    public function getHeading(): string
    {
        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTables(): array
    {
        return DiningTable::query()
            ->active()
            ->with(['activeOrders' => fn ($q) => $q->orderBy('created_at')->select(['id', 'dining_table_id', 'transaction_code', 'customer_name', 'total_amount', 'created_at'])])
            ->orderByRaw('LENGTH(number), number')
            ->get()
            ->map(function (DiningTable $table) {
                $orders = $table->activeOrders;
                $since = $orders->first()?->created_at;

                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'occupied' => $orders->isNotEmpty(),
                    'total' => (float) $orders->sum('total_amount'),
                    // waktu mulai duduk (ISO) → lama duduk dihitung di browser
                    'since' => $since?->toIso8601String(),
                    'customer' => $orders->pluck('customer_name')->filter()->unique()->implode(', '),
                    'orders' => $orders->map(fn (Outbound $o) => [
                        'code' => $o->transaction_code,
                        'customer' => $o->customer_name,
                        'total' => (float) $o->total_amount,
                        'time' => $o->created_at->timezone('Asia/Jakarta')->format('H:i'),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /** Pelanggan sudah selesai: meja jadi kosong kembali. */
    public function releaseTable(int $tableId): void
    {
        $table = DiningTable::find($tableId);

        if (! $table) {
            return;
        }

        $table->release();

        Notification::make()
            ->title("Meja {$table->number} dikosongkan")
            ->success()
            ->send();
    }
}
