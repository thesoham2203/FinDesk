<?php

declare(strict_types=1);

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Expense;
use App\Models\User;
use App\Policies\ExpensePolicy;

describe('ExpensePolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows authenticated users', function (): void {
            $user = User::factory()->create();
            $policy = new ExpensePolicy();

            expect($policy->viewAny($user))->toBeTrue();
        });
    });

    describe('view', function (): void {
        it('allows employees to view their own expenses', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $expense = Expense::factory()->create(['user_id' => $employee->id]);
            $policy = new ExpensePolicy();

            expect($policy->view($employee, $expense))->toBeTrue();
        });

        it('prevents employees from viewing others expenses', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $otherEmployee = User::factory()->create(['role' => UserRole::Employee]);
            $expense = Expense::factory()->create(['user_id' => $otherEmployee->id]);
            $policy = new ExpensePolicy();

            expect($policy->view($employee, $expense))->toBeFalse();
        });

        it('allows managers to view department expenses', function (): void {
            $department = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department->id,
            ]);
            $expense = Expense::factory()->create(['department_id' => $department->id]);
            $policy = new ExpensePolicy();

            expect($policy->view($manager, $expense))->toBeTrue();
        });

        it('prevents managers from viewing other department expenses', function (): void {
            $department1 = Department::factory()->create();
            $department2 = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department1->id,
            ]);
            $expense = Expense::factory()->create(['department_id' => $department2->id]);
            $policy = new ExpensePolicy();

            expect($policy->view($manager, $expense))->toBeFalse();
        });

        it('allows admin to view all expenses', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $expense = Expense::factory()->create();
            $policy = new ExpensePolicy();

            expect($policy->view($admin, $expense))->toBeTrue();
        });

        it('allows accountant to view all expenses', function (): void {
            $accountant = User::factory()->create(['role' => UserRole::Accountant]);
            $expense = Expense::factory()->create();
            $policy = new ExpensePolicy();

            expect($policy->view($accountant, $expense))->toBeTrue();
        });
    });

    describe('create', function (): void {
        it('allows employees with department to create expenses', function (): void {
            $department = Department::factory()->create();
            $employee = User::factory()->create([
                'role' => UserRole::Employee,
                'department_id' => $department->id,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->create($employee))->toBeTrue();
        });

        it('prevents employees without department from creating expenses', function (): void {
            $employee = User::factory()->create([
                'role' => UserRole::Employee,
                'department_id' => null,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->create($employee))->toBeFalse();
        });

        it('allows managers with department to create expenses', function (): void {
            $department = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department->id,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->create($manager))->toBeFalse();
        });

        it('prevents managers without department', function (): void {
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => null,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->create($manager))->toBeFalse();
        });
    });

    describe('update', function (): void {
        it('allows owner to update draft expense', function (): void {
            $user = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $user->id,
                'status' => ExpenseStatus::Draft,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->update($user, $expense))->toBeTrue();
        });

        it('prevents owner from updating submitted expense', function (): void {
            $user = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $user->id,
                'status' => ExpenseStatus::Submitted,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->update($user, $expense))->toBeFalse();
        });

        it('prevents non-owner from updating expense', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $owner->id,
                'status' => ExpenseStatus::Draft,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->update($other, $expense))->toBeFalse();
        });
    });

    describe('delete', function (): void {
        it('allows owner to delete draft expense', function (): void {
            $user = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $user->id,
                'status' => ExpenseStatus::Draft,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->delete($user, $expense))->toBeTrue();
        });

        it('prevents owner from deleting submitted expense', function (): void {
            $user = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $user->id,
                'status' => ExpenseStatus::Submitted,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->delete($user, $expense))->toBeFalse();
        });

        it('prevents non-owner from deleting expense', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $expense = Expense::factory()->create([
                'user_id' => $owner->id,
                'status' => ExpenseStatus::Draft,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->delete($other, $expense))->toBeFalse();
        });
    });

    describe('approve', function (): void {
        it('allows manager to approve submitted expense in their department', function (): void {
            $department = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department->id,
            ]);
            $expense = Expense::factory()->create([
                'department_id' => $department->id,
                'status' => ExpenseStatus::Submitted,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->approve($manager, $expense))->toBeTrue();
        });

        it('prevents non-manager from approving', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
            $policy = new ExpensePolicy();

            expect($policy->approve($employee, $expense))->toBeFalse();
        });

        it('prevents manager from approving other department expense', function (): void {
            $department1 = Department::factory()->create();
            $department2 = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department1->id,
            ]);
            $expense = Expense::factory()->create([
                'department_id' => $department2->id,
                'status' => ExpenseStatus::Submitted,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->approve($manager, $expense))->toBeFalse();
        });

        it('prevents manager from approving non-submitted expense', function (): void {
            $department = Department::factory()->create();
            $manager = User::factory()->create([
                'role' => UserRole::Manager,
                'department_id' => $department->id,
            ]);
            $expense = Expense::factory()->create([
                'department_id' => $department->id,
                'status' => ExpenseStatus::Draft,
            ]);
            $policy = new ExpensePolicy();

            expect($policy->approve($manager, $expense))->toBeFalse();
        });
    });
});
