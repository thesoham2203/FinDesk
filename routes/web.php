<?php

declare(strict_types=1);

use App\Livewire\Admin\ExpenseCategoryForm;
use App\Livewire\Admin\ExpenseCategoryIndex;
use App\Livewire\Admin\TaxRateForm;
use App\Livewire\Admin\TaxRateIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    // Expense Categories
    Route::get('/categories', ExpenseCategoryIndex::class)
        ->name('categories.index');

    Route::get('/categories/create', ExpenseCategoryForm::class)
        ->name('categories.create');

    Route::get('/categories/{category}/edit', ExpenseCategoryForm::class)
        ->name('categories.edit');

    // Tax Rates
    Route::get('/tax-rates', TaxRateIndex::class)
        ->name('tax-rates.index');

    Route::get('/tax-rates/create', TaxRateForm::class)
        ->name('tax-rates.create');

    Route::get('/tax-rates/{taxRate}/edit', TaxRateForm::class)
        ->name('tax-rates.edit');
});

require __DIR__.'/auth.php';
