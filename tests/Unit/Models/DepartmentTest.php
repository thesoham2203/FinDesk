<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Expense;
use App\Models\User;

// ============================================================================
// SECTION 1: BASIC CREATION & ATTRIBUTES
// ============================================================================

test('department can be created with factory', function (): void {
    // ARRANGE & ACT: Create a department
    $department = Department::factory()->create([
        'name' => 'Engineering',
        'description' => 'Software development team',
        'monthly_budget' => 5000000,  // ₹50,000.00 in paise
    ]);

    // ASSERT: All fields are stored correctly
    expect($department->name)->toBe('Engineering')
        ->and($department->description)->toBe('Software development team')
        ->and($department->monthly_budget)->toBe(5000000);
});

test('department monthly_budget is cast to integer', function (): void {
    // WHY: Budget is stored in cents/paise (same as expense amounts)
    $department = Department::factory()->create(['monthly_budget' => 10000000]);

    expect($department->monthly_budget)->toBeInt()
        ->and($department->monthly_budget)->toBe(10000000);
});

// ============================================================================
// SECTION 2: RELATIONSHIPS — USERS
// ============================================================================

test('department has many users', function (): void {
    // ARRANGE: Create a department and assign users to it
    $department = Department::factory()->create();
    $user1 = User::factory()->create(['department_id' => $department->id]);
    $user2 = User::factory()->create(['department_id' => $department->id]);

    // ACT: Access the users relationship
    $users = $department->users;

    // ASSERT: Both users are returned
    expect($users)->toHaveCount(2)
        ->and($users->pluck('id')->toArray())
        ->toContain($user1->id, $user2->id);
});

// ============================================================================
// SECTION 3: RELATIONSHIPS — EXPENSES (HasManyThrough)
// ============================================================================

test('department has many expenses through users', function (): void {
    // WHY THIS TEST: This tests the HasManyThrough relationship.
    // The chain is: Department → Users → Expenses
    // A department doesn't have a direct expenses table link.
    // Instead, Laravel goes through the users table to find expenses.

    // ARRANGE: Create a department, a user in it, and expenses for that user
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense1 = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
    ]);
    $expense2 = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
    ]);

    // ACT: Access the expenses relationship (goes through users)
    $expenses = $department->expenses;

    // ASSERT: Both expenses are found via the HasManyThrough chain
    expect($expenses)->toHaveCount(2)
        ->and($expenses->pluck('id')->toArray())
        ->toContain($expense1->id, $expense2->id);
});

test('department expenses excludes other departments', function (): void {
    // WHY THIS TEST: Make sure the relationship doesn't leak expenses
    // from other departments.

    // ARRANGE: Two departments, each with their own user and expense
    $dept1 = Department::factory()->create();
    $dept2 = Department::factory()->create();

    $user1 = User::factory()->create(['department_id' => $dept1->id]);
    $user2 = User::factory()->create(['department_id' => $dept2->id]);

    Expense::factory()->create(['user_id' => $user1->id, 'department_id' => $dept1->id]);
    Expense::factory()->create(['user_id' => $user2->id, 'department_id' => $dept2->id]);

    // ACT: Get expenses for dept1 only
    $dept1Expenses = $dept1->expenses;

    // ASSERT: Only dept1's expense is returned
    expect($dept1Expenses)->toHaveCount(1)
        ->and($dept1Expenses->first()->department_id)->toBe($dept1->id);
});

// ============================================================================
// SECTION 4: FORMATTED ACCESSOR
// ============================================================================
// NOTE: Accessor tests (formattedBudget) removed - private method can't be tested
// TODO: Implement as public accessor or property before testing in UI
