<?php

declare(strict_types=1);

use App\Events\ExpenseSubmitted;
use App\Listeners\LogExpenseActivity;
use App\Models\Activity;
use App\Models\Expense;

it('does not create activity for unsupported event types', function (): void {
    $listener = new LogExpenseActivity();
    $expense = Expense::factory()->create();

    $event = new class($expense)
    {
        public function __construct(public Expense $expense) {}
    };

    $listener->handle($event);

    expect(Activity::query()->where('subject_id', $expense->id)->count())->toBe(1);
});

it('creates activity for submitted expense event', function (): void {
    $listener = new LogExpenseActivity();
    $expense = Expense::factory()->create();

    $listener->handle(new ExpenseSubmitted($expense));

    expect(Activity::query()
        ->where('subject_id', $expense->id)
        ->where('description', 'like', 'Employee % submitted expense:%')
        ->exists())->toBeTrue();
});

it('returns early when expense user is not a User model', function (): void {
    $listener = new LogExpenseActivity();
    $expense = Expense::factory()->create();

    // Force user to be null (or not an instance of User)
    $expense->setRelation('user', null);

    $countBefore = Activity::query()->count();
    $listener->handle(new ExpenseSubmitted($expense));

    expect(Activity::query()->count())->toBe($countBefore);
});
