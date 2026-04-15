<?php

declare(strict_types=1);

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

        return false;
    }

    /**
     * Determine if the user can view a specific expense.
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($user->role === UserRole::Employee && $expense->user_id === $user->id) {
            return true;
        }
        if ($user->role === UserRole::Manager && $expense->department_id === $user->department_id) {
            return true;
        }
        if ($user->role === UserRole::Admin || $user->role === UserRole::Accountant) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create expenses.
     */
    public function create(User $user): bool
    {
        if ($user->role === UserRole::Employee || $user->role === UserRole::Manager) {

            if ($user->department_id !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user can update an expense.
     */
    public function update(User $user, Expense $expense): bool
    {

        if ($expense->user_id === $user->id && $expense->status === ExpenseStatus::Draft) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete an expense.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($expense->user_id === $user->id) {
            if ($expense->status === ExpenseStatus::Draft) {
                return true;
            }
        }

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
