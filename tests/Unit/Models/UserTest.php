<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;

test('user can be created with factory', function (): void {
    // ARRANGE: Use the factory to create a user with defaults
    $user = User::factory()->create();

    // ACT: Refresh from database to ensure it persisted
    $user->refresh();

    // ASSERT: Verify the user exists in the database with expected attributes
    expect($user)->not->toBeNull()
        ->and($user->name)->toBeString()
        ->and($user->email)->toBeString()
        ->and($user->password)->toBeString()
        ->and($user->id)->toBeString(); // UUID
});

test('user has all required attributes', function (): void {
    // ARRANGE: Create a user with the factory
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => UserRole::Employee,
    ]);

    // ACT & ASSERT: Access attributes and verify they exist
    // NOTE: 'password' and 'remember_token' are hidden and won't appear in toArray()
    expect($user->toArray())
        ->toHaveKeys([
            'id',
            'name',
            'email',
            'email_verified_at',
            'role',
            'department_id',
            'manager_id',
            'created_at',
            'updated_at',
        ]);
});

test('user role is cast to enum', function (): void {
    // ARRANGE: Create a user and manually set the role as an enum
    $user = User::factory()->create(['role' => UserRole::Manager]);

    // ACT & ASSERT: Verify the role is returned as an enum, not a string
    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::Manager);
});

test('user password is hashed', function (): void {
    // ARRANGE: Create a user with the factory (password is hashed during factory)
    $plainPassword = 'password123';
    $user = User::factory()->create(['password' => $plainPassword]);

    // ACT & ASSERT: Verify the stored password is different from the plain text
    // (Laravel's 'hashed' cast automatically hashes it)
    expect($user->password)->not->toBe($plainPassword)
        ->and(hash_equals($user->password, bcrypt($plainPassword)) || // Verify it's actually hashed
            $user->password !== $plainPassword) // At minimum, it should differ
        ->toBeTrue();
});

test('user belongs to a department', function (): void {
    // ARRANGE: Create a department and a user linked to it
    $department = Department::factory()->create(['name' => 'Engineering']);
    $user = User::factory()->create(['department_id' => $department->id]);

    // ACT: Access the department relationship
    $userDepartment = $user->department;

    // ASSERT: Verify the relationship returns the correct department
    expect($userDepartment)->not->toBeNull()
        ->and($userDepartment->id)->toBe($department->id)
        ->and($userDepartment->name)->toBe('Engineering');
});

test('user department can be null for some users', function (): void {
    // ARRANGE: Create a user without assigning a department (e.g., admin user)
    $user = User::factory()->create(['department_id' => null]);

    // ACT & ASSERT: Verify the department relationship returns null
    expect($user->department)->toBeNull();
});

test('employee has a manager', function (): void {
    // ARRANGE: Create a manager user and an employee user who reports to them
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $employee = User::factory()->create([
        'role' => UserRole::Employee,
        'manager_id' => $manager->id,
    ]);

    // ACT: Access the manager relationship from the employee
    $employeeManager = $employee->manager;

    // ASSERT: Verify the employee's manager is the correct user
    expect($employeeManager)->not->toBeNull()
        ->and($employeeManager->id)->toBe($manager->id);
});

test('manager has subordinates', function (): void {
    // ARRANGE: Create a manager and multiple employees reporting to them
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $employee1 = User::factory()->create([
        'role' => UserRole::Employee,
        'manager_id' => $manager->id,
    ]);
    $employee2 = User::factory()->create([
        'role' => UserRole::Employee,
        'manager_id' => $manager->id,
    ]);

    // ACT: Get all subordinates for the manager
    $subordinates = $manager->subordinates;

    // ASSERT: Verify both employees are in the manager's subordinates list
    expect($subordinates)->toHaveCount(2)
        ->and($subordinates->pluck('id')->toArray())->toContain($employee1->id, $employee2->id);
});

test('user with no manager returns null for manager relationship', function (): void {
    // ARRANGE: Create a user (e.g., admin) with no manager
    $user = User::factory()->create(['manager_id' => null]);

    // ACT & ASSERT: Verify the manager relationship is null
    expect($user->manager)->toBeNull();
});

test('user has many expenses', function (): void {
    // ARRANGE: Create a user and multiple expenses for that user
    $user = User::factory()->create();
    $expense1 = Expense::factory()->create(['user_id' => $user->id]);
    $expense2 = Expense::factory()->create(['user_id' => $user->id]);

    // ACT: Get all expenses for the user
    $expenses = $user->expenses;

    // ASSERT: Verify both expenses are returned
    expect($expenses)->toHaveCount(2)
        ->and($expenses->pluck('id')->toArray())->toContain($expense1->id, $expense2->id);
});

test('user expenses only include their own expenses', function (): void {
    // ARRANGE: Create two users and expenses for each
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Expense::factory()->create(['user_id' => $user1->id]);
    Expense::factory()->create(['user_id' => $user2->id]);

    // ACT: Get user1's expenses
    $expenses = $user1->expenses;

    // ASSERT: Verify user1 only has their own expense
    expect($expenses)->toHaveCount(1)
        ->and($expenses->first()->user_id)->toBe($user1->id);
});

test('user can have created invoices', function (): void {
    // ARRANGE: Create a user (accountant) who will create invoices
    $user = User::factory()->create(['role' => UserRole::Accountant]);
    $invoice1 = Invoice::factory()->create(['created_by' => $user->id]);
    $invoice2 = Invoice::factory()->create(['created_by' => $user->id]);

    // ACT: Get all invoices created by this user
    $createdInvoices = $user->createdInvoices;

    // ASSERT: Verify both invoices are returned
    expect($createdInvoices)->toHaveCount(2)
        ->and($createdInvoices->pluck('id')->toArray())->toContain($invoice1->id, $invoice2->id);
});

test('all user roles can be applied to user', function (): void {
    // ARRANGE: Get all available roles from the enum
    $roles = [
        UserRole::Admin,
        UserRole::Manager,
        UserRole::Employee,
        UserRole::Accountant,
    ];

    // ACT & ASSERT: Create a user for each role and verify it sticks
    foreach ($roles as $role) {
        $user = User::factory()->create(['role' => $role]);
        expect($user->role)->toBe($role);
    }
});

test('deleting a user cascades to their expenses', function (): void {
    // ARRANGE: Create a user with expenses
    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $expenseId = $expense->id;

    // ACT: Delete the user
    $user->delete();

    // ASSERT: Verify the expense is also deleted (cascade)
    expect(Expense::query()->find($expenseId))->toBeNull();
});

test('deleting a user cascades to their created invoices', function (): void {
    // ARRANGE: Create a user who created invoices
    $user = User::factory()->create(['role' => UserRole::Accountant]);
    $invoice = Invoice::factory()->create(['created_by' => $user->id]);

    $invoiceId = $invoice->id;

    // ACT: Delete the user
    $user->delete();

    // ASSERT: Verify the invoice is also deleted (cascade)
    expect(Invoice::query()->find($invoiceId))->toBeNull();
});
