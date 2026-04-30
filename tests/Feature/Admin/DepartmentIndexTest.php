<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentIndex;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the department index component', function (): void {
    $department = Department::factory()->create(['name' => 'Engineering']);

    Livewire::test(DepartmentIndex::class)
        ->assertStatus(200)
        ->assertSee('Engineering');
});

it('can search for departments', function (): void {
    Department::factory()->create(['name' => 'Engineering']);
    Department::factory()->create(['name' => 'Marketing']);

    Livewire::test(DepartmentIndex::class)
        ->set('search', 'Eng')
        ->assertSee('Engineering')
        ->assertDontSee('Marketing');
});

it('can delete a department without users', function (): void {
    $department = Department::factory()->create();

    Livewire::test(DepartmentIndex::class)
        ->call('delete', $department->id)
        ->assertDispatched('flash', type: 'success');

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

it('cannot delete a department with users', function (): void {
    $department = Department::factory()->create();
    User::factory()->create(['department_id' => $department->id]);

    Livewire::test(DepartmentIndex::class)
        ->call('delete', $department->id)
        ->assertDispatched('flash', type: 'error');

    $this->assertDatabaseHas('departments', ['id' => $department->id]);
});
