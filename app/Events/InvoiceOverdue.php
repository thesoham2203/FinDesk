<?php

declare(strict_types=1);

/**
 * InvoiceOverdue Event
 *
 * WHAT: Fired when the scheduled command marks an invoice as overdue.
 *
 * WHY: Accountants and admins need to be notified when invoices become overdue.
 *      Using an event allows the notification logic to be decoupled from the command.
 *
 * IMPLEMENT: Simple data holder. The CheckOverdueInvoices command fires this event,
 *            and listeners send notifications and log activity.
 *
 * REFERENCE:
 * - Events & Listeners: https://laravel.com/docs/13.x/events
 */

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class InvoiceOverdue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}
}
