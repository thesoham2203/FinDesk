<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Policies\DepartmentPolicy;

describe('DepartmentPolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows authenticated users to view any department', function (): void {
            $user = User::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->viewAny($user))->toBeTrue();
        });
    });

    describe('view', function (): void {
        it('allows authenticated users to view a specific department', function (): void {
            $user = User::factory()->create();
            $department = Department::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->view($user, $department))->toBeTrue();
        });
    });

    describe('create', function (): void {
        it('allows admin to create department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new DepartmentPolicy();

            expect($policy->create($admin))->toBeTrue();
        });

        it('allows admin to create department (double check)', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new DepartmentPolicy();

            expect($policy->create($admin))->toBeTrue();
        });

        it('allows any user to create department', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $policy = new DepartmentPolicy();

            expect($policy->create($employee))->toBeTrue();
        });
    });

    describe('update', function (): void {
        it('allows admin to update department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $department = Department::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->update($admin, $department))->toBeTrue();
        });
    });

    describe('delete', function (): void {
        it('has delete method', function (): void {
            $policy = new DepartmentPolicy();

            // Verify method exists
            expect(method_exists($policy, 'delete'))->toBeTrue();
        });
    });

});
