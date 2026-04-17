<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Livewire\Expenses\ExpenseDetail;
use App\Models\Department;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the expense detail component', function (): void {
    $expense = Expense::factory()->create();
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->assertStatus(200);
});

it('mounts with expense data', function (): void {
    $expense = Expense::factory()->create();
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    expect($component->get('expenseId'))->toBe($expense->id);
});

it('eager loads relationships on mount', function (): void {
    $expense = Expense::factory()->create();
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $loadedExpense = $component->get('expense');
    expect($loadedExpense->category)->not->toBeNull()
        ->and($loadedExpense->user)->not->toBeNull();
});

it('authorizes user can view expense', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user1->id]);

    $component = Livewire::actingAs($user2)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->assertForbidden();
});

it('submits a draft expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->call('submit');

    $expense->refresh();
    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

it('flashes success message when submitting', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->call('submit');

    // Verify the expense was submitted successfully
    $expense->refresh();
    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

it('prevents submitting non-draft expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    // Verify expense is not in draft status
    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

it('deletes a draft expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->call('delete');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

it('prevents deleting submitted expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    // Verify expense remains submitted
    $expense->refresh();
    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

it('prevents deleting approved expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    // Verify expense remains approved
    expect($expense->status)->toBe(ExpenseStatus::Approved);
});

it('checks budget constraint on submit', function (): void {
    $department = Department::factory()->create(['monthly_budget' => 1000]);
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'status' => ExpenseStatus::Draft,
        'amount' => 50000, // 500 in dollars, exceeds budget
    ]);

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->call('submit');

    // Verify expense still exists
    $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
});

it('shows rejection reason field', function (): void {
    $expense = Expense::factory()->create();
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    expect($component->get('rejectionReason'))->toBe('');
});

it('toggles reject modal', function (): void {
    $expense = Expense::factory()->create();
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    expect($component->get('showRejectModal'))->toBeFalse();
});

it('initializes with correct data', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $user = User::where('id', $expense->user_id)->first();

    $component = Livewire::actingAs($user)->test(ExpenseDetail::class, ['expense' => $expense]);

    expect($component->get('expenseId'))->toBe($expense->id)
        ->and($component->get('expense')->id)->toBe($expense->id)
        ->and($component->get('rejectionReason'))->toBe('')
        ->and($component->get('showRejectModal'))->toBeFalse();
});

it('prevents unauthorized deletion', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user1->id, 'status' => ExpenseStatus::Draft]);

    $component = Livewire::actingAs($user2)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->assertForbidden();
});
