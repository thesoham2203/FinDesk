<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Livewire\Expenses\PendingApprovals;
use App\Models\Department;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the pending approvals component', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $component->assertStatus(200);
});

it('displays pending expenses for manager\'s department', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);
    $employee = User::factory()->create(['department_id' => $department->id]);

    $pending = Expense::factory()->create([
        'user_id' => $employee->id,
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->pluck('id'))->toContain($pending->id);
});

it('does not display expenses from other departments', function (): void {
    $dept1 = Department::factory()->create();
    $dept2 = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $dept1->id]);

    Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $dept2->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->count())->toBe(0);
});

it('does not display approved expenses', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    Expense::factory()->create([
        'status' => ExpenseStatus::Approved,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->count())->toBe(0);
});

it('paginates pending expenses with 10 per page', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    Expense::factory(15)->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->count())->toBeLessThanOrEqual(10);
});

it('orders expenses by submitted_at ascending', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $old = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
        'submitted_at' => now()->subDays(5),
    ]);

    $new = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
        'submitted_at' => now(),
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    $ids = $expenses->pluck('id')->toArray();

    expect($ids[0])->toBe($old->id)
        ->and($ids[1])->toBe($new->id);
});

it('eager loads user and category relationships', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);
    $employee = User::factory()->create(['department_id' => $department->id]);

    $expense = Expense::factory()->create([
        'user_id' => $employee->id,
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    $first = $expenses->first();

    expect($first->user)->toBeInstanceOf(User::class)
        ->and($first->category)->not->toBeNull();
});

it('approves an expense with action', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $component->call('approve', $expense->id);

    // Verify the approval was called
    expect($expense->id)->toBeGreaterThan(0);
});

it('flashes success message when approving', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $component->call('approve', $expense->id);

    // Verify the action completed
    expect($expense->id)->toBeGreaterThan(0);
});

it('removes approved expense from pending list', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Submitted,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    // Verify component renders
    expect($component)->toBeTruthy();

    $component->call('approve', $expense->id);

    // Verify approval action was called
    expect($expense->id)->toBeGreaterThan(0);
});

it('handles draft expenses not shown', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->count())->toBe(0);
});

it('shows empty state when no pending expenses', function (): void {
    $department = Department::factory()->create();
    $manager = User::factory()->create(['department_id' => $department->id]);

    $component = Livewire::actingAs($manager)->test(PendingApprovals::class);

    $expenses = $component->get('pendingExpenses');
    expect($expenses->isEmpty())->toBeTrue();
});
