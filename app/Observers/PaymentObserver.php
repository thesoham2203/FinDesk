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
     */
    public function created(Payment $payment): void
    {
        $invoice = $payment->invoice;

        $totalPaid = $invoice->payments()->sum('amount');
        $allowedTransitions = $invoice->status->allowedTransitions();

        if ($totalPaid >= $invoice->total && in_array(InvoiceStatus::Paid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Paid);
        } elseif ($totalPaid > 0 && in_array(InvoiceStatus::PartiallyPaid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::PartiallyPaid);
        } elseif ($invoice->due_date?->isPast() && in_array(InvoiceStatus::Overdue, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Overdue);
        }

        $invoice->save();

        event(new PaymentRecorded($payment, $invoice));
    }

    /**
     * Handle the Payment "deleted" event (IMPORTANT CHANGE).
     */
    public function deleted(Payment $payment): void
    {
        $invoice = $payment->invoice;

        $totalPaid = $invoice->payments()->sum('amount');
        $allowedTransitions = $invoice->status->allowedTransitions();

        if ($totalPaid >= $invoice->total && in_array(InvoiceStatus::Paid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Paid);

        } elseif ($totalPaid > 0 && in_array(InvoiceStatus::PartiallyPaid, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::PartiallyPaid);

        } elseif ($invoice->due_date?->isPast() && $totalPaid > 0 && in_array(InvoiceStatus::Overdue, $allowedTransitions, true)) {
            $invoice->transitionTo(InvoiceStatus::Overdue);

        } elseif (in_array(InvoiceStatus::Draft, $allowedTransitions, true)) {
            // only fallback if allowed
            $invoice->transitionTo(InvoiceStatus::Draft);
        }

        $invoice->save();
    }
}
