<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use App\Models\Attachment;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class UpdateExpense
{
    /**
     * Update an existing draft expense.
     *
     * @param  Expense  $expense  The expense being edited
     * @param  array{title: string, amount: int, currency: string, description?: string|null, date: string, category_id: int|string}  $data  Validated expense data from the form layer
     * @param  UploadedFile|null  $receipt  Optional replacement receipt file
     */
    public function execute(Expense $expense, array $data, ?UploadedFile $receipt = null): Expense
    {
        throw_if($expense->status !== ExpenseStatus::Draft, InvalidArgumentException::class, 'Only draft expenses can be updated.');

        /** @var string $title */
        $title = $data['title'];
        $expense->title = $title;

        /** @var int $amount */
        $amount = $data['amount'];
        $expense->amount = $amount;

        /** @var string|null $description */
        $description = $data['description'] ?? null;
        $expense->description = $description;

        /** @var Carbon $date */
        $date = Date::parse($data['date']);
        $expense->date = $date;

        $expense->category_id = (int) $data['category_id'];

        /** @var string $currency */
        $currency = $data['currency'];
        $expense->currency = Currency::from($currency);

        if ($receipt instanceof UploadedFile) {
            // Delete old file from receipt_path if it exists
            if ($expense->receipt_path) {
                Storage::delete($expense->receipt_path);
            }

            // Also delete all current attachments
            foreach ($expense->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            }

            // Capture metadata BEFORE storing the file
            $originalName = $receipt->getClientOriginalName();
            $mimeType = $receipt->getMimeType();
            $size = $receipt->getSize();

            // Store the file in 'receipts' as expected by tests
            $path = $receipt->store('receipts');
            $expense->receipt_path = $path !== false ? $path : null;
            // Create new attachment record with captured metadata
            Attachment::query()->create([
                'attachable_type' => Expense::class,
                'attachable_id' => $expense->id,
                'user_id' => $expense->user_id,
                'path' => $path,
                'disk' => 'local',
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $size,
            ]);
        }

        $expense->save();

        return $expense;
    }
}
