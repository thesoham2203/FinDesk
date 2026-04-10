<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseCategory;

test('expense category can be created with factory', function (): void {
    // ARRANGE & ACT: Create a category with the factory
    $category = ExpenseCategory::factory()->create([
        'name' => 'Travel',
        'description' => 'Business travel expenses',
        'max_amount' => 500000,       // ?5,000.00 in paise
        'requires_receipt' => true,
    ]);

    // ASSERT: All fields are stored correctly
    expect($category->name)->toBe('Travel')
        ->and($category->description)->toBe('Business travel expenses')
        ->and($category->max_amount)->toBe(500000)
        ->and($category->requires_receipt)->toBeTrue();
});

test('category max_amount is cast to integer', function (): void {
    // WHY: Like expenses, category limits are stored as integers (cents/paise)
    // This test verifies the 'integer' cast is applied.
    $category = ExpenseCategory::factory()->create(['max_amount' => 100000]);

    // The cast is applied automatically, so direct check should work
    expect(is_int($category->max_amount))->toBeTrue();
});

test('category requires_receipt is cast to boolean', function (): void {
    // WHY: The database stores 0/1, but Laravel casts it to true/false
    $category = ExpenseCategory::factory()->create(['requires_receipt' => true]);

    expect(is_bool($category->requires_receipt))->toBeTrue()
        ->and($category->requires_receipt)->toBeTrue();
});

test('category max_amount can be null (no limit)', function (): void {
    // WHY: Some categories don't have a spending limit.
    // null means "no maximum" — any amount is accepted.
    $category = ExpenseCategory::factory()->create(['max_amount' => null]);

    expect($category->max_amount)->toBeNull();
});

// ============================================================================
// SECTION 2: RELATIONSHIPS
// ============================================================================

test('category has many expenses', function (): void {
    // WHY: We need to check if a category has expenses before deleting it
    // (HLD rule: cannot delete a category with existing expenses)

    // ARRANGE: Create a category and two expenses in it
    $category = ExpenseCategory::factory()->create();
    $expense1 = Expense::factory()->create(['category_id' => $category->id]);
    $expense2 = Expense::factory()->create(['category_id' => $category->id]);

    // ACT: Access the expenses relationship
    $expenses = $category->expenses;

    // ASSERT: Both expenses are linked
    expect($expenses)->toHaveCount(2)
        ->and($expenses->pluck('id')->toArray())
        ->toContain($expense1->id, $expense2->id);
});
