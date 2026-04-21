<?php

declare(strict_types=1);

/**
 * LogPaymentActivity Listener
 *
 * WHAT: Listens for PaymentRecorded event and logs the payment action to the activity log.
 *
 * WHY: Every business action on an invoice should be audited. This listener creates
 *      an Activity record for reconciliation and compliance.
 *
 * IMPLEMENT: Listen for PaymentRecorded event, create Activity record with details
 *            of the payment (amount, method, reference).
 *
 * REFERENCE:
 * - Event Listeners: https://laravel.com/docs/13.x/events#event-listeners
 * - Activity Model: App\Models\Activity (polymorphic audit log)
 */

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Models\Activity;

final class LogPaymentActivity
{
    /**
     * Log payment activity to the activity table.
     */
    public function handle(PaymentRecorded $event): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => \App\Models\Invoice::class,
            'subject_id' => $event->invoice->id,
            'description' => sprintf(
                'Payment of ₹%s recorded via %s',
                number_format($event->payment->amount / 100, 2),
                $event->payment->payment_method->label()
            ),
            'properties' => [
                'payment_id' => $event->payment->id,
                'payment_amount' => $event->payment->amount,
                'payment_method' => $event->payment->payment_method->value,
                'reference_number' => $event->payment->reference_number,
            ],
        ]);
    }
}
