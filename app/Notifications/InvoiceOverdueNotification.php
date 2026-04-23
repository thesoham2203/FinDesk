<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the notification's database representation.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        // Calculate days overdue
        $daysOverdue = $this->invoice->due_date->diffInDays(now());

        return [
            'title' => 'Invoice Overdue',
            'message' => sprintf(
                'Invoice %s for %s (₹%s) is %d days overdue (Due: %s)',
                $this->invoice->invoice_number,
                $this->invoice->client->name,
                number_format($this->invoice->total / 100, 2),
                $daysOverdue,
                $this->invoice->due_date->format('M d, Y')
            ),
            'invoice_id' => $this->invoice->id,
            'client_name' => $this->invoice->client->name,
            'invoice_amount' => $this->invoice->total,
            'due_date' => $this->invoice->due_date->toDateString(),
            'days_overdue' => $daysOverdue,
            'action_url' => route('invoices.show', $this->invoice),
        ];
    }
}
