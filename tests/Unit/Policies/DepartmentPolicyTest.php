<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Policies\DepartmentPolicy;

describe('DepartmentPolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows admin to view any department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new DepartmentPolicy();

            expect($policy->viewAny($admin))->toBeTrue();
        });

        it('prevents non-admin from viewing any department', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $policy = new DepartmentPolicy();

            expect($policy->viewAny($employee))->toBeFalse();
        });
    });

    describe('view', function (): void {
        it('allows admin to view a specific department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $department = Department::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->view($admin))->toBeTrue();
        });

        it('prevents non-admin from viewing a specific department', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $department = Department::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->view($employee))->toBeFalse();
        });
    });

    describe('create', function (): void {
        it('allows admin to create department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new DepartmentPolicy();

            expect($policy->create($admin))->toBeTrue();
        });

        it('prevents non-admin from creating department', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $policy = new DepartmentPolicy();

            expect($policy->create($employee))->toBeFalse();
        });
    });

    describe('update', function (): void {
        it('allows admin to update department', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $department = Department::factory()->create();
            $policy = new DepartmentPolicy();

            expect($policy->update($admin))->toBeTrue();
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
