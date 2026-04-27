<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ExpenseSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Expense $expense) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        // STRETCH: add 'mail' channel for email notifications
        return ['database'];
    }

    /**
     * Get the notification's database representation.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'New Expense Submitted',
            'message' => sprintf('%s submitted "%s" for %s', $this->expense->user->name, $this->expense->title, $this->expense->formattedAmount),
            'expense_id' => $this->expense->id,
            'action_url' => route('expenses.show', $this->expense),
            'submitted_at' => $this->expense->submitted_at?->toDateTimeString(),
        ];
    }
}
