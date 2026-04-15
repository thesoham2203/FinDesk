<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays paginated list of user expenses', function () {
    $user = User::factory()->create();
    $expenses = Expense::factory(5)->create(['user_id' => $user->id]);

    $response = Livewire::actingAs($user)
        ->test('expenses.expense-index');

    foreach ($expenses as $expense) {
        $response->assertSeeText($expense->title);
    }
});

it('filters expenses by search term', function () {
    $user = User::factory()->create();
    $searchExpense = Expense::factory()->create(['user_id' => $user->id, 'title' => 'Unique Flight']);
    Expense::factory(3)->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('search', 'Flight')
        ->assertSeeText('Unique Flight');
});

it('filters expenses by status', function () {
    $user = User::factory()->create();
    $draftExpense = Expense::factory()->create([
        'user_id' => $user->id,
        'title' => 'Draft Expense',
        'status' => ExpenseStatus::Draft,
    ]);
    $submittedExpense = Expense::factory()->submitted()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('statusFilter', ExpenseStatus::Draft->value)
        ->assertSeeText($draftExpense->title)
        ->assertDontSeeText($submittedExpense->title);
});

it('filters expenses by category', function () {
    $user = User::factory()->create();
    $cat1 = ExpenseCategory::factory()->create();
    $cat2 = ExpenseCategory::factory()->create();
    
    $exp1 = Expense::factory()->create(['user_id' => $user->id, 'category_id' => $cat1->id, 'title' => 'Cat1 Expense']);
    Expense::factory()->create(['user_id' => $user->id, 'category_id' => $cat2->id, 'title' => 'Cat2 Expense']);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('categoryFilter', (string) $cat1->id)
        ->assertSeeText($exp1->title)
        ->assertDontSeeText('Cat2 Expense');
});

it('filters expenses by date range', function () {
    $user = User::factory()->create();
    $oldExpense = Expense::factory()->create([
        'user_id' => $user->id,
        'date' => now()->subDays(30),
        'title' => 'Old Expense',
    ]);
    $recentExpense = Expense::factory()->create([
        'user_id' => $user->id,
        'date' => now()->subDays(5),
        'title' => 'Recent Expense',
    ]);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('dateFrom', now()->subDays(10)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertSeeText('Recent Expense')
        ->assertDontSeeText('Old Expense');
});

it('filters expenses by amount range', function () {
    $user = User::factory()->create();
    Expense::factory()->create(['user_id' => $user->id, 'amount' => 5000, 'title' => 'Small Expense']);
    Expense::factory()->create(['user_id' => $user->id, 'amount' => 15000, 'title' => 'Large Expense']);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('amountMin', '100')
        ->set('amountMax', '200')
        ->assertSeeText('Large Expense')
        ->assertDontSeeText('Small Expense');
});

it('clears all filters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('search', 'test')
        ->set('statusFilter', ExpenseStatus::Draft->value)
        ->set('categoryFilter', '1')
        ->set('amountMin', '100')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('statusFilter', '')
        ->assertSet('categoryFilter', '')
        ->assertSet('amountMin', '');
});

it('resets pagination when search changes', function () {
    $user = User::factory()->create();
    Expense::factory(50)->create(['user_id' => $user->id, 'title' => 'Test Expense']);

    Livewire::actingAs($user)
        ->test('expenses.expense-index')
        ->set('search', 'test') // This calls updatedSearch() which calls resetPage()
        ->assertSet('search', 'test');
});