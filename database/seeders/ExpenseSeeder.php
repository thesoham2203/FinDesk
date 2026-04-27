<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

final class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ExpenseCategory::all();
        $employeeUsers = User::query()->where('role', UserRole::Employee)->get();
        $managerUsers = User::query()->where('role', UserRole::Manager)->get();
        User::query()->where('role', UserRole::Accountant)->get();

        // Seed categories first if they don't exist
        if ($categories->isEmpty()) {
            $this->call(ExpenseCategorySeeder::class);
            $categories = ExpenseCategory::all();
        }

        // Ensure we have users to seed expenses for
        if ($employeeUsers->isEmpty() || $managerUsers->isEmpty()) {
            return;
        }

        // ===== DRAFT EXPENSES =====
        // Employees can create and save expenses as drafts
        foreach ($employeeUsers->take(2) as $user) {
            Expense::factory()
                ->count(2)
                ->for($user)
                ->state(fn (): array => ['department_id' => $user->department_id])
                ->create(['status' => ExpenseStatus::Draft]);
        }

        // ===== SUBMITTED EXPENSES =====
        // Expenses submitted by employees, waiting for manager review
        Expense::factory()
            ->count(2)
            ->submitted()
            ->for($employeeUsers->first())
            ->state(fn (): array => ['department_id' => $employeeUsers->first()->department_id])
            ->create();

        Expense::factory()
            ->approved()
            ->for($employeeUsers->first())
            ->state(fn (): array => [
                'department_id' => $employeeUsers->first()->department_id,
                'reviewed_by' => $managerUsers->first()->id,
            ])
            ->create();

        // ===== REJECTED EXPENSE =====
        // Manager rejected the expense with reason (employee can fix and resubmit)
        Expense::factory()
            ->rejected()
            ->for($employeeUsers->skip(1)->first() ?? $employeeUsers->first())
            ->state(fn (): array => [
                'department_id' => ($employeeUsers->skip(1)->first() ?? $employeeUsers->first())->department_id,
                'reviewed_by' => $managerUsers->first()->id,
                'rejection_reason' => 'Missing receipt documentation. Please attach and resubmit.',
            ])
            ->create();

        // ===== REIMBURSED EXPENSE =====
        // Approved expense marked as reimbursed (final state)
        Expense::factory()
            ->reimbursed()
            ->for($employeeUsers->first())
            ->state(fn (): array => [
                'department_id' => $employeeUsers->first()->department_id,
                'reviewed_by' => $managerUsers->first()->id,
            ])
            ->create();
    }
}
