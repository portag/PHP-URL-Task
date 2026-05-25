<?php

use App\Http\Controllers\TicketCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketCatalogController::class, 'index']);

// Esta pantalla recibe la URL del evento y muestra las entradas agrupadas.
Route::get('/tickets', [TicketCatalogController::class, 'index'])
    ->name('tickets.index');