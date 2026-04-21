<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Events\PaymentRecorded;
use App\Models\Payment;

final class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     * Fires when a payment is recorded; recalculate and update invoice status.
     */
    public function created(Payment $payment): void
    {
        $invoice = $payment->invoice;
        $totalPaid = $invoice->payments()->sum('amount');
        $allowedTransitions = $invoice->status->allowedTransitions();

        // Only attempt status transitions if the invoice is in a valid state for payments
        if ($totalPaid >= $invoice->total && in_array(InvoiceStatus::Paid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Paid);
        } elseif ($totalPaid > 0 && in_array(InvoiceStatus::PartiallyPaid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::PartiallyPaid);
        } elseif ($invoice->due_date < now() && in_array(InvoiceStatus::Overdue, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Overdue);
        }
        $invoice->save();

        // Dispatch event for listeners to react (notifications, activity logging)
        PaymentRecorded::dispatch($payment, $invoice);
    }

    /**
     * Handle the Payment "deleting" event.
     * Fires BEFORE a payment is deleted; recalculate and revert invoice status.
     */
    public function deleting(Payment $payment): void
    {
        $invoice = $payment->invoice;
        $remainingPaid = $invoice->payments()->where('id', '!=', $payment->id)->sum('amount');
        $allowedTransitions = $invoice->status->allowedTransitions();

        // Only attempt status transitions if the invoice is in a valid state for payments
        if ($remainingPaid >= $invoice->total && in_array(InvoiceStatus::Paid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Paid);
        } elseif ($remainingPaid > 0 && in_array(InvoiceStatus::PartiallyPaid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::PartiallyPaid);
        } elseif ($invoice->due_date < now() && in_array(InvoiceStatus::Overdue, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Overdue);
        }
        $invoice->save();
    }
}
