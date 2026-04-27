<?php

declare(strict_types=1);

use App\Events\ExpenseApproved;
use App\Models\Expense;
use App\Models\User;

describe('ExpenseApproved Event', function (): void {
    it('can create an event with expense and approver', function (): void {
        $expense = Expense::factory()->create();
        $approver = User::factory()->create();

        $event = new ExpenseApproved($expense, $approver);

        expect($event->expense->id)->toBe($expense->id);
        expect($event->approver->id)->toBe($approver->id);
    });

    it('has correct properties', function (): void {
        $expense = Expense::factory()->create();
        $approver = User::factory()->create();

        $event = new ExpenseApproved($expense, $approver);

        expect($event)->toHaveProperties(['expense', 'approver']);
    });

    it('can be serialized for queuing', function (): void {
        $expense = Expense::factory()->create();
        $approver = User::factory()->create();

        $event = new ExpenseApproved($expense, $approver);

        // Test that the event can be serialized (for queue broadcasting)
        $serialized = serialize($event);
        expect($serialized)->toBeString();

        $unserialized = unserialize($serialized);
        expect($unserialized->expense->id)->toBe($expense->id);
        expect($unserialized->approver->id)->toBe($approver->id);
    });
});
