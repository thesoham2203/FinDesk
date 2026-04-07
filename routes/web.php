<?php

declare(strict_types=1);

use App\Livewire\Expenses\ExpenseForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('expenses')->name('expenses.')->group(function (): void {
        Route::get('/create', ExpenseForm::class)->name('create');
    });
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
