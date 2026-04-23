<?php

declare(strict_types=1);

use App\Events\ExpenseSubmitted;
use App\Listeners\NotifyExpenseSubmitted;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('NotifyExpenseSubmitted Listener', function (): void {
    it('attempts to notify manager when manager exists', function (): void {
        $manager = User::factory()->create();
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $expense = Expense::factory()->create(['user_id' => $employee->id]);

        $event = new ExpenseSubmitted($expense);
        $listener = new NotifyExpenseSubmitted();

        // Should not throw when manager exists
        expect(fn () => $listener->handle($event))->not->toThrow(Exception::class);
    });

    it('handles case when employee has no manager gracefully', function (): void {
        $employee = User::factory()->create(['manager_id' => null]);
        $expense = Expense::factory()->create(['user_id' => $employee->id]);

        $event = new ExpenseSubmitted($expense);
        $listener = new NotifyExpenseSubmitted();

        // Should not throw even when manager is null
        expect(fn () => $listener->handle($event))->not->toThrow(Exception::class);
    });

    it('listener implements should queue interface', function (): void {
        $listener = new NotifyExpenseSubmitted();

        expect($listener)->toBeInstanceOf(ShouldQueue::class);
    });
});
