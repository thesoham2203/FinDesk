<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * ============================================================================
 * PAYMENT MODEL TESTS
 * ============================================================================
 *
 * WHAT WE'RE TESTING:
 * The Payment model tracks money received from clients against invoices.
 * Key concepts:
 * - One invoice can receive multiple payments (partial payment support)
 * - Each payment stores: amount (integer cents/paise), date, method, optional reference/notes
 * - Payment method is stored as PaymentMethod enum
 * - Observer pattern (Day 5) auto-updates invoice status based on payment total
 *
 * WHY THESE TESTS MATTER:
 * Payment accuracy is non-negotiable in financial software:
 * - Wrong amount = accounting mismatch
 * - Wrong date = revenue recognition errors
 * - Broken status updates = business reports are inaccurate
 *
 * ============================================================================
 */
test('payment can be created with all required attributes', function (): void {
    $invoice = Invoice::factory()->sent()->create();
    $today = now()->format('Y-m-d');

    $payment = Payment::query()->create([
        'invoice_id' => $invoice->id,
        'amount' => 50000,
        'payment_date' => $today,
        'payment_method' => 'bank_transfer',
        'reference_number' => 'TXN-12345',
        'notes' => 'Payment received',
    ]);

    expect($payment->id)->not->toBeNull();
    expect($payment->invoice_id)->toBe($invoice->id);
    expect($payment->amount)->toBe(50000);
    expect($payment->reference_number)->toBe('TXN-12345');
});

test('payment factory generates realistic payments', function (): void {
    $payment = Payment::factory()->create();

    expect($payment->invoice_id)->not->toBeNull();
    expect($payment->amount)->toBeInt();
    expect($payment->amount)->toBeGreaterThan(0);
    expect($payment->payment_method)->not->toBeNull();
    expect($payment->payment_date)->not->toBeNull();
});

test('payment amount is stored as integer', function (): void {
    $payment = Payment::factory()->create(['amount' => 50050]);

    expect($payment->amount)->toBeInt();
    expect($payment->amount)->toBe(50050);

    $reloaded = Payment::query()->find($payment->id);
    expect($reloaded->amount)->toBeInt();
});

test('payment can be created with zero amount (edge case)', function (): void {
    $payment = Payment::factory()->create(['amount' => 0]);

    expect($payment->amount)->toBe(0);
});

test('payment amount can be very large', function (): void {
    $payment = Payment::factory()->create(['amount' => 100000000]);

    expect($payment->amount)->toBe(100000000);
});

test('payment method is cast to PaymentMethod enum', function (): void {
    $payment = Payment::factory()->create(['payment_method' => 'bank_transfer']);

    expect($payment->payment_method)->toBeInstanceOf(PaymentMethod::class);
    expect($payment->payment_method->value)->toBe('bank_transfer');
});

test('payment can use different payment methods', function (): void {
    $paymentBank = Payment::factory()->create(['payment_method' => 'bank_transfer']);
    $paymentCash = Payment::factory()->create(['payment_method' => 'cash']);
    $paymentCheque = Payment::factory()->create(['payment_method' => 'cheque']);

    expect($paymentBank->payment_method->value)->toBe('bank_transfer');
    expect($paymentCash->payment_method->value)->toBe('cash');
    expect($paymentCheque->payment_method->value)->toBe('cheque');
});

test('payment date is required and stored as date', function (): void {
    $date = '2025-01-15';

    $payment = Payment::factory()->create(['payment_date' => $date]);

    expect($payment->payment_date->format('Y-m-d'))->toBe($date);
});

test('payment can be dated in the past', function (): void {
    $payment = Payment::factory()->create(['payment_date' => '2025-01-01']);

    expect($payment->payment_date->format('Y-m-d'))->toBe('2025-01-01');
});

test('payment can be dated today', function (): void {
    $today = now()->format('Y-m-d');

    $payment = Payment::factory()->create(['payment_date' => $today]);

    expect($payment->payment_date->format('Y-m-d'))->toBe($today);
});

test('payment reference number is optional', function (): void {
    $paymentWithRef = Payment::factory()->create(['reference_number' => 'REF-123']);
    $paymentNoRef = Payment::factory()->create(['reference_number' => null]);

    expect($paymentWithRef->reference_number)->toBe('REF-123');
    expect($paymentNoRef->reference_number)->toBeNull();
});

test('payment notes are optional', function (): void {
    $paymentWithNotes = Payment::factory()->create(['notes' => 'Check received in mail']);
    $paymentNoNotes = Payment::factory()->create(['notes' => null]);

    expect($paymentWithNotes->notes)->toBe('Check received in mail');
    expect($paymentNoNotes->notes)->toBeNull();
});

test('payment belongs to an invoice', function (): void {
    $invoice = Invoice::factory()->sent()->create();
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

    $retrievedInvoice = $payment->invoice;

    expect($retrievedInvoice->id)->toBe($invoice->id);
    expect($retrievedInvoice)->toBeInstanceOf(Invoice::class);
});

test('invoice can have multiple payments', function (): void {
    $invoice = Invoice::factory()->sent()->create(['total' => 100000]);

    $payment1 = Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 40000]);
    $payment2 = Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 35000]);
    $payment3 = Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 25000]);

    expect($invoice->payments)->toHaveCount(3);
    $totalPaid = $invoice->payments->sum('amount');
    expect($totalPaid)->toBe(100000);
});

test('payment dates can span multiple dates', function (): void {
    $invoice = Invoice::factory()->sent()->create();

    $payment1 = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_date' => '2025-01-10',
        'amount' => 50000,
    ]);
    $payment2 = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_date' => '2025-01-20',
        'amount' => 50000,
    ]);
    $payment3 = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'payment_date' => '2025-02-05',
        'amount' => 50000,
    ]);

    expect($payment1->payment_date->format('Y-m-d'))->toBe('2025-01-10');
    expect($payment2->payment_date->format('Y-m-d'))->toBe('2025-01-20');
    expect($payment3->payment_date->format('Y-m-d'))->toBe('2025-02-05');
});

test('partial payment can be underpayment (less than invoice total)', function (): void {
    $invoice = Invoice::factory()->sent()->create(['total' => 100000]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 50000,
    ]);

    expect($payment->amount)->toBeLessThan($invoice->total);
    expect($payment->amount)->toBe(50000);
});

test('payment can exceed full invoice amount (overpayment)', function (): void {
    $invoice = Invoice::factory()->sent()->create(['total' => 100000]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 105000,
    ]);

    expect($payment->amount)->toBeGreaterThan($invoice->total);
});

test('payment attributes are cast to correct types', function (): void {
    $payment = Payment::query()->create([
        'invoice_id' => Invoice::factory()->sent()->create()->id,
        'amount' => 50000,
        'payment_date' => '2025-01-15',
        'payment_method' => 'bank_transfer',
        'reference_number' => 'REF-123',
        'notes' => 'Test payment',
    ]);

    expect($payment->amount)->toBeInt();
    // payment_date can be Carbon or CarbonImmutable depending on Laravel version
    expect($payment->payment_date)->toBeInstanceOf(DateTimeInterface::class);
    expect($payment->payment_method)->toBeInstanceOf(PaymentMethod::class);
    expect(is_string($payment->reference_number) || $payment->reference_number === null)->toBeTrue();
    expect(is_string($payment->notes) || $payment->notes === null)->toBeTrue();
});

test('payment factory creates realistic random payments', function (): void {
    $payment = Payment::factory()->create();

    expect($payment->amount)->toBeGreaterThan(10000);
    expect($payment->amount)->toBeLessThan(500000);
});

test('multiple payments to same invoice in same day', function (): void {
    $invoice = Invoice::factory()->sent()->create();
    $today = now()->format('Y-m-d');

    $payment1 = Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_date' => $today, 'amount' => 50000]);
    $payment2 = Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_date' => $today, 'amount' => 50000]);

    expect($payment1->payment_date->format('Y-m-d'))->toBe($today);
    expect($payment2->payment_date->format('Y-m-d'))->toBe($today);
    expect($invoice->payments)->toHaveCount(2);
});

test('payment can have both reference number and notes', function (): void {
    $reference = 'TXN-98765';
    $notes = 'Wire transfer from ABC Corp';

    $payment = Payment::factory()->create([
        'reference_number' => $reference,
        'notes' => $notes,
    ]);

    expect($payment->reference_number)->toBe($reference);
    expect($payment->notes)->toBe($notes);
});
