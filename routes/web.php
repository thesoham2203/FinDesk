<?php

declare(strict_types=1);

use App\Http\Controllers\AttachmentController;
use App\Livewire\Admin\ClientForm;
use App\Livewire\Admin\ClientIndex;
use App\Livewire\Admin\ExpenseCategoryForm;
use App\Livewire\Admin\ExpenseCategoryIndex;
use App\Livewire\Admin\TaxRateForm;
use App\Livewire\Admin\TaxRateIndex;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Expenses\ExpenseDetail;
use App\Livewire\Expenses\ExpenseForm;
use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Expenses\PendingApprovals;
use App\Livewire\Invoices\InvoiceDetail;
use App\Livewire\Invoices\InvoiceForm;
use App\Livewire\Invoices\InvoiceIndex;
use App\Livewire\Notifications\NotificationIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Expense Routes
Route::middleware(['auth', 'verified'])
    ->prefix('expenses')
    ->name('expenses.')
    ->group(function (): void {
        Route::get('/', ExpenseIndex::class)
            ->name('index');

        Route::get('/create', ExpenseForm::class)
            ->name('create');

        Route::get('/{expense}', ExpenseDetail::class)
            ->name('show');

        Route::get('/{expense}/edit', ExpenseForm::class)
            ->name('edit');
        Route::post('/', ExpenseForm::class)->name('store');
    });

Route::middleware(['auth', 'verified', 'role:manager,admin'])
    ->prefix('approvals')
    ->name('approvals.')
    ->group(function (): void {
        Route::get('/', PendingApprovals::class)
            ->name('index');
    });

// Notification Routes
Route::middleware(['auth', 'verified'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', NotificationIndex::class)
            ->name('index');
    });

// Invoice Routes (Admin, Manager, Accountant)
Route::middleware(['auth', 'verified', 'role:admin,manager,accountant'])
    ->prefix('invoices')
    ->name('invoices.')
    ->group(function (): void {
        Route::get('/', InvoiceIndex::class)
            ->name('index');

        Route::get('/create', InvoiceForm::class)
            ->name('create');

        Route::get('/{invoice}', InvoiceDetail::class)
            ->name('show');

        Route::get('/{invoice}/edit', InvoiceForm::class)
            ->name('edit');
    });

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    // Clients
    Route::get('/clients', ClientIndex::class)
        ->name('clients.index');

    Route::get('/clients/create', ClientForm::class)
        ->name('clients.create');

    Route::get('/clients/{client}/edit', ClientForm::class)
        ->name('clients.edit');

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

    // Users
    Route::get('/users', UserIndex::class)
        ->name('users.index');

    Route::get('/users/create', UserForm::class)
        ->name('users.create');

    Route::get('/users/{user}/edit', UserForm::class)
        ->name('users.edit');
});

Route::middleware(['auth', 'verified'])->prefix('attachments')->name('attachments.')->group(function (): void {
    Route::get('/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('download');
});

require __DIR__.'/auth.php';
