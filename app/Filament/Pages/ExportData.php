<?php

namespace App\Filament\Pages;

use App\Jobs\ExportDataJob;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExportData extends Page
{

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Export Data';

    protected static ?string $title = 'Export Data';

    protected static ?string $slug = 'export-data';

    protected static \UnitEnum|string|null $navigationGroup = 'Aplikasi';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.export-data';

    // Form state
    public ?string $export_type = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $preset_period = null;

    // Pagination state
    public int $perPage = 5;
    public int $currentPage = 1;

    public function mount(): void
    {
        $this->preset_period = '30';
        $this->applyPreset('30');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilih Laporan & Periode')
                    ->description('Pilih jenis laporan yang ingin diexport dan tentukan periode data.')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Select::make('export_type')
                            ->label('Jenis Laporan')
                            ->placeholder('Pilih jenis laporan...')
                            ->options([
                                'item_stock' => 'Stok Barang — Daftar seluruh barang dengan stok terkini',
                                'inbound' => 'Barang Masuk — Transaksi pembelian/penerimaan barang',
                                'outbound' => 'Barang Keluar — Transaksi penjualan/pengeluaran barang',
                                'stock_movement' => 'Mutasi Stok — Seluruh pergerakan stok masuk & keluar',
                                'profit_loss' => 'Laba Rugi — Ringkasan pendapatan, HPP, dan laba',
                                'stock_opname' => 'Stock Opname — Hasil stock opname & selisih stok',
                            ])
                            ->native(false)
                            ->searchable()
                            ->required(),

                        Select::make('preset_period')
                            ->label('Periode Cepat')
                            ->options([
                                'today' => 'Hari Ini',
                                '7' => '7 Hari Terakhir',
                                '30' => '30 Hari Terakhir',
                                'month' => 'Bulan Ini',
                                'year' => 'Tahun Ini',
                                'custom' => 'Pilih Manual...',
                            ])
                            ->default('30')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, callable $set) {
                                if ($state && $state !== 'custom') {
                                    $this->applyPreset($state);
                                    $set('start_date', $this->start_date);
                                    $set('end_date', $this->end_date);
                                }
                            }),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->disabled(fn (callable $get) => $get('preset_period') !== 'custom')
                            ->dehydrated(),

                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->disabled(fn (callable $get) => $get('preset_period') !== 'custom')
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }

    protected function applyPreset(string $preset): void
    {
        $this->end_date = now()->format('Y-m-d');

        $this->start_date = match ($preset) {
            'today' => now()->format('Y-m-d'),
            '7' => now()->subDays(6)->format('Y-m-d'),
            '30' => now()->subDays(29)->format('Y-m-d'),
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'year' => now()->startOfYear()->format('Y-m-d'),
            default => now()->subDays(29)->format('Y-m-d'),
        };
    }

    public function processExport(): void
    {
        // Validate form
        if (empty($this->export_type)) {
            Notification::make()
                ->title('Pilih Jenis Laporan')
                ->body('Silakan pilih jenis laporan yang ingin diexport terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        if (empty($this->start_date) || empty($this->end_date)) {
            Notification::make()
                ->title('Pilih Periode')
                ->body('Silakan tentukan tanggal mulai dan tanggal akhir.')
                ->warning()
                ->send();
            return;
        }

        if (Carbon::parse($this->start_date)->greaterThan(Carbon::parse($this->end_date))) {
            Notification::make()
                ->title('Periode Tidak Valid')
                ->body('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.')
                ->danger()
                ->send();
            return;
        }

        $labels = [
            'item_stock' => 'Stok Barang',
            'inbound' => 'Barang Masuk',
            'outbound' => 'Barang Keluar',
            'stock_movement' => 'Mutasi Stok',
            'profit_loss' => 'Laba Rugi',
            'stock_opname' => 'Stock Opname',
        ];

        // Dispatch job to queue
        ExportDataJob::dispatch(
            $this->export_type,
            $this->start_date,
            $this->end_date,
            auth()->id(),
        );
    }

    /**
     * Reset pagination when per-page changes.
     */
    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
    }

    /**
     * Go to a specific page.
     */
    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
    }

    /**
     * Delete an export file.
     */
    public function deleteExportFile(string $filename): void
    {
        $filename = basename($filename);
        $path = "exports/{$filename}";

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);

            Notification::make()
                ->title('File Dihapus')
                ->body("File {$filename} berhasil dihapus.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('File Tidak Ditemukan')
                ->body('File yang ingin dihapus tidak ditemukan.')
                ->warning()
                ->send();
        }

        // Recalculate total pages and adjust current page if needed
        $allFiles = $this->getAllExportFiles();
        $totalPages = max(1, (int) ceil(count($allFiles) / $this->perPage));
        if ($this->currentPage > $totalPages) {
            $this->currentPage = $totalPages;
        }
    }

    /**
     * Get all export files sorted by newest first.
     */
    protected function getAllExportFiles(): array
    {
        $files = Storage::disk('local')->files('exports');
        $result = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (!str_ends_with($filename, '.xlsx')) {
                continue;
            }

            $result[] = [
                'name' => $filename,
                'size' => $this->formatFileSize(Storage::disk('local')->size($file)),
                'date' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'timestamp' => Storage::disk('local')->lastModified($file),
                'url' => route('export.download', ['filename' => $filename]),
            ];
        }

        // Sort by newest first (using timestamp for reliable sorting)
        usort($result, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        return $result;
    }

    /**
     * Get paginated export files with metadata.
     */
    public function getExportFiles(): array
    {
        $allFiles = $this->getAllExportFiles();
        $total = count($allFiles);
        $totalPages = max(1, (int) ceil($total / $this->perPage));

        // Clamp current page
        $this->currentPage = max(1, min($this->currentPage, $totalPages));

        $offset = ($this->currentPage - 1) * $this->perPage;
        $items = array_slice($allFiles, $offset, $this->perPage);

        return [
            'items' => $items,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $this->currentPage,
            'perPage' => $this->perPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $this->perPage, $total),
        ];
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
