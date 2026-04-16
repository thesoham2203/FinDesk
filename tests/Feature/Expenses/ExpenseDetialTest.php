<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays expense details for authorized user', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->assertSet('expenseId', $expense->id);
});

it('prevents unauthorized employee from viewing other expense', function () {
    $expenseOwner = User::factory()->create(['role' => UserRole::Employee]);
    $otherEmployee = User::factory()->create(['role' => UserRole::Employee]);
    $expense = Expense::factory()->create(['user_id' => $expenseOwner->id]);

    // Authorization check happens in mount, route will return 403
    $this->actingAs($otherEmployee)
        ->get(route('expenses.show', $expense))
        ->assertForbidden();
});

it('allows manager from same department to view expense', function () {
    $department = Department::factory()->create();
    $employee = User::factory()->create([
        'role' => UserRole::Employee,
        'department_id' => $department->id,
    ]);
    $manager = User::factory()->create([
        'role' => UserRole::Manager,
        'department_id' => $department->id,
    ]);
    $expense = Expense::factory()->create(['user_id' => $employee->id, 'department_id' => $department->id]);

    Livewire::actingAs($manager)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->assertSet('expenseId', $expense->id);
});

it('allows admin to view any expense', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $expense = Expense::factory()->create(['user_id' => $employee->id]);

    Livewire::actingAs($admin)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->assertSet('expenseId', $expense->id);
});

it('loads related data with expense', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->assertViewHas('expense');
});

it('submits draft expense', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id, 'status' => ExpenseStatus::Draft]);

    Livewire::actingAs($user)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->call('submit');

    expect($expense->fresh())->status->toBe(ExpenseStatus::Submitted);
});

it('prevents submitting non-draft expense', function () {
    $user = User::factory()->create();
    $expense = Expense::factory()->submitted()->create(['user_id' => $user->id]);

    // Livewire catches exceptions in ->call(), verify status didn't change instead
    Livewire::actingAs($user)
        ->test('expenses.expense-detail', ['expense' => $expense])
        ->call('submit');

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Submitted);
});
