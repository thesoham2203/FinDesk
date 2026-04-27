<?php

declare(strict_types=1);

use App\Livewire\Client\Invoices\InvoiceDetail;
use App\Livewire\Client\Invoices\InvoiceIndex;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Client guest routes
Route::middleware('guest:client')->group(function (): void {
    Volt::route('login', 'pages.auth.client-login')
        ->name('login');
});

// Client authenticated routes
Route::middleware('auth:client')->group(function (): void {
    Route::view('dashboard', 'client.dashboard')
        ->name('dashboard');

    Route::get('/invoices', InvoiceIndex::class)
        ->name('invoices.index');

    Route::get('/invoices/{invoice}', InvoiceDetail::class)
        ->name('invoices.show');
});
