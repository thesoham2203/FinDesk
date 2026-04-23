<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Rules\CategoryNotInUse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CategoryNotInUse Rule', function (): void {
    it('passes when category has no expenses', function (): void {
        $category = ExpenseCategory::factory()->create();
        $rule = new CategoryNotInUse();

        $failed = false;
        $rule->validate('category_id', $category->id, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails when category has expenses', function (): void {
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->count(2)->create(['category_id' => $category->id]);

        $rule = new CategoryNotInUse();
        $failed = false;

        $rule->validate('category_id', $category->id, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails when category does not exist', function (): void {
        $rule = new CategoryNotInUse();
        $failed = false;

        $rule->validate('category_id', 99999, function () use (&$failed): void {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('implements validation rule interface', function (): void {
        $rule = new CategoryNotInUse();

        expect($rule)->toBeInstanceOf(ValidationRule::class);
    });
});
