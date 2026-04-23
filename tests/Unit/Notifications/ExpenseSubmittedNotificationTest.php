<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

describe('ExpenseSubmittedNotification', function (): void {
    it('returns database channel', function (): void {
        $expense = Expense::factory()->create();
        $notifiable = User::factory()->create();

        $notification = new ExpenseSubmittedNotification($expense);

        expect($notification->via($notifiable))->toBe(['database']);
    });

    it('formats notification data correctly', function (): void {
        $employee = User::factory()->create(['name' => 'John Employee']);
        $expense = Expense::factory()->create([
            'title' => 'Travel Expenses',
            'amount' => 75000,
            'user_id' => $employee->id,
        ]);
        $notifiable = User::factory()->create();

        $notification = new ExpenseSubmittedNotification($expense);
        $data = $notification->toDatabase($notifiable);

        expect($data['title'])->toBe('New Expense Submitted');
        expect($data['expense_id'])->toBe($expense->id);
        expect($data['action_url'])->toContain(route('expenses.show', $expense));
        expect($data['message'])->toContain('John Employee');
        expect($data['message'])->toContain('Travel Expenses');
    });

    it('includes employee name in message', function (): void {
        $employee = User::factory()->create(['name' => 'Alice Smith']);
        $expense = Expense::factory()->create([
            'title' => 'Conference',
            'user_id' => $employee->id,
        ]);
        $notifiable = User::factory()->create();

        $notification = new ExpenseSubmittedNotification($expense);
        $data = $notification->toDatabase($notifiable);

        expect($data['message'])->toContain('Alice Smith');
        expect($data['message'])->toContain('Conference');
    });

    it('includes submitted date when available', function (): void {
        $expense = Expense::factory()->create();
        $expense->update(['submitted_at' => now()]);

        $notifiable = User::factory()->create();

        $notification = new ExpenseSubmittedNotification($expense);
        $data = $notification->toDatabase($notifiable);

        expect($data['submitted_at'])->not->toBeNull();
    });

    it('can be queued', function (): void {
        $expense = Expense::factory()->create();

        $notification = new ExpenseSubmittedNotification($expense);

        expect($notification instanceof ShouldQueue)->toBeTrue();
    });
});
