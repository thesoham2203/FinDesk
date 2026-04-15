<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use InvalidArgumentException;

final class SubmitExpense
{
    /**
     * Submit a draft expense.
     *
     * @param  Expense  $expense  The draft expense to submit
     */
    public function execute(Expense $expense): Expense
    {
        if ($expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be submitted.');
        }
        $expense->transitionTo(ExpenseStatus::Submitted);
        $expense->submitted_at = now();
        $expense->department_id = $expense->user->department_id;
        $expense->save();

        return $expense;
    }
}
