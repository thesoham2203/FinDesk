<?php

declare(strict_types=1);

namespace App\Actions\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Attachment;
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
        throw_if($expense->status !== ExpenseStatus::Draft, InvalidArgumentException::class, 'Only draft expenses can be updated.');

        $expense->title = $data['title'];
        $expense->amount = $data['amount'];
        $expense->description = $data['description'];
        $expense->date = $data['date'];
        $expense->category_id = (int) $data['category_id'];
        $expense->currency = $data['currency'];

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
            $expense->receipt_path = $path;

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
