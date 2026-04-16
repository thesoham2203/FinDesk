<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ExpenseRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Expense $expense,
        public User $rejector,
        public string $reason,
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
            'title' => 'Expense Rejected',
            'message' => "Your expense \"{$this->expense->title}\" for {$this->expense->formattedAmount} was rejected by {$this->rejector->name}. Reason: {$this->reason}",
            'expense_id' => $this->expense->id,
            'action_url' => route('expenses.show', $this->expense),
            'rejector_name' => $this->rejector->name,
            'reason' => $this->reason,
            'rejected_at' => $this->expense->reviewed_at?->toDateTimeString(),
        ];
    }
}
