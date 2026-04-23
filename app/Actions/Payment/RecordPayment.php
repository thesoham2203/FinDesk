<?php

declare(strict_types=1);


namespace App\Actions\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use InvalidArgumentException;

final class RecordPayment
{
    /**
     * Record a payment against an invoice.
     *
     * @param  array{amount: int, payment_date: string, payment_method: string, reference_number?: string, notes?: string}  $data
     *
     * @throws InvalidArgumentException if invoice cannot receive payments or overpayment attempted
     */
    public function execute(Invoice $invoice, array $data): Payment
    {
        // Check if invoice status allows payments
        $payableStatuses = [
            \App\Enums\InvoiceStatus::Sent,
            \App\Enums\InvoiceStatus::Viewed,
            \App\Enums\InvoiceStatus::PartiallyPaid,
            \App\Enums\InvoiceStatus::Overdue,
        ];

        if (! in_array($invoice->status, $payableStatuses, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invoice %s with status %s cannot receive payments',
                    $invoice->invoice_number,
                    $invoice->status->label()
                )
            );
        }

        // Calculate remaining balance in cents
        $totalPaid = $invoice->payments()->sum('amount');
        $remaining = $invoice->total - $totalPaid;

        // Prevent overpayment
        if ($data['amount'] > $remaining) {
            throw new InvalidArgumentException(
                sprintf(
                    'Payment of ₹%s exceeds remaining balance of ₹%s',
                    number_format($data['amount'] / 100, 2),
                    number_format($remaining / 100, 2)
                )
            );
        }

        // Create the payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // PaymentObserver::created() fires and auto-updates invoice status via transitionTo()
        return $payment;
    }
}
