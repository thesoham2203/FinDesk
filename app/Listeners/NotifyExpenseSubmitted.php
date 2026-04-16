<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ExpenseSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyExpenseSubmitted implements ShouldQueue
{
    /**
     * Handle the event: notify the manager of the new submission.
     */
    public function handle(ExpenseSubmitted $event): void
    {
        $manager = $event->expense->user->manager;
        if ($manager) {
            $manager->notify(new ExpenseSubmitted($event->expense));
        }

    }
}
