<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\UserIndex;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the user index component', function () {
    Livewire::test(UserIndex::class)
        ->assertStatus(200);
});

it('lists users', function () {
    User::factory()->create(['name' => 'User A']);
    User::factory()->create(['name' => 'User B']);

    Livewire::test(UserIndex::class)
        ->assertSee('User A')
        ->assertSee('User B');
});

it('filters users by search', function () {
    User::factory()->create(['name' => 'Apple']);
    User::factory()->create(['name' => 'Banana']);

    Livewire::test(UserIndex::class)
        ->set('search', 'Apple')
        ->assertSee('Apple')
        ->assertDontSee('Banana');
});

it('filters users by role', function () {
    User::factory()->create(['role' => UserRole::Manager, 'name' => 'Manager User']);
    User::factory()->create(['role' => UserRole::Employee, 'name' => 'Employee User']);

    Livewire::test(UserIndex::class)
        ->set('roleFilter', UserRole::Manager->value)
        ->assertSee('Manager User')
        ->assertDontSee('Employee User');
});

it('deletes a user', function () {
    $user = User::factory()->create(['role' => UserRole::Employee]);

    Livewire::test(UserIndex::class)
        ->call('delete', $user->id)
        ->assertDispatched('flash', type: 'success');

    expect(User::query()->find($user->id))->toBeNull();
});

it('cannot delete yourself', function () {
    Livewire::test(UserIndex::class)
        ->call('delete', $this->admin->id)
        ->assertStatus(403);

    expect(User::query()->find($this->admin->id))->not->toBeNull();
});

it('cannot delete the last admin user', function () {
    $otherAdmin = User::factory()->create(['role' => UserRole::Admin]);

    // Now there are 2 admins. Delete one.
    Livewire::test(UserIndex::class)
        ->call('delete', $otherAdmin->id)
        ->assertDispatched('flash', type: 'success');

    expect(User::query()->find($otherAdmin->id))->toBeNull();

    // Now only 1 admin left. We can't test "last admin" check easily because
    // the only admin left cannot delete themselves.
});
