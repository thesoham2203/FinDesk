<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ExpensePartiallyPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Expense $expense,
        public User $approver,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        // TODO: return ['database']
        return ['database'];
    }

    /**
     * Get the notification's database representation.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Expense Partially Paid',
            'message' => sprintf('Your expense "%s" for %s was Partially paid by %s', $this->expense->title, $this->expense->formattedAmount, $this->approver->name),
            'expense_id' => $this->expense->id,
            'action_url' => route('expenses.show', $this->expense),
            'approver_name' => $this->approver->name,
            'approved_at' => $this->expense->reviewed_at?->toDateTimeString(),
        ];
    }
}
