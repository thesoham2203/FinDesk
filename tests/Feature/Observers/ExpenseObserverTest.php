<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Events\ExpenseApproved;
use App\Events\ExpenseRejected;
use App\Models\Activity;
use App\Models\Expense;
use App\Models\User;
use App\Observers\ExpenseObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

test('deleting an expense with receipt deletes the receipt file', function (): void {
    Storage::fake('local');

    $expense = Expense::factory()->create([
        'receipt_path' => 'receipts/test-receipt.pdf',
    ]);

    $expense->delete();

    Storage::disk('local')->assertMissing('receipts/test-receipt.pdf');
});

test('deleting an expense without receipt does not throw error', function (): void {
    $expense = Expense::factory()->create([
        'receipt_path' => null,
    ]);

    $expense->delete();

    expect($expense->exists)->toBeFalse();
});

test('creating an expense logs activity', function (): void {
    $user = User::factory()->create(['name' => 'John Doe']);

    $expense = Expense::factory()->create([
        'title' => 'Office Supplies',
        'amount' => 50000,
        'user_id' => $user->id,
    ]);

    $activity = Activity::query()
        ->where('subject_type', Expense::class)
        ->where('subject_id', $expense->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->description)->toContain('Employee John Doe created expense: Office Supplies');
});

test('updating expense status from draft to submitted does not break observer', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
    ]);

    $expense->update(['status' => ExpenseStatus::Submitted]);

    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

test('updating expense status to approved with reviewer', function (): void {
    $reviewer = User::factory()->create();
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
    ]);

    $expense->update([
        'status' => ExpenseStatus::Approved,
        'reviewed_by' => $reviewer->id,
    ]);

    expect($expense->status)->toBe(ExpenseStatus::Approved)
        ->and($expense->reviewed_by)->toBe($reviewer->id);
});

test('updating expense status to rejected with reason', function (): void {
    $reviewer = User::factory()->create();
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
    ]);

    $expense->update([
        'status' => ExpenseStatus::Rejected,
        'reviewed_by' => $reviewer->id,
        'rejection_reason' => 'Missing receipt',
    ]);

    expect($expense->status)->toBe(ExpenseStatus::Rejected)
        ->and($expense->rejection_reason)->toBe('Missing receipt');
});

test('updating expense status to reimbursed', function (): void {
    $processor = User::factory()->create();
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Approved,
    ]);

    $this->actingAs($processor);
    $expense->update(['status' => ExpenseStatus::Reimbursed]);

    expect($expense->status)->toBe(ExpenseStatus::Reimbursed);
});

test('updating expense title without changing status works', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'title' => 'Old Title',
    ]);

    $expense->update(['title' => 'New Title']);

    expect($expense->title)->toBe('New Title')
        ->and($expense->status)->toBe(ExpenseStatus::Draft);
});

test('created without author returns early', function (): void {
    $expense = Expense::factory()->create();
    $expense->setRelation('user', null);

    $countBefore = Activity::query()->count();
    new ExpenseObserver()->created($expense);

    expect(Activity::query()->count())->toBe($countBefore);
});

test('updating to approved without reviewer falls back to current user', function (): void {
    Event::fake([ExpenseApproved::class]);
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
    ]);

    $expense->update([
        'status' => ExpenseStatus::Approved,
        'reviewed_by' => null,
    ]);

    expect($expense->reviewed_by)->toBeNull();
    Event::assertDispatched(ExpenseApproved::class, fn (ExpenseApproved $event): bool => $event->approver->id === $admin->id);
});

test('updating to rejected without reviewer falls back to current user', function (): void {
    Event::fake([ExpenseRejected::class]);
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
    ]);

    $expense->update([
        'status' => ExpenseStatus::Rejected,
        'reviewed_by' => null,
    ]);

    expect($expense->reviewed_by)->toBeNull();
    Event::assertDispatched(ExpenseRejected::class, fn (ExpenseRejected $event): bool => $event->rejector->id === $admin->id);
});
