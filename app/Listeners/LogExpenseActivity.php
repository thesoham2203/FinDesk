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
            $description = "Employee {$user->name} submitted expense: {$expense->title} ({$expense->formattedAmount})";
        } elseif ($event instanceof ExpenseApproved) {
            $action = 'approved';
            $user = $event->approver;
            $description = "Manager {$user->name} approved expense: {$expense->title} ({$expense->formattedAmount})";
        } elseif ($event instanceof ExpenseRejected) {
            $action = 'rejected';
            $user = $event->rejector;
            $description = "Manager {$user->name} rejected expense: {$expense->title} ({$expense->formattedAmount}), Reason: {$event->reason}";
            $properties['rejection_reason'] = $event->reason;
        } elseif ($event instanceof ExpenseReimbursed) {
            $action = 'reimbursed';
            $user = $event->processor;
            $description = "Accountant {$user->name} marked expense as reimbursed: {$expense->title} ({$expense->formattedAmount})";
        }

        if (! $action || ! $user) {
            return;
        }

        Activity::create([
            'user_id' => $user->id,
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
            'description' => $description,
            'properties' => $properties,
        ]);

    }
}
