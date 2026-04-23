<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Rules\CategoryNotInUse;

test('category without expenses passes validation', function (): void {
    $category = ExpenseCategory::factory()->create();
    $rule = new CategoryNotInUse();

    $fails = [];
    $rule->validate('category_id', $category->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('category with expenses fails validation', function (): void {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->create(['category_id' => $category->id]);

    $rule = new CategoryNotInUse();
    $fails = [];
    $rule->validate('category_id', $category->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
    expect($fails[0])->toContain('cannot be deleted');
});

test('category with multiple expenses fails validation with correct count', function (): void {
    $category = ExpenseCategory::factory()->create();
    Expense::factory(5)->create(['category_id' => $category->id]);

    $rule = new CategoryNotInUse();
    $fails = [];
    $rule->validate('category_id', $category->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails);
    expect($fails[0])->toContain('5 expenses');
});
