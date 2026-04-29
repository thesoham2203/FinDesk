<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\UserForm;
use App\Models\Department;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('populates form fields when editing a user', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => UserRole::Employee,
    ]);

    Livewire::test(UserForm::class, ['user' => $user])
        ->assertSet('name', 'John Doe')
        ->assertSet('email', 'john@example.com')
        ->assertSet('role', 'employee');
});

it('creates a new user', function () {
    $department = Department::factory()->create();

    Livewire::test(UserForm::class)
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('role', 'employee')
        ->set('departmentId', $department->id)
        ->call('save')
        ->assertDispatched('flash', type: 'success')
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

it('updates an existing user', function () {
    $user = User::factory()->create();

    Livewire::test(UserForm::class, ['user' => $user])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
});

it('requires a password when creating a new user', function () {
    Livewire::test(UserForm::class)
        ->set('name', 'New User')
        ->set('email', 'new-user@example.com')
        ->set('role', 'employee')
        ->call('save')
        ->assertHasErrors(['password']);

    expect(User::query()->where('email', 'new-user@example.com')->exists())->toBeFalse();
});

it('validates required fields on create', function () {
    Livewire::test(UserForm::class)
        ->set('role', '')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'role']);
});

it('validates password matching', function () {
    Livewire::test(UserForm::class)
        ->set('password', 'pass1')
        ->set('password_confirmation', 'pass2')
        ->call('save')
        ->assertHasErrors(['password' => 'confirmed']);
});
