<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use InvalidArgumentException;

final class RejectExpense
{
    /**
     * Reject an expense with a reason, triggering side effects through observers and events.
     *
     * @throws InvalidArgumentException if transition is invalid
     */
    public function execute(Expense $expense, User $rejector, string $reason): Expense
    {
        $expense->transitionTo(ExpenseStatus::Rejected);
        $expense->reviewed_at = now();
        $expense->reviewed_by = $rejector->id;
        $expense->rejection_reason = $reason;
        $expense->save();

        return $expense->fresh();
    }
}
