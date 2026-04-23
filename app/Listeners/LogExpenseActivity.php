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
        $action = null;
        $description = null;
        $properties = [];
        $user = null;

        $expense = $event->expense;
        if ($event instanceof ExpenseSubmitted) {
            $action = 'submitted';
            $user = $expense->user;
            $description = sprintf('Employee %s submitted expense: %s (%s)', $user->name, $expense->title, $expense->formattedAmount);
        } elseif ($event instanceof ExpenseApproved) {
            $action = 'approved';
            $user = $event->approver;
            $description = sprintf('Manager %s approved expense: %s (%s)', $user->name, $expense->title, $expense->formattedAmount);
        } elseif ($event instanceof ExpenseRejected) {
            $action = 'rejected';
            $user = $event->rejector;
            $description = sprintf('Manager %s rejected expense: %s (%s), Reason: %s', $user->name, $expense->title, $expense->formattedAmount, $event->reason);
            $properties['rejection_reason'] = $event->reason;
        } elseif ($event instanceof ExpenseReimbursed) {
            $action = 'reimbursed';
            $user = $event->processor;
            $description = sprintf('Accountant %s marked expense as reimbursed: %s (%s)', $user->name, $expense->title, $expense->formattedAmount);
        }

        if (! $user) {
            return;
        }

        Activity::query()->create([
            'user_id' => $user->id,
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
            'description' => $description,
            'properties' => $properties,
        ]);

    }
}
