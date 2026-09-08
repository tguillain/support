<?php

use Functional\Tickets\Http\Controllers\TicketsExportController;
use Functional\Tickets\Rest\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;
use Lomkit\Rest\Facades\Rest;

// Routes here are wrapped in the 'api' middleware group with the 'api'
// prefix by the layer service provider, so everything below lands under
// /api/v1/... behind authentication.
Route::prefix('v1')->middleware('auth')->group(function (): void {
    Rest::resource('tickets', TicketsController::class)
        ->withSoftDeletes();

    Route::get('tickets/exports/resolved-this-month', TicketsExportController::class)
        ->name('tickets.exports.resolved-this-month');
});
