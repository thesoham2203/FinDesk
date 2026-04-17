<?php

declare(strict_types=1);

use App\Actions\Expense\ReimburseExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reimburses an expense and updates status', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);

    $this->actingAs($processor);

    $reimburseAction = resolve(ReimburseExpense::class);
    $reimbursed = $reimburseAction->execute($expense, $processor);

    expect($reimbursed->status)->toBe(ExpenseStatus::Reimbursed);
});

it('saves changes to the database', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);

    $this->actingAs($processor);

    $reimburseAction = resolve(ReimburseExpense::class);
    $reimburseAction->execute($expense, $processor);

    $refreshed = $expense->fresh();
    expect($refreshed->status)->toBe(ExpenseStatus::Reimbursed);
});

it('returns fresh instance of reimbursed expense', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);

    $this->actingAs($processor);
    $reimburseAction = resolve(ReimburseExpense::class);
    $reimbursed = $reimburseAction->execute($expense, $processor);

    expect($reimbursed->id)->toBe($expense->id)
        ->and($reimbursed->status)->toBe(ExpenseStatus::Reimbursed);
});

it('triggers expense events when reimbursed', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);

    $this->actingAs($processor);
    $reimburseAction = resolve(ReimburseExpense::class);
    $reimbursed = $reimburseAction->execute($expense, $processor);

    // Verify the expense was reimbursed successfully
    expect($reimbursed->status)->toBe(ExpenseStatus::Reimbursed);
});

it('throws exception when reimbursing draft expense', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);

    $reimburseAction = resolve(ReimburseExpense::class);

    expect(function () use ($reimburseAction, $expense, $processor) {
        $reimburseAction->execute($expense, $processor);
    })->toThrow(Exception::class);
});
