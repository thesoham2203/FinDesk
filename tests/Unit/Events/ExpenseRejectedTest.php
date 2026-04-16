<?php

declare(strict_types=1);

use App\Events\ExpenseRejected;
use App\Models\Expense;
use App\Models\User;

describe('ExpenseRejected Event', function (): void {
    it('can create an event with expense, rejector, and reason', function (): void {
        $expense = Expense::factory()->create();
        $rejector = User::factory()->create();
        $reason = 'Missing receipt';

        $event = new ExpenseRejected($expense, $rejector, $reason);

        expect($event->expense->id)->toBe($expense->id);
        expect($event->rejector->id)->toBe($rejector->id);
        expect($event->reason)->toBe($reason);
    });

    it('stores rejection reason correctly', function (): void {
        $expense = Expense::factory()->create();
        $rejector = User::factory()->create();
        $reason = 'Amount exceeds budget limit';

        $event = new ExpenseRejected($expense, $rejector, $reason);

        expect($event->reason)->toBe($reason);
    });

    it('can be serialized for queuing', function (): void {
        $expense = Expense::factory()->create();
        $rejector = User::factory()->create();
        $reason = 'Invalid category';

        $event = new ExpenseRejected($expense, $rejector, $reason);

        $serialized = serialize($event);
        expect($serialized)->toBeString();

        $unserialized = unserialize($serialized);
        expect($unserialized->expense->id)->toBe($expense->id);
        expect($unserialized->rejector->id)->toBe($rejector->id);
        expect($unserialized->reason)->toBe($reason);
    });
});
