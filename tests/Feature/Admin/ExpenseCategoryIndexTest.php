<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ExpenseCategoryIndex;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the expense category index component', function (): void {
    $category = ExpenseCategory::factory()->create(['name' => 'Travel']);

    Livewire::test(ExpenseCategoryIndex::class)
        ->assertStatus(200)
        ->assertSee('Travel');
});

it('can search for expense categories', function (): void {
    ExpenseCategory::factory()->create(['name' => 'Travel']);
    ExpenseCategory::factory()->create(['name' => 'Meals']);

    Livewire::test(ExpenseCategoryIndex::class)
        ->set('search', 'Tra')
        ->assertSee('Travel')
        ->assertDontSee('Meals');
});

it('can delete an expense category without expenses', function (): void {
    $category = ExpenseCategory::factory()->create();

    Livewire::test(ExpenseCategoryIndex::class)
        ->call('delete', $category->id)
        ->assertDispatched('flash', type: 'success');

    $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
});

it('cannot delete an expense category with expenses', function (): void {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->create(['category_id' => $category->id]);

    Livewire::test(ExpenseCategoryIndex::class)
        ->call('delete', $category->id)
        ->assertDispatched('flash', type: 'error');

    $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
});
