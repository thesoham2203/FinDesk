<?php

declare(strict_types=1);

/**
 * ExpenseSeeder
 *
 * WHAT: Seeds the database with sample expenses in various workflow states.
 *
 * WHY: Testing requires expenses across all states to verify filters, state transitions,
 *      and authorization rules. This seeder creates realistic scenarios.
 *
 * CREATES:
 * - Draft expenses (2 employees × 2 expenses = 4 total) — ready to test submission
 * - Submitted expenses (2) — waiting for manager review
 * - Approved expense (1) — example of completed approval flow
 * - Rejected expense (1) — with rejection reason for resubmission testing
 * - Reimbursed expense (1) — terminal state
 *
 * TOTAL: ~9 diverse expenses covering all workflow states
 */

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
        $accountantUsers = User::query()->where('role', UserRole::Accountant)->get();

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
                ->state(fn () => ['department_id' => $user->department_id])
                ->create(['status' => ExpenseStatus::Draft]);
        }

        // ===== SUBMITTED EXPENSES =====
        // Expenses submitted by employees, waiting for manager review
        Expense::factory()
            ->count(2)
            ->submitted()
            ->for($employeeUsers->first())
            ->state(fn () => ['department_id' => $employeeUsers->first()->department_id])
            ->create();

        Expense::factory()
            ->approved()
            ->for($employeeUsers->first())
            ->state(fn () => [
                'department_id' => $employeeUsers->first()->department_id,
                'reviewed_by' => $managerUsers->first()->id,
            ])
            ->create();

        // ===== REJECTED EXPENSE =====
        // Manager rejected the expense with reason (employee can fix and resubmit)
        Expense::factory()
            ->rejected()
            ->for($employeeUsers->skip(1)->first() ?? $employeeUsers->first())
            ->state(fn () => [
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
            ->state(fn () => [
                'department_id' => $employeeUsers->first()->department_id,
                'reviewed_by' => $managerUsers->first()->id,
            ])
            ->create();
    }
}
