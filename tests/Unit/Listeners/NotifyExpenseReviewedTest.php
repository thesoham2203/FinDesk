<?php

declare(strict_types=1);

use App\Events\ExpenseApproved;
use App\Events\ExpenseRejected;
use App\Listeners\NotifyExpenseReviewed;
use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseApprovedNotification;
use App\Notifications\ExpenseRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

describe('NotifyExpenseReviewed Listener', function (): void {
    it('sends approved notification when expense is approved', function (): void {
        Notification::fake();

        $employee = User::factory()->create();
        $approver = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $employee->id]);

        $event = new ExpenseApproved($expense, $approver);
        $listener = new NotifyExpenseReviewed();

        $listener->handle($event);

        Notification::assertSentTo(
            [$employee],
            ExpenseApprovedNotification::class
        );
    });

    it('sends rejected notification when expense is rejected', function (): void {
        Notification::fake();

        $employee = User::factory()->create();
        $rejector = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $employee->id]);
        $reason = 'Missing receipt';

        $event = new ExpenseRejected($expense, $rejector, $reason);
        $listener = new NotifyExpenseReviewed();

        $listener->handle($event);

        Notification::assertSentTo(
            [$employee],
            ExpenseRejectedNotification::class
        );
    });

    it('passes correct data to approved notification', function (): void {
        Notification::fake();

        $employee = User::factory()->create(['name' => 'Jane Employee']);
        $approver = User::factory()->create(['name' => 'Bob Approver']);
        $expense = Expense::factory()->create(['user_id' => $employee->id]);

        $event = new ExpenseApproved($expense, $approver);
        $listener = new NotifyExpenseReviewed();

        $listener->handle($event);

        Notification::assertSentTo(
            [$employee],
            ExpenseApprovedNotification::class,
            fn (ExpenseApprovedNotification $notification): bool => $notification->expense->id === $expense->id &&
                $notification->approver->id === $approver->id
        );
    });

    it('passes correct data to rejected notification', function (): void {
        Notification::fake();

        $employee = User::factory()->create(['name' => 'Jane Employee']);
        $rejector = User::factory()->create(['name' => 'Alice Rejector']);
        $expense = Expense::factory()->create(['user_id' => $employee->id]);
        $reason = 'Invalid category';

        $event = new ExpenseRejected($expense, $rejector, $reason);
        $listener = new NotifyExpenseReviewed();

        $listener->handle($event);

        Notification::assertSentTo(
            [$employee],
            ExpenseRejectedNotification::class,
            fn (ExpenseRejectedNotification $notification): bool => $notification->expense->id === $expense->id &&
                $notification->rejector->id === $rejector->id &&
                $notification->reason === $reason
        );
    });

    it('implements ShouldQueue interface', function (): void {
        $listener = new NotifyExpenseReviewed();

        expect($listener instanceof ShouldQueue)->toBeTrue();
    });
});
