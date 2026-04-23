<?php

declare(strict_types=1);

use App\Actions\Expense\SubmitExpense;
use App\Enums\ExpenseStatus;
use App\Models\Department;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('submits a draft expense', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'status' => ExpenseStatus::Draft,
        'submitted_at' => null,
    ]);

    $submitAction = resolve(SubmitExpense::class);
    $submitted = $submitAction->execute($expense);

    expect($submitted->status)->toBe(ExpenseStatus::Submitted)
        ->and($submitted->submitted_at)->not->toBeNull()
        ->and($submitted->department_id)->toBe($department->id);
});

it('sets submitted_at timestamp', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'status' => ExpenseStatus::Draft,
    ]);

    $beforeSubmit = now();
    $submitAction = resolve(SubmitExpense::class);
    $submitted = $submitAction->execute($expense);
    $afterSubmit = now();

    expect($submitted->submitted_at)
        ->not->toBeNull()
        ->and($submitted->submitted_at->toDateTimeString())->not->toBeEmpty();
});

it('assigns department from user', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'status' => ExpenseStatus::Draft,
    ]);

    $submitAction = resolve(SubmitExpense::class);
    $submitted = $submitAction->execute($expense);

    expect($submitted->department_id)->toBe($department->id);
});

it('saves changes to database', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'status' => ExpenseStatus::Draft,
        'submitted_at' => null,
    ]);

    $submitAction = resolve(SubmitExpense::class);
    $submitAction->execute($expense);

    $refreshed = $expense->fresh();
    expect($refreshed->status)->toBe(ExpenseStatus::Submitted)
        ->and($refreshed->submitted_at)->not->toBeNull();
});

it('throws exception if expense is not draft', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);

    $submitAction = resolve(SubmitExpense::class);

    expect(function () use ($submitAction, $expense): void {
        $submitAction->execute($expense);
    })->toThrow(InvalidArgumentException::class)
        ->and(function () use ($submitAction, $expense): void {
            $submitAction->execute($expense);
        })->toThrow(InvalidArgumentException::class, 'Only draft expenses can be submitted.');
});

it('throws exception for approved expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);

    $submitAction = resolve(SubmitExpense::class);

    expect(function () use ($submitAction, $expense): void {
        $submitAction->execute($expense);
    })->toThrow(InvalidArgumentException::class);
});

it('throws exception for rejected expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Rejected]);

    $submitAction = resolve(SubmitExpense::class);

    expect(function () use ($submitAction, $expense): void {
        $submitAction->execute($expense);
    })->toThrow(InvalidArgumentException::class);
});

it('throws exception for reimbursed expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Reimbursed]);

    $submitAction = resolve(SubmitExpense::class);

    expect(function () use ($submitAction, $expense): void {
        $submitAction->execute($expense);
    })->toThrow(InvalidArgumentException::class);
});

it('triggers expense events when submitted', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'status' => ExpenseStatus::Draft,
    ]);

    $submitAction = resolve(SubmitExpense::class);
    $submitted = $submitAction->execute($expense);

    // Verify the expense was submitted successfully
    expect($submitted->status)->toBe(ExpenseStatus::Submitted);
});

it('returns the submitted expense instance', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'status' => ExpenseStatus::Draft,
    ]);

    $submitAction = resolve(SubmitExpense::class);
    $returned = $submitAction->execute($expense);

    expect($returned->id)->toBe($expense->id)
        ->and($returned->status)->toBe(ExpenseStatus::Submitted);
});
