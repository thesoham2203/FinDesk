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
        if ($user->role->value === UserRole::Employee->value && $expense->user_id === $user->id) {
            return true;
        }
        if ($user->role->value === UserRole::Manager->value && $expense->department_id === $user->department_id) {
            return true;
        }
        if ($user->role->value === UserRole::Admin->value || $user->role->value === UserRole::Accountant->value) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create expenses.
     */
    public function create(User $user): bool
    {
        if ($user->role->value === UserRole::Employee->value) {
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

        if ($expense->user_id === $user->id && $expense->status === ExpenseStatus::Draft || $user->role->value === UserRole::Manager->value) {
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
        if ($user->role->value !== UserRole::Manager->value) {
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

    /**
     * Determine if the user can mark an expense as partially paid.
     */
    public function partiallyPaid(User $user, Expense $expense): bool
    {
        // Only Admin and Accountant can record partial payments
        if (! in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value], true)) {
            return false;
        }

        // Can only mark as partially paid when status is Approved
        return $expense->status === ExpenseStatus::Approved;
    }

    /**
     * Determine if the user can reject an expense.
     */
    public function reject(User $user, Expense $expense): bool
    {
        if ($user->role->value !== UserRole::Manager->value) {
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

    /**
     * Determine if the user can reimburse an expense.
     */
    public function reimburse(User $user, Expense $expense): bool
    {
        // Only Admin and Accountant can mark expenses as reimbursed
        if (! in_array($user->role->value, [UserRole::Admin->value, UserRole::Accountant->value], true)) {
            return false;
        }

        // Can reimburse from Approved or PartiallyPaid status
        return $expense->status === ExpenseStatus::Approved || $expense->status === ExpenseStatus::PartiallyPaid;
    }
}
