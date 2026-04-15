<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Enums\ExpenseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays empty form for creating new expense', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->assertSeeText('Title')
        ->assertSeeText('Amount')
        ->assertSeeText('Category');
});

it('validates required fields on save', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->set('title', '')
        ->set('amount', '')
        ->set('categoryId', '')
        ->call('save')
        ->assertHasErrors(['title', 'amount', 'categoryId']);
});

it('validates amount must be greater than 0', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->set('title', 'Test')
        ->set('amount', '0')
        ->set('categoryId', '999')
        ->call('save')
        ->assertHasErrors('amount');
});

it('creates new expense successfully', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['requires_receipt' => false]);

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->set('title', 'Flight Ticket')
        ->set('description', 'Business trip')
        ->set('amount', '5000')
        ->set('categoryId', (string) $category->id)
        ->set('currency', 'INR')
        ->set('date', now()->format('Y-m-d'))
        ->call('save');

    expect(Expense::where('title', 'Flight Ticket')->first())
        ->not->toBeNull()
        ->amount->toBe(500000) // 5000 * 100 = 500000 paise
        ->status->toBe(ExpenseStatus::Draft);
});

it('loads existing draft expense for editing', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Expense',
        'amount' => 100000,
    ]);

    Livewire::actingAs($user)
        ->test('expenses.expense-form', ['expense' => $expense])
        ->assertSet('title', 'Old Expense')
        ->assertSet('amount', '1000');
});

it('prevents editing of non-draft expenses', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->submitted()->create(['user_id' => $user->id]);

    expect(fn () => 
        Livewire::actingAs($user)
            ->test('expenses.expense-form', ['expense' => $expense])
    )->toThrow(Illuminate\View\ViewException::class); // Livewire wraps InvalidArgumentException
});

it('updates existing expense successfully', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('expenses.expense-form', ['expense' => $expense])
        ->set('title', 'Updated Title')
        ->set('amount', '2000')
        ->set('categoryId', (string) $category->id)
        ->set('date', now()->format('Y-m-d'))
        ->call('save');

    expect($expense->fresh())
        ->title->toBe('Updated Title')
        ->amount->toBe(200000);
});

it('submits expense when saving with andSubmit flag', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->set('title', 'Urgent Expense')
        ->set('amount', '1000')
        ->set('categoryId', (string) $category->id)
        ->set('currency', 'INR')
        ->set('date', now()->format('Y-m-d'))
        ->call('save', true);

    expect(Expense::where('title', 'Urgent Expense')->first())
        ->status->toBe(ExpenseStatus::Submitted);
});

it('updates category requirements when category is set', function () {
    $user = User::factory()->create();
    $categoryWithReceipt = ExpenseCategory::factory()->create(['requires_receipt' => true]);
    $categoryNoReceipt = ExpenseCategory::factory()->create(['requires_receipt' => false]);

    Livewire::actingAs($user)
        ->test('expenses.expense-form')
        ->set('categoryId', (string) $categoryWithReceipt->id)
        ->assertSet('requiresReceipt', true)
        ->set('categoryId', (string) $categoryNoReceipt->id)
        ->assertSet('requiresReceipt', false);
});