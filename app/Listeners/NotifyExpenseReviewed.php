<?php

declare(strict_types=1);

/**
 * NotifyExpenseReviewed Listener
 *
 * WHAT: Sends a notification to the employee when their expense is approved or rejected.
 *
 * WHY: Employees need feedback on whether their expense was approved or rejected.
 *      If rejected, they receive the reason so they can resubmit with corrections.
 *      The notification is QUEUED for background processing.
 *
 * IMPLEMENT: In the handle() method:
 *            1. Get the employee: $event->expense->user
 *            2. If event is ExpenseApproved:
 *               $employee->notify(new ExpenseApprovedNotification($event->expense, $event->approver))
 *            3. If event is ExpenseRejected:
 *               $employee->notify(new ExpenseRejectedNotification($event->expense, $event->rejector, $event->reason))
 *
 *            Note: Notification classes are created in Step 3.
 *
 * KEY CONCEPTS:
 * - ShouldQueue: Makes the listener async
 * - instanceof checks: Handles multiple event types in a single listener
 * - Type safety: Each notification receives the appropriate event data
 * - Employee feedback loop: Rejection reason enables the employee to understand issues
 *
 * REFERENCE:
 * - https://laravel.com/docs/13.x/events#defining-listeners
 * - https://laravel.com/docs/13.x/notifications#sending-notifications
 */

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
