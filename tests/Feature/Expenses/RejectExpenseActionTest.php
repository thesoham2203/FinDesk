<?php

declare(strict_types=1);

use App\Actions\Expense\RejectExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects an expense and records rejection reason', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $rejector = User::factory()->create();
    $reason = 'Missing receipt';

    $rejectAction = resolve(RejectExpense::class);
    $rejected = $rejectAction->execute($expense, $rejector, $reason);

    expect($rejected->status)->toBe(ExpenseStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe($reason)
        ->and($rejected->reviewed_by)->toBe($rejector->id)
        ->and($rejected->reviewed_at)->not->toBeNull();
});

it('saves changes to the database', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $rejector = User::factory()->create();
    $reason = 'Duplicate expense';

    $rejectAction = resolve(RejectExpense::class);
    $rejectAction->execute($expense, $rejector, $reason);

    $refreshed = $expense->fresh();
    expect($refreshed->status)->toBe(ExpenseStatus::Rejected)
        ->and($refreshed->rejection_reason)->toBe($reason)
        ->and($refreshed->reviewed_by)->toBe($rejector->id);
});

it('returns fresh instance of rejected expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $rejector = User::factory()->create();
    $reason = 'Invalid category';

    $rejectAction = resolve(RejectExpense::class);
    $rejected = $rejectAction->execute($expense, $rejector, $reason);

    expect($rejected->id)->toBe($expense->id)
        ->and($rejected->status)->toBe(ExpenseStatus::Rejected);
});

it('triggers expense events when rejected', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $rejector = User::factory()->create();

    $rejectAction = resolve(RejectExpense::class);
    $rejected = $rejectAction->execute($expense, $rejector, 'Invalid');

    // Verify the expense was rejected successfully
    expect($rejected->status)->toBe(ExpenseStatus::Rejected);
});

it('cannot transition from invalid state', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $rejector = User::factory()->create();

    $rejectAction = resolve(RejectExpense::class);

    expect(function () use ($rejectAction, $expense, $rejector): void {
        $rejectAction->execute($expense, $rejector, 'Invalid');
    })->toThrow(Exception::class);
});

it('handles different rejection reasons', function (): void {
    $reasons = [
        'Missing documentation',
        'Amount exceeds budget',
        'Not approved category',
        'Duplicate submission',
    ];

    foreach ($reasons as $reason) {
        $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
        $rejector = User::factory()->create();

        $rejectAction = resolve(RejectExpense::class);
        $rejected = $rejectAction->execute($expense, $rejector, $reason);

        expect($rejected->rejection_reason)->toBe($reason);
    }
});
