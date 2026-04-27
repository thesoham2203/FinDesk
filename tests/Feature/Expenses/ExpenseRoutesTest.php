<?php

declare(strict_types=1);

/**
 * ExpenseRoutesTest
 *
 * WHAT: Smoke tests for the authenticated expense route group.
 *
 * WHY: Ensures routes are accessible and correctly wired.
 */

use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

it('exposes the expense routes to an authenticated verified user', function (): void {
    // Create a department
    $department = Department::factory()->create();

    // Create a user in that department
    $user = User::factory()->create([
        'department_id' => $department->id,
        'email_verified_at' => now(), // ✅ ensure verified
    ]);

    // Create category (NO department_id)
    $category = ExpenseCategory::factory()->create();

    // Create expense
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'category_id' => $category->id,
        'date' => fake()->date(),
    ]);

    // Index
    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk();

    // Create
    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertOk();

    // Show
    $this->actingAs($user)
        ->get(route('expenses.show', $expense))
        ->assertOk();

    // Edit
    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertOk();
});
