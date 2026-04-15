<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Expense;
use App\Rules\ExpenseWithinBudget;

// write tests for ExpenseWithinBudget rule
test('department with no expenses passes validation', function () {
    $department = Department::factory()->create(['monthly_budget' => 100000]); // 1000.00
    $rule = new ExpenseWithinBudget(departmentId: $department->id, amount: 50000); // 500.00

    $fails = [];
    $rule->validate('amount', 50000, function ($message) use (&$fails) {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('expense exceeding budget fails validation', function () {
    $department = Department::factory()->create(['monthly_budget' => 100000]); // 1000.00
    Expense::factory()->create([
        'department_id' => $department->id,
        'amount' => 80000, // 800.00
        'status' => 'approved',
        'date' => now(), // Set date to current month/year so rule finds it
    ]);

    $rule = new ExpenseWithinBudget(departmentId: $department->id, amount: 50000); // 500.00 more

    $fails = [];
    $rule->validate('amount', 50000, function ($message) use (&$fails) {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
    expect($fails[0])->toContain('exceed');
});

test('expense within budget passes validation', function () {
    $department = Department::factory()->create(['monthly_budget' => 100000]); // 1000.00
    Expense::factory()->create([
        'department_id' => $department->id,
        'amount' => 30000, // 300.00
        'status' => 'approved',
        'date' => now(), // Set date to current month/year so rule finds it
    ]);

    $rule = new ExpenseWithinBudget(departmentId: $department->id, amount: 40000); // 400.00 more = 700.00 total

    $fails = [];
    $rule->validate('amount', 40000, function ($message) use (&$fails) {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});
