<?php

declare(strict_types=1);

/**
 * ExpenseObserver
 *
 * WHAT: Observes Expense model lifecycle events (creating, created, updating, updated, deleting, deleted).
 *       Observers hook into Eloquent lifecycle events, automatically calling their methods.
 *
 * WHY: Observers separate cross-cutting concerns (like event dispatch) from business logic.
 *      When expense status changes, we dispatch the appropriate event instead of doing it inline.
 *      When an expense is deleted, we clean up the receipt file from storage.
 *
 * IMPLEMENT:
 *            - created(Expense $expense): void
 *              Log activity: 'Employee [name] created expense [title]'
 *              This fires when an expense is first created (Draft status)
 *
 *            - updating(Expense $expense): void
 *              Check if 'status' attribute is dirty (changed):
 *              if ($expense->isDirty('status')) {
 *                  Dispatch appropriate event based on NEW status:
 *                  - Submitted → dispatch(new ExpenseSubmitted($expense))
 *                  - Approved → dispatch(new ExpenseApproved($expense, reviewer))
 *                  - Rejected → dispatch(new ExpenseRejected($expense, reviewer, reason))
 *                  - Reimbursed → dispatch(new ExpenseReimbursed($expense, processor))
 *              }
 *              Use $expense->getOriginal('status') for old value, $expense->status for new value
 *
 *            - deleting(Expense $expense): void
 *              Delete the receipt file from storage if receipt_path is set:
 *              Storage::disk('local')->delete($expense->receipt_path)
 *
 * KEY CONCEPTS:
 * - isDirty('status'): Checks if a specific attribute has changed but not saved
 * - getOriginal('status'): Gets the value BEFORE changes
 * - $model->status: Gets the new (current) value
 * - Lifecycle methods: created, creating, updated, updating, deleted, deleting, etc.
 * - Event dispatch: Triggers listeners registered in EventServiceProvider
 * - File cleanup: Storage facade manages file deletion
 *
 * REFERENCE:
 * - https://laravel.com/docs/13.x/eloquent#observers
 * - https://laravel.com/docs/13.x/eloquent#checking-attribute-changes
 * - https://laravel.com/docs/13.x/filesystem
 */

namespace App\Observers;

use App\Enums\ExpenseStatus;
use App\Events\ExpenseApproved;
use App\Events\ExpenseReimbursed;
use App\Events\ExpenseRejected;
use App\Events\ExpenseSubmitted;
use App\Models\Activity;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

final class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        Activity::query()->create([
            'user_id' => $expense->user_id,
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,

            'description' => sprintf('Employee %s created expense: %s', $expense->user->name, $expense->title),
            'properties' => ['amount' => $expense->amount, 'currency' => $expense->currency?->value],
        ]);
    }

    public function updating(Expense $expense): void
    {
        if ($expense->isDirty('status')) {
            if ($expense->status === ExpenseStatus::Submitted) {
                Event::dispatch(new ExpenseSubmitted($expense));
            } elseif ($expense->status === ExpenseStatus::Approved) {
                $approver = User::query()->find($expense->reviewed_by) ?: Auth::user();
                Event::dispatch(new ExpenseApproved($expense, $approver));
            } elseif ($expense->status === ExpenseStatus::Rejected) {
                $rejector = User::query()->find($expense->reviewed_by) ?: Auth::user();
                Event::dispatch(new ExpenseRejected($expense, $rejector, $expense->rejection_reason ?? 'No reason provided'));
            } elseif ($expense->status === ExpenseStatus::Reimbursed) {
                Event::dispatch(new ExpenseReimbursed($expense, Auth::user()));
            }
        }
    }

    public function deleting(Expense $expense): void
    {
        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }
    }
}
