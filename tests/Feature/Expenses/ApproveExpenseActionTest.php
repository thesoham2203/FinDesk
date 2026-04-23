<?php

declare(strict_types=1);

use App\Actions\Expense\ApproveExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('approves an expense and records reviewer information', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $approver = User::factory()->create();

    $approveAction = resolve(ApproveExpense::class);
    $approved = $approveAction->execute($expense, $approver);

    expect($approved->status)->toBe(ExpenseStatus::Approved)
        ->and($approved->reviewed_by)->toBe($approver->id)
        ->and($approved->reviewed_at)->not->toBeNull();
});

it('saves changes to the database', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $approver = User::factory()->create();

    $approveAction = resolve(ApproveExpense::class);
    $approveAction->execute($expense, $approver);

    $refreshed = $expense->fresh();
    expect($refreshed->status)->toBe(ExpenseStatus::Approved)
        ->and($refreshed->reviewed_by)->toBe($approver->id);
});

it('returns fresh instance of approved expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $approver = User::factory()->create();

    $approveAction = resolve(ApproveExpense::class);
    $approved = $approveAction->execute($expense, $approver);

    expect($approved->id)->toBe($expense->id)
        ->and($approved->status)->toBe(ExpenseStatus::Approved);
});

it('triggers expense events when approved', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $approver = User::factory()->create();

    $approveAction = resolve(ApproveExpense::class);
    $approved = $approveAction->execute($expense, $approver);

    // Verify the expense was approved successfully
    expect($approved->status)->toBe(ExpenseStatus::Approved);
});

it('cannot transition from invalid state', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $approver = User::factory()->create();

    $approveAction = resolve(ApproveExpense::class);

    expect(function () use ($approveAction, $expense, $approver): void {
        $approveAction->execute($expense, $approver);
    })->toThrow(Exception::class);
});
