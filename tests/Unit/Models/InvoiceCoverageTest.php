<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;

it('formats invoice amounts using currency symbol', function (): void {
    $invoice = Invoice::factory()->create([
        'subtotal' => 12345,
        'tax_total' => 2345,
        'total' => 14690,
        'currency' => 'INR',
    ]);

    expect($invoice->formatted_subtotal)->toBe('₹ 123.45')
        ->and($invoice->formatted_tax_total)->toBe('₹ 23.45')
        ->and($invoice->formatted_total)->toBe('₹ 146.90');
});

it('recalculates totals and computes amount due', function (): void {
    $invoice = Invoice::factory()->create();

    InvoiceLineItem::factory()->create([
        'invoice_id' => $invoice->id,
        'line_total' => 10000,
        'tax_amount' => 1000,
    ]);

    InvoiceLineItem::factory()->create([
        'invoice_id' => $invoice->id,
        'line_total' => 5000,
        'tax_amount' => 500,
    ]);

    $invoice->recalculateTotals();
    $invoice->refresh();

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 3000,
    ]);

    $invoice->refresh();

    expect($invoice->subtotal)->toBe(15000)
        ->and($invoice->tax_total)->toBe(1500)
        ->and($invoice->total)->toBe(16500)
        ->and($invoice->amount_due)->toBe(13500);
});

it('throws for invalid status transitions with allowed transitions in message', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    expect(fn () => $invoice->transitionTo(InvoiceStatus::Paid))
        ->toThrow(InvalidArgumentException::class, 'Allowed transitions: sent');
});
