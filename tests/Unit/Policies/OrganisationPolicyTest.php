<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\OrganizationPolicy;

test('user role admin can view organization settings', function (): void {
    $adminUser = User::factory()->create(['role' => UserRole::Admin]);

    $policy = new OrganizationPolicy();

    expect($policy->view($adminUser))->toBeTrue();
});

test('user role admin can update organization settings', function (): void {
    $adminUser = User::factory()->create(['role' => UserRole::Admin]);

    $policy = new OrganizationPolicy();

    expect($policy->update($adminUser))->toBeTrue();
});

test('user role manager cannot view organization settings', function (): void {
    $managerUser = User::factory()->create(['role' => UserRole::Manager]);

    $policy = new OrganizationPolicy();

    expect($policy->view($managerUser))->toBeFalse();
});
