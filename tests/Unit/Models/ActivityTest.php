<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Expense;
use App\Models\User;

test('activity can be created with factory', function (): void {
    $user = User::factory()->create();
    $expense = Expense::factory()->create();

    $activity = Activity::factory()->create([
        'user_id' => $user->id,
        'subject_type' => Expense::class,
        'subject_id' => $expense->id,
        'description' => 'Expense submitted',
        'properties' => ['amount' => 1000],
    ]);

    expect($activity->description)->toBe('Expense submitted')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(Expense::class)
        ->and($activity->subject_id)->toBe($expense->id)
        ->and($activity->properties)->toBe(['amount' => 1000]);
});

test('activity belongs to a user', function (): void {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['user_id' => $user->id]);

    expect($activity->user->id)->toBe($user->id);
});

test('activity has a polymorphic subject', function (): void {
    $expense = Expense::factory()->create();
    $activity = Activity::factory()->create([
        'subject_type' => Expense::class,
        'subject_id' => $expense->id,
    ]);

    expect($activity->subject->id)->toBe($expense->id);
    expect($activity->subject)->toBeInstanceOf(Expense::class);
});
