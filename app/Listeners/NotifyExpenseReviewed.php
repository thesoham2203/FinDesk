<?php

declare(strict_types=1);


namespace App\Listeners;

use App\Events\ExpenseApproved;
use App\Events\ExpenseRejected;
use App\Notifications\ExpenseApprovedNotification;
use App\Notifications\ExpenseRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyExpenseReviewed implements ShouldQueue
{
    /**
     * Handle the event: notify the employee of approval or rejection.
     *
     * @param  ExpenseApproved|ExpenseRejected  $event
     */
    public function handle(object $event): void
    {
        $employee = $event->expense->user;
        if ($event instanceof ExpenseApproved) {
            $employee->notify(new ExpenseApprovedNotification($event->expense, $event->approver));
        } elseif ($event instanceof ExpenseRejected) {
            $employee->notify(new ExpenseRejectedNotification($event->expense, $event->rejector, $event->reason));
        }
    }
}
