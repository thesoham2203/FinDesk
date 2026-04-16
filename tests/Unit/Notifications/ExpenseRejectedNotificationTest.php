<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseRejectedNotification;

describe('ExpenseRejectedNotification', function (): void {
    it('returns database channel', function (): void {
        $expense = Expense::factory()->create();
        $rejector = User::factory()->create();
        $employee = User::factory()->create();

        $notification = new ExpenseRejectedNotification($expense, $rejector, 'Missing receipt');

        expect($notification->via($employee))->toBe(['database']);
    });

    it('formats notification data correctly', function (): void {
        $expense = Expense::factory()->create([
            'title' => 'Office Supplies',
            'amount' => 30000,
        ]);
        $rejector = User::factory()->create(['name' => 'Manager Steve']);
        $reason = 'Missing receipt documentation';
        $employee = User::factory()->create();

        $notification = new ExpenseRejectedNotification($expense, $rejector, $reason);
        $data = $notification->toDatabase($employee);

        expect($data['title'])->toBe('Expense Rejected');
        expect($data['expense_id'])->toBe($expense->id);
        expect($data['rejector_name'])->toBe('Manager Steve');
        expect($data['reason'])->toBe($reason);
        expect($data['action_url'])->toContain(route('expenses.show', $expense));
    });

    it('includes rejection reason in message', function (): void {
        $expense = Expense::factory()->create(['title' => 'Hotel']);
        $rejector = User::factory()->create(['name' => 'Admin Alex']);
        $reason = 'Amount exceeds approval limit';
        $employee = User::factory()->create();

        $notification = new ExpenseRejectedNotification($expense, $rejector, $reason);
        $data = $notification->toDatabase($employee);

        expect($data['message'])->toContain($reason);
        expect($data['message'])->toContain('Admin Alex');
    });

    it('includes rejected date when available', function (): void {
        $expense = Expense::factory()->create();
        $expense->update(['reviewed_at' => now()]);

        $rejector = User::factory()->create();
        $employee = User::factory()->create();

        $notification = new ExpenseRejectedNotification($expense, $rejector, 'Invalid category');
        $data = $notification->toDatabase($employee);

        expect($data['rejected_at'])->not->toBeNull();
    });

    it('can be queued', function (): void {
        $expense = Expense::factory()->create();
        $rejector = User::factory()->create();

        $notification = new ExpenseRejectedNotification($expense, $rejector, 'Test reason');

        expect($notification instanceof Illuminate\Contracts\Queue\ShouldQueue)->toBeTrue();
    });
});
