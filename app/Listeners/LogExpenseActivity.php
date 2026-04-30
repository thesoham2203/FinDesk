<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ExpenseApproved;
use App\Events\ExpenseReimbursed;
use App\Events\ExpenseRejected;
use App\Events\ExpenseSubmitted;
use App\Models\Activity;
use App\Models\Expense;

final class LogExpenseActivity
{
    /**
     * Handle the event: determine action type and create an Activity entry.
     *
     * @param  ExpenseSubmitted|ExpenseApproved|ExpenseRejected|ExpenseReimbursed  $event
     */
    public function handle(object $event): void
    {
        $expense = $event->expense;

        [$action, $user, $description, $properties] = match (true) {
            $event instanceof ExpenseSubmitted => [
                'submitted',
                $expense->user,
                sprintf('Employee %s submitted expense: %s (%s)', $expense->user->name, $expense->title, $expense->formatted_amount),
                [],
            ],
            $event instanceof ExpenseApproved => [
                'approved',
                $event->approver,
                sprintf('Manager %s approved expense: %s (%s)', $event->approver->name, $expense->title, $expense->formatted_amount),
                [],
            ],
            $event instanceof ExpenseRejected => [
                'rejected',
                $event->rejector,
                sprintf('Manager %s rejected expense: %s (%s), Reason: %s', $event->rejector->name, $expense->title, $expense->formatted_amount, $event->reason),
                ['rejection_reason' => $event->reason],
            ],
            $event instanceof ExpenseReimbursed => [
                'reimbursed',
                $event->processor,
                sprintf('Accountant %s marked expense as reimbursed: %s (%s)', $event->processor->name, $expense->title, $expense->formatted_amount),
                [],
            ],
            default => [null, null, null, []],
        };

        if ($user !== null && $action !== null) {
            Activity::query()->create([
                'user_id' => $user->id,
                'subject_type' => Expense::class,
                'subject_id' => $expense->id,
                'description' => $description,
                'properties' => $properties,
            ]);
        }
    }
}
