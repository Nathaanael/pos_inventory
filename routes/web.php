<?php

use App\Http\Controllers\OutboundPrintController;
use App\Http\Controllers\StockOpnamePdfController;
use Illuminate\Support\Facades\Route;

// Filament Portal Panel handles the root route (/)

Route::get('/outbound/{outbound}/print', [OutboundPrintController::class, 'print'])
    ->middleware(['web', 'auth'])
    ->name('outbound.print');

Route::get('/stock-opname/{stockOpname}/pdf', [StockOpnamePdfController::class, 'download'])
    ->middleware(['web', 'auth'])
    ->name('stock-opname.pdf');
