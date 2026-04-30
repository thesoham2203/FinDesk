<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\Currency;
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
     * @param  array{title: string, amount: int, currency: string|Currency, description?: string|null, date: string, category_id?: int|string|null}  $data  Validated expense data from the form layer
     * @param  UploadedFile|null  $receipt  Optional uploaded receipt file
     */
    public function execute(User $user, array $data, ?UploadedFile $receipt = null): Expense
    {
        /** @var string $title */
        $title = $data['title'];
        /** @var int $amount */
        $amount = $data['amount'];
        /** @var string|Currency $currency */
        $currency = $data['currency'];
        /** @var string|null $description */
        $description = $data['description'] ?? null;
        /** @var string $date */
        $date = $data['date'];

        $expense = Expense::query()->create([
            'title' => $title,
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => ExpenseStatus::Draft,
            'amount' => $amount,
            'currency' => $currency instanceof Currency ? $currency : Currency::from($currency),
            'description' => $description,
            'category_id' => isset($data['category_id']) ? (int) $data['category_id'] : null,
            'date' => $date,
        ]);

        if ($receipt instanceof UploadedFile) {
            // Capture metadata BEFORE storing the file
            $originalName = $receipt->getClientOriginalName();
            $mimeType = $receipt->getMimeType();
            $size = $receipt->getSize();

            // Store the file
            $path = $receipt->store('expenses');

            Attachment::query()->create([
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
