<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\DepartmentForm;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the department form component', function (): void {
    Livewire::test(DepartmentForm::class)
        ->assertStatus(200);
});

it('can create a department', function (): void {
    Livewire::test(DepartmentForm::class)
        ->set('name', 'Engineering')
        ->set('description', 'Engineering Department')
        ->set('monthlyBudget', '10000.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.departments.index'));

    $this->assertDatabaseHas('departments', [
        'name' => 'Engineering',
        'monthly_budget' => 1000050,
    ]);
});

it('can update a department', function (): void {
    $department = Department::factory()->create();

    Livewire::test(DepartmentForm::class, ['department' => $department])
        ->set('name', 'Updated Department Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.departments.index'));

    $department->refresh();
    expect($department->name)->toBe('Updated Department Name');
});

it('validates required fields', function (): void {
    Livewire::test(DepartmentForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
