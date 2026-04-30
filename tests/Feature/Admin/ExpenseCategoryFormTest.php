<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ExpenseCategoryForm;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the expense category form component', function (): void {
    Livewire::test(ExpenseCategoryForm::class)
        ->assertStatus(200);
});

it('can create an expense category', function (): void {
    Livewire::test(ExpenseCategoryForm::class)
        ->set('name', 'Travel')
        ->set('description', 'Travel expenses')
        ->set('maxAmount', '500')
        ->set('requiresReceipt', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Travel',
        'max_amount' => 50000,
        'requires_receipt' => true,
    ]);
});

it('can update an expense category', function (): void {
    $category = ExpenseCategory::factory()->create();

    Livewire::test(ExpenseCategoryForm::class, ['category' => $category])
        ->set('name', 'Updated Category Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    $category->refresh();
    expect($category->name)->toBe('Updated Category Name');
});

it('validates required fields', function (): void {
    Livewire::test(ExpenseCategoryForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
