<?php

use App\Http\Controllers\OutboundPrintController;
use App\Http\Controllers\StockOpnamePdfController;
use App\Http\Controllers\ReturnTransactionPdfController;
use App\Http\Middleware\RedirectKasirToPortal;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Filament Portal Panel handles the root route (/)

Route::get('/outbound/{outbound}/print', [OutboundPrintController::class, 'print'])
    ->middleware(['web', 'auth'])
    ->name('outbound.print');

Route::get('/stock-opname/{stockOpname}/pdf', [StockOpnamePdfController::class, 'download'])
    ->middleware(['web', 'auth', RedirectKasirToPortal::class])
    ->name('stock-opname.pdf');

Route::get('/return-transaction/{returnTransaction}/pdf', [ReturnTransactionPdfController::class, 'download'])
    ->middleware(['web', 'auth', RedirectKasirToPortal::class])
    ->name('return-transaction.pdf');

Route::get('/exports/download/{filename}', function (string $filename) {
    // Sanitize filename to prevent directory traversal
    $filename = basename($filename);
    $path = "exports/{$filename}";

    if (!Storage::disk('local')->exists($path)) {
        abort(404, 'File tidak ditemukan.');
    }

    return Storage::disk('local')->download($path, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
})->middleware(['web', 'auth', RedirectKasirToPortal::class])->name('export.download');
