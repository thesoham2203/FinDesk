<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use InvalidArgumentException;

final class ApproveExpense
{
    /**
     * Approve an expense, triggering side effects through observers and events.
     *
     * @throws InvalidArgumentException if transition is invalid
     */
    public function execute(Expense $expense, User $approver): Expense
    {
        $expense->transitionTo(ExpenseStatus::Approved);
        $expense->reviewed_at = now();
        $expense->reviewed_by = $approver->id;
        $expense->save();

        return $expense->fresh();
    }
}
