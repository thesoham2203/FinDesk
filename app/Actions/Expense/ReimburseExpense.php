<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use Ramsey\Uuid\Exception\InvalidArgumentException;

final class ReimburseExpense
{
    /**
     * Mark an expense as reimbursed, triggering side effects through observers and events.
     *
     * @throws InvalidArgumentException if transition is invalid
     */
    public function execute(Expense $expense, User $processor): Expense
    {
        $expense->transitionTo(ExpenseStatus::Reimbursed);
        $expense->save();

        return $expense->fresh();
    }
}
