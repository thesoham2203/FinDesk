<?php

declare(strict_types=1);


namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final class CreateExpense
{
    /**
     * Create a new draft expense.
     *
     * @param  User  $user  The authenticated user creating the expense
     * @param  array<string, mixed>  $data  Validated expense data from the form layer
     * @param  UploadedFile|null  $receipt  Optional uploaded receipt file
     */
    public function execute(User $user, array $data, ?UploadedFile $receipt = null): Expense
    {
        // TODO:
        // 1. Create Expense with status = Draft, user_id = $user->id, department_id = $user->department_id
        // 2. Map validated data into the expense fields
        // 3. If a receipt exists, store it and assign receipt_path
        // 4. Save the expense and return it
        $expense = Expense::create([
            'title' => $data['title'],
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => ExpenseStatus::Draft,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => $data['description'],
            'category_id' => (int) $data['category_id'],
            'date' => $data['date'],
        ]);
        if ($receipt !== null) {
            // Capture metadata BEFORE storing the file
            $originalName = $receipt->getClientOriginalName();
            $mimeType = $receipt->getMimeType();
            $size = $receipt->getSize();

            // Store the file
            $path = $receipt->store('expenses');

            Attachment::create([
                'attachable_type' => Expense::class,
                'attachable_id' => $expense->id,
                'user_id' => $user->id,
                'path' => $path,
                'disk' => 'local',
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $size,
            ]);
        }

        return $expense;
    }
}
