<?php

use App\Http\Controllers\OutboundPrintController;
use Illuminate\Support\Facades\Route;

// Filament Portal Panel handles the root route (/)

Route::get('/outbound/{outbound}/print', [OutboundPrintController::class, 'print'])
    ->middleware(['web', 'auth'])
    ->name('outbound.print');
