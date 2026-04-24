<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpensePartiallyPaid;
use Illuminate\Contracts\Queue\ShouldQueue;

test('expense partially paid notification can be created', function (): void {
    $expense = Expense::factory()->create();
    $approver = User::factory()->create();

    $notification = new ExpensePartiallyPaid($expense, $approver);

    expect($notification)->toBeInstanceOf(ExpensePartiallyPaid::class);
});

test('expense partially paid notification uses database channel', function (): void {
    $expense = Expense::factory()->create();
    $approver = User::factory()->create();

    $notification = new ExpensePartiallyPaid($expense, $approver);

    expect($notification->via(null))->toContain('database');
});

test('expense partially paid notification should queue', function (): void {
    $expense = Expense::factory()->create();
    $approver = User::factory()->create();

    $notification = new ExpensePartiallyPaid($expense, $approver);

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});

test('expense partially paid notification formats data correctly', function (): void {
    $expense = Expense::factory()->create([
        'title' => 'Office Supplies',
        'amount' => 50000,
    ]);

    $approver = User::factory()->create(['name' => 'Jane Approver']);

    $notification = new ExpensePartiallyPaid($expense, $approver);
    $data = $notification->toDatabase(null);

    expect($data['title'])->toBe('Expense Partially Paid');
    expect($data['message'])->toContain('Your expense "Office Supplies" for ₹ 500.00 was Partially paid by Jane Approver');
    expect($data['expense_id'])->toBe($expense->id);
    expect($data['action_url'])->toContain(route('expenses.show', $expense));
    expect($data['approver_name'])->toBe('Jane Approver');
});

test('expense partially paid notification includes approved_at when present', function (): void {
    $now = now();
    $expense = Expense::factory()->create([
        'title' => 'Travel Expenses',
        'amount' => 75000,
        'reviewed_at' => $now,
    ]);

    $approver = User::factory()->create(['name' => 'John Manager']);

    $notification = new ExpensePartiallyPaid($expense, $approver);
    $data = $notification->toDatabase(null);

    expect($data['approved_at'])->toBe($now->toDateTimeString());
});

test('expense partially paid notification handles null approved_at', function (): void {
    $expense = Expense::factory()->create([
        'title' => 'Meeting Expenses',
        'amount' => 25000,
        'reviewed_at' => null,
    ]);

    $approver = User::factory()->create(['name' => 'Sarah Admin']);

    $notification = new ExpensePartiallyPaid($expense, $approver);
    $data = $notification->toDatabase(null);

    expect($data['approved_at'])->toBeNull();
});
