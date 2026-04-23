<?php

declare(strict_types=1);


namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyPaymentReceived implements ShouldQueue
{
    /**
     * Send payment received notification to the invoice creator.
     */
    public function handle(PaymentRecorded $event): void
    {
        // Notify the invoice creator
        $event->invoice->creator->notify(
            new PaymentReceivedNotification($event->payment, $event->invoice)
        );
    }
}
