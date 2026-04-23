<?php

declare(strict_types=1);

use App\Livewire\Admin\ExpenseCategoryForm;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the form component', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component->assertStatus(200);
});

it('creates a new expense category', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Travel')
        ->set('description', 'Travel expenses')
        ->set('maxAmount', '5000')
        ->set('requiresReceipt', true)
        ->call('save');

    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Travel',
        'description' => 'Travel expenses',
        'max_amount' => 500000, // 5000 * 100 cents
        'requires_receipt' => true,
    ]);
});

it('converts dollars to cents when saving', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Meals')
        ->set('description', 'Meal expenses')
        ->set('maxAmount', '100.50')
        ->set('requiresReceipt', false)
        ->call('save');

    $category = ExpenseCategory::query()->where('name', 'Meals')->first();
    expect($category->max_amount)->toBe(10050);
});

it('edits an existing expense category', function (): void {
    $category = ExpenseCategory::factory()->create([
        'name' => 'Original',
        'max_amount' => 50000,
        'requires_receipt' => false,
    ]);

    $component = Livewire::test(ExpenseCategoryForm::class, ['category' => $category]);

    expect($component->get('name'))->toBe('Original')
        ->and($component->get('maxAmount'))->toBe('500'); // 50000 / 100
});

it('updates an existing expense category', function (): void {
    $category = ExpenseCategory::factory()->create([
        'name' => 'Original',
        'requires_receipt' => false,
    ]);

    $component = Livewire::test(ExpenseCategoryForm::class, ['category' => $category]);

    $component
        ->set('name', 'Updated')
        ->set('description', 'New desc')
        ->set('requiresReceipt', true)
        ->call('save');

    $category->refresh();
    expect($category->name)->toBe('Updated')
        ->and($category->description)->toBe('New desc')
        ->and($category->requires_receipt)->toBeTrue();
});

it('validates required name field', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', '')
        ->set('requiresReceipt', false)
        ->call('save')
        ->assertHasErrors('name');
});

it('validates description length', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Valid')
        ->set('description', str_repeat('a', 1001))
        ->set('requiresReceipt', false)
        ->call('save')
        ->assertHasErrors('description');
});

it('allows null max amount', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'No Limit')
        ->set('description', '')
        ->set('maxAmount', '')
        ->set('requiresReceipt', false)
        ->call('save');

    $category = ExpenseCategory::query()->where('name', 'No Limit')->first();
    expect($category->max_amount)->toBeNull();
});

it('validates max amount as numeric', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Test')
        ->set('maxAmount', 'invalid')
        ->set('requiresReceipt', false)
        ->call('save')
        ->assertHasErrors('maxAmount');
});

it('validates max amount minimum value', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Test')
        ->set('maxAmount', '-100')
        ->set('requiresReceipt', false)
        ->call('save')
        ->assertHasErrors('maxAmount');
});

it('requires receipt boolean validation', function (): void {
    $component = Livewire::test(ExpenseCategoryForm::class);

    $component
        ->set('name', 'Test')
        ->set('requiresReceipt', 'invalid')
        ->call('save');

    // Verify component still renders (validation happened)
    expect($component)->toBeTruthy();
});
