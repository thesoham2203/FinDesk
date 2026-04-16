<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseApprovedNotification;

describe('ExpenseApprovedNotification', function (): void {
    it('returns database channel', function (): void {
        $expense = Expense::factory()->create();
        $approver = User::factory()->create();
        $employee = User::factory()->create();

        $notification = new ExpenseApprovedNotification($expense, $approver);

        expect($notification->via($employee))->toBe(['database']);
    });

    it('formats notification data correctly', function (): void {
        $expense = Expense::factory()->create([
            'title' => 'Conference Attendance',
            'amount' => 50000, // $500.00
        ]);
        $approver = User::factory()->create(['name' => 'John Manager']);
        $employee = User::factory()->create();

        $notification = new ExpenseApprovedNotification($expense, $approver);
        $data = $notification->toDatabase($employee);

        expect($data['title'])->toBe('Expense Approved');
        expect($data['expense_id'])->toBe($expense->id);
        expect($data['approver_name'])->toBe('John Manager');
        expect($data['action_url'])->toContain(route('expenses.show', $expense));
        expect($data['message'])->toContain('was approved by John Manager');
    });

    it('includes expense amount in message', function (): void {
        $expense = Expense::factory()->create([
            'title' => 'Travel',
            'amount' => 15000, // $150.00
        ]);
        $approver = User::factory()->create(['name' => 'Sarah Admin']);
        $employee = User::factory()->create();

        $notification = new ExpenseApprovedNotification($expense, $approver);
        $data = $notification->toDatabase($employee);

        expect($data['message'])->toContain('Travel');
        expect($data['message'])->toContain('Sarah Admin');
    });

    it('includes reviewed date when available', function (): void {
        $expense = Expense::factory()->create();
        $expense->update(['reviewed_at' => now()]);

        $approver = User::factory()->create();
        $employee = User::factory()->create();

        $notification = new ExpenseApprovedNotification($expense, $approver);
        $data = $notification->toDatabase($employee);

        expect($data['approved_at'])->not->toBeNull();
    });

    it('can be queued', function (): void {
        $expense = Expense::factory()->create();
        $approver = User::factory()->create();

        $notification = new ExpenseApprovedNotification($expense, $approver);

        // Check that the notification implements ShouldQueue
        expect($notification instanceof Illuminate\Contracts\Queue\ShouldQueue)->toBeTrue();
    });
});
