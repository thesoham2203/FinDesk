<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public Invoice $invoice,
    ) {}

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
        // Calculate remaining balance
        $totalPaid = $this->invoice->payments()->sum('amount');
        $remaining = $this->invoice->total - $totalPaid;

        return [
            'title' => 'Payment Received',
            'message' => sprintf(
                'Payment of ₹%s received for invoice %s. Remaining balance: ₹%s',
                number_format($this->payment->amount / 100, 2),
                $this->invoice->invoice_number,
                number_format($remaining / 100, 2)
            ),
            'invoice_id' => $this->invoice->id,
            'payment_id' => $this->payment->id,
            'payment_method' => $this->payment->payment_method->label(),
            'payment_amount' => $this->payment->amount,
            'remaining_balance' => $remaining,
            'action_url' => route('invoices.show', $this->invoice),
        ];
    }
}
