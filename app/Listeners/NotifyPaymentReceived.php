<?php

declare(strict_types=1);

/**
 * NotifyPaymentReceived Listener
 *
 * WHAT: Listens for PaymentRecorded event and sends PaymentReceivedNotification
 *       to the invoice creator.
 *
 * WHY: Invoice creators need to know when payments are received. This queued listener
 *      prevents blocking the payment recording request.
 *
 * IMPLEMENT: Listen for PaymentRecorded event, notify the invoice creator with
 *            PaymentReceivedNotification containing payment details and remaining balance.
 *
 * REFERENCE:
 * - Event Listeners: https://laravel.com/docs/13.x/events#event-listeners
 * - Queued Event Listeners: https://laravel.com/docs/13.x/events#queued-event-listeners
 */

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
