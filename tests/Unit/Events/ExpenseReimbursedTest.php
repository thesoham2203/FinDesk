<?php

declare(strict_types=1);

use App\Events\ExpenseReimbursed;
use App\Models\Expense;
use App\Models\User;

describe('ExpenseReimbursed Event', function (): void {
    it('can create an event with expense and processor', function (): void {
        $expense = Expense::factory()->create();
        $processor = User::factory()->create();

        $event = new ExpenseReimbursed($expense, $processor);

        expect($event->expense->id)->toBe($expense->id);
        expect($event->processor->id)->toBe($processor->id);
    });

    it('has correct properties', function (): void {
        $expense = Expense::factory()->create();
        $processor = User::factory()->create();

        $event = new ExpenseReimbursed($expense, $processor);

        expect($event)->toHaveProperties(['expense', 'processor']);
    });

    it('can be serialized for queuing', function (): void {
        $expense = Expense::factory()->create();
        $processor = User::factory()->create();

        $event = new ExpenseReimbursed($expense, $processor);

        $serialized = serialize($event);
        expect($serialized)->toBeString();

        $unserialized = unserialize($serialized);
        expect($unserialized->expense->id)->toBe($expense->id);
        expect($unserialized->processor->id)->toBe($processor->id);
    });
});
