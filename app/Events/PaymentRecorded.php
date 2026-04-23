<?php

declare(strict_types=1);

/**
 * PaymentRecorded Event
 *
 * WHAT: Fired when a payment is successfully recorded against an invoice.
 *
 * WHY: Multiple listeners need to react to payment creation: log activity, send notifications
 *      to the invoice creator. Using events decouples these responsibilities.
 *
 * IMPLEMENT: Simple data holder with no logic. Listeners subscribe to this event
 *            in the service provider (EventServiceProvider or auto-discovered).
 *
 * REFERENCE:
 * - Events & Listeners: https://laravel.com/docs/13.x/events
 */

namespace App\Events;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaymentRecorded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public Invoice $invoice,
    ) {}
}
