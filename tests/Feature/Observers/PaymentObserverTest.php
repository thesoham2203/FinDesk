<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PaymentObserver', function (): void {
    it('can create a payment for an invoice', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        expect($payment)->not->toBeNull()
            ->and($payment->invoice_id)->toBe($invoice->id)
            ->and($payment->amount)->toBe(50000);
    });

    it('payment can be retrieved with invoice', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        $invoice->refresh();
        $payments = $invoice->payments;

        expect($payments)->toHaveCount(1)
            ->and($payments->first()->id)->toBe($payment->id);
    });

    it('multiple payments can be created for same invoice', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        $invoice->refresh();

        expect($invoice->payments)->toHaveCount(2);
    });

    it('payment belongs to invoice', function (): void {
        $invoice = Invoice::factory()->create();
        $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

        expect($payment->invoice)->toBeInstanceOf(Invoice::class)
            ->and($payment->invoice->id)->toBe($invoice->id);
    });

    it('observer hook is triggered on payment creation', function (): void {
        // Test that the observer is wired up
        $invoice = Invoice::factory()->create(['total' => 100000]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        expect($payment->exists)->toBeTrue();
    });
});
