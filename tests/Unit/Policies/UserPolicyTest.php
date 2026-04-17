<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;

describe('UserPolicy', function (): void {
    describe('viewAny', function (): void {
        it('allows any authenticated user to view any user', function (): void {
            $user = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->viewAny($user))->toBeTrue();
        });
    });

    describe('view', function (): void {
        it('allows admin to view any user', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $otherUser = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->view($admin, $otherUser))->toBeTrue();
        });

        it('allows user to view their own profile', function (): void {
            $user = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->view($user, $user))->toBeTrue();
        });

        it('prevents user from viewing other users', function (): void {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->view($user1, $user2))->toBeFalse();
        });
    });

    describe('create', function (): void {
        it('allows any authenticated user to create users', function (): void {
            $user = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->create($user))->toBeTrue();
        });
    });

    describe('update', function (): void {
        it('allows admin to update any user', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $otherUser = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->update($admin, $otherUser))->toBeTrue();
        });

        it('allows user to update their own profile', function (): void {
            $user = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->update($user, $user))->toBeTrue();
        });

        it('prevents user from updating other users', function (): void {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->update($user1, $user2))->toBeFalse();
        });
    });

    describe('delete', function (): void {
        it('allows admin to delete other users', function (): void {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $otherUser = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->delete($admin, $otherUser))->toBeTrue();
        });

        it('prevents user from deleting themselves', function (): void {
            $user = User::factory()->create(['role' => UserRole::Admin]);
            $policy = new UserPolicy();

            expect($policy->delete($user, $user))->toBeFalse();
        });

        it('prevents non-admin from deleting users', function (): void {
            $employee = User::factory()->create(['role' => UserRole::Employee]);
            $otherUser = User::factory()->create();
            $policy = new UserPolicy();

            expect($policy->delete($employee, $otherUser))->toBeFalse();
        });
    });
});
