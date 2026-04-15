<?php

declare(strict_types=1);

/**
 * UpdateExpense Action
 *
 * WHAT: Updates an existing draft expense and optionally replaces its receipt upload.
 *
 * WHY: Only draft expenses are editable, and this rule belongs in a reusable business-action
 *      layer rather than being spread across components or requests.
 *
 * IMPLEMENT: Verify the expense is Draft, apply validated data, handle receipt replacement,
 *            save the expense, and return the refreshed model.
 *
 * KEY CONCEPTS:
 * - Draft-only edit rules
 * - UploadedFile replacement flow
 * - Storage cleanup before replacement
 */

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class UpdateExpense
{
    /**
     * Update an existing draft expense.
     *
     * @param  Expense  $expense  The expense being edited
     * @param  array<string, mixed>  $data  Validated expense data from the form layer
     * @param  UploadedFile|null  $receipt  Optional replacement receipt file
     */
    public function execute(Expense $expense, array $data, ?UploadedFile $receipt = null): Expense
    {

        if ($expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be updated.');
        }
        $expense->title = $data['title'];
        $expense->amount = $data['amount'];
        $expense->description = $data['description'];
        $expense->date = $data['date'];
        $expense->category_id = (int) $data['category_id'];
        $expense->currency = $data['currency'];
        if ($receipt) {
            // Delete old receipt if it exists
            if ($expense->receipt_path) {
                Storage::delete($expense->receipt_path);
            }
            // Store new receipt and update path
            $expense->receipt_path = $receipt->store('receipts');
            $expense->save();

            return $expense;
        }
        $expense->save();

        return $expense;
    }
}
