<?php

use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Livewire\TicketList;
use Illuminate\Support\Facades\Route;

// Routes here are wrapped in the 'web' middleware group by the layer
// service provider.
Route::middleware('auth')->group(function (): void {
    Route::livewire('/tickets', TicketList::class)->name('tickets.index');
    Route::livewire('/tickets/create', TicketForm::class)->name('tickets.create');
    Route::livewire('/tickets/{ticket}/edit', TicketForm::class)->name('tickets.edit');
});
