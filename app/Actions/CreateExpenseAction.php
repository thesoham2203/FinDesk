<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Activity;
use App\Models\Expense;
use App\Models\User;

final class CreateExpenseAction
{
    public function execute(
        User $user,
        string $title,
        string $description,
        int $amount,
        string $currency,
        int $categoryId,
        ?string $receiptPath,
    ): Expense {
        $expense = Expense::query()->create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'currency' => $currency,
            'status' => ExpenseStatus::Draft,
            'receipt_path' => $receiptPath,
        ]);

        Activity::query()->create([
            'user_id' => $user->id,
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
            'description' => 'Expense created',
            'properties' => [
                'amount' => $amount,
                'title' => $title,
            ],
        ]);

        return $expense;
    }
}
