<?php

declare(strict_types=1);

/**
 * ExpensePolicy
 *
 * WHAT: Determines who can view, create, update, delete, approve, or reject expense records.
 *
 * WHY: Expenses are role-specific (employees create, managers review, accountants record).
 *      Each action has different authorization rules (e.g., only owners can edit drafts,
 *      only managers can approve). Policies centralize these rules.
 *
 * IMPLEMENT: Each method checks the user's role and sometimes the expense's state/ownership.
 *            Return true if authorized, false if not. Blade directives (e.g., @can('view', $expense))
 *            and controller checks call these methods implicitly.
 *
 * REFERENCE:
 * - Laravel Policies: https://laravel.com/docs/13.x/authorization#creating-policies
 * - Authorization Helpers: https://laravel.com/docs/13.x/authorization#via-the-user-model
 */

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;

final class ExpensePolicy
{
    /**
     * Determine if the user can view any expenses.
     */
    public function viewAny(User $user): bool
    {
        // TODO: All authenticated roles can view expense lists (but queries should scope differently)
        if ($user) {
            return true;
        }

        // TODO: Return true for all roles
        return true;
    }

    /**
     * Determine if the user can view a specific expense.
     */
    public function view(User $user, Expense $expense): bool
    {
        // TODO: Employee can only view their own expenses
        if ($user->role === UserRole::Employee && $expense->user_id === $user->id) {
            return true;
        }
        // TODO: Manager can view expenses from their own department
        if ($user->role === UserRole::Manager && $expense->department_id === $user->department_id) {
            return true;
        }
        // TODO: Admin and Accountant can view all expenses
        if ($user->role === UserRole::Admin || $user->role === UserRole::Accountant) {
            return true;
        }

        // TODO: Return true/false based on role and ownership/department
        return true;
    }

    /**
     * Determine if the user can create expenses.
     */
    public function create(User $user): bool
    {
        // TODO: Only Employee and Manager roles can create expenses
        if ($user->role === UserRole::Employee || $user->role === UserRole::Manager) {

            // TODO: User MUST have a department_id assigned (not null)
            if ($user->department_id !== null) {
                return true;
            }
        }

        // TODO: Return false if no department assigned
        return false;
    }

    /**
     * Determine if the user can update an expense.
     */
    public function update(User $user, Expense $expense): bool
    {
        // TODO: Only the owner (user who created it) can update

        // TODO: AND only when expense status is Draft
        if ($expense->user_id === $user->id && $expense->status === ExpenseStatus::Draft) {
            return true;
        }

        // TODO: Return false if not owner or status is not Draft
        return false;
    }

    /**
     * Determine if the user can delete an expense.
     */
    public function delete(User $user, Expense $expense): bool
    {
        // TODO: Only the owner can delete
        if ($expense->user_id === $user->id) {
            // TODO: AND only when expense status is Draft
            if ($expense->status === ExpenseStatus::Draft) {
                return true;
            }
        }
        // TODO: Return false if not owner or status is not Draft

        return false;
    }

    public function approve(User $user, Expense $expense): bool
    {
        // TODO: Only Managers can approve
        if ($user->role !== UserRole::Manager) {
            return false;
        }
        // TODO: Only for expenses in their own department
        if ($expense->department_id !== $user->department_id) {
            return false;
        }
        // TODO: Only when expense status is Submitted
        if ($expense->status !== ExpenseStatus::Submitted) {
            return false;
        }

        // TODO: Check: user role is Manager, department matches, status is Submitted
        return true;
    }

    /**
     * Determine if the user can reject an expense.
     */
    public function reject(User $user, Expense $expense): bool
    {
        // TODO: Same rules as approve
        if ($user->role !== UserRole::Manager) {
            return false;
        }
        if ($expense->department_id !== $user->department_id) {
            return false;
        }
        if ($expense->status !== ExpenseStatus::Submitted) {
            return false;
        }

        return true;
    }
}
