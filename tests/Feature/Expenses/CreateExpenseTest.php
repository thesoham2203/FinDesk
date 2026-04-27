<?php

declare(strict_types=1);

use App\Actions\CreateExpenseAction;
use App\Enums\ExpenseStatus;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('creates an expense with correct amount in paise', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $category = ExpenseCategory::factory()->create(['requires_receipt' => false]);

    $expense = resolve(CreateExpenseAction::class)->execute(
        user: $user,
        title: 'Flight to Mumbai',
        description: 'Client visit',
        amount: 19999,
        currency: 'INR',
        categoryId: $category->id,
        receiptPath: null,
    );

    expect($expense)->toBeInstanceOf(Expense::class);
    expect($expense->amount)->toBe(19999);
    expect($expense->status)->toBe(ExpenseStatus::Draft);
    expect($expense->user_id)->toBe($user->id);
});

it('creates an activity log entry when expense is created', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $category = ExpenseCategory::factory()->create();

    resolve(CreateExpenseAction::class)->execute(
        user: $user,
        title: 'Test expense',
        description: '',
        amount: 5000,
        currency: 'INR',
        categoryId: $category->id,
        receiptPath: null,
    );

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'subject_type' => Expense::class,
        'description' => 'Expense created',
    ]);
});

it('stores receipt path when provided', function (): void {
    Storage::fake('private');

    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $category = ExpenseCategory::factory()->create(['requires_receipt' => true]);

    $expense = resolve(CreateExpenseAction::class)->execute(
        user: $user,
        title: 'Meal',
        description: '',
        amount: 5000,
        currency: 'INR',
        categoryId: $category->id,
        receiptPath: 'receipts/test.jpg',
    );

    expect($expense->receipt_path)->toBe('receipts/test.jpg');
});

it('sets status to Draft on creation', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $category = ExpenseCategory::factory()->create();

    $expense = resolve(CreateExpenseAction::class)->execute(
        user: $user,
        title: 'Test',
        description: '',
        amount: 1000,
        currency: 'INR',
        categoryId: $category->id,
        receiptPath: null,
    );

    expect($expense->status)->toBe(ExpenseStatus::Draft);
});

it('sets department_id from user department', function (): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $category = ExpenseCategory::factory()->create();

    $expense = resolve(CreateExpenseAction::class)->execute(
        user: $user,
        title: 'Test',
        description: '',
        amount: 5000,
        currency: 'INR',
        categoryId: $category->id,
        receiptPath: null,
    );

    expect($expense->department_id)->toBe($department->id);
});
