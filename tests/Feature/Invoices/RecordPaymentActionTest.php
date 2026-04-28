<?php

declare(strict_types=1);

use App\Actions\Payment\RecordPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records payment successfully for valid statuses', function (InvoiceStatus $status): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => $status,
    ]);

    $action = resolve(RecordPayment::class);
    $payment = $action->execute($invoice, [
        'amount' => 50000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->invoice_id)->toBe($invoice->id)
        ->and($payment->amount)->toBe(50000);
})->with([
    InvoiceStatus::Sent,
    InvoiceStatus::Viewed,
    InvoiceStatus::PartiallyPaid,
    InvoiceStatus::Overdue,
]);

it('throws InvalidArgumentException for unpayable statuses', function (InvoiceStatus $status): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => $status,
    ]);

    $action = resolve(RecordPayment::class);

    expect(function () use ($action, $invoice): void {
        $action->execute($invoice, [
            'amount' => 50000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => PaymentMethod::CreditCard->value,
        ]);
    })->toThrow(InvalidArgumentException::class);
})->with([
    InvoiceStatus::Draft,
    InvoiceStatus::Paid,
    InvoiceStatus::Cancelled,
]);

it('throws InvalidArgumentException on overpayment attempt', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);

    expect(function () use ($action, $invoice): void {
        $action->execute($invoice, [
            'amount' => 150000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => PaymentMethod::CreditCard->value,
        ]);
    })->toThrow(InvalidArgumentException::class, 'exceeds remaining balance');
});

it('allows exact remaining balance payment', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);
    $payment = $action->execute($invoice, [
        'amount' => 100000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    expect($payment->amount)->toBe(100000);
});

it('allows partial payments and balance reduces correctly', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);

    $payment1 = $action->execute($invoice, [
        'amount' => 30000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
    $remaining = $invoice->total - $totalPaid;

    expect($totalPaid)->toBe(30000)
        ->and($remaining)->toBe(70000);

    $payment2 = $action->execute($invoice, [
        'amount' => 50000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
    expect($totalPaid)->toBe(80000);
});

it('creates Payment record with correct attributes', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);
    $paymentDate = '2026-05-15';
    $paymentData = [
        'amount' => 50000,
        'payment_date' => $paymentDate,
        'payment_method' => PaymentMethod::BankTransfer->value,
        'reference_number' => 'REF-12345',
        'notes' => 'Payment received',
    ];

    $payment = $action->execute($invoice, $paymentData);

    expect($payment->invoice_id)->toBe($invoice->id)
        ->and($payment->amount)->toBe(50000)
        ->and($payment->payment_date->format('Y-m-d'))->toBe($paymentDate)
        ->and($payment->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($payment->reference_number)->toBe('REF-12345')
        ->and($payment->notes)->toBe('Payment received');
});

it('handles multiple partial payments in sequence', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);

    $amounts = [20000, 30000, 25000, 25000];
    $payments = [];

    foreach ($amounts as $amount) {
        $payments[] = $action->execute($invoice, [
            'amount' => $amount,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => PaymentMethod::CreditCard->value,
        ]);
    }

    $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
    expect(count($payments))->toBe(4)
        ->and($totalPaid)->toBe(100000);
});

it('prevents overpayment after partial payments', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);

    $action->execute($invoice, [
        'amount' => 70000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    expect(function () use ($action, $invoice): void {
        $action->execute($invoice, [
            'amount' => 50000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => PaymentMethod::CreditCard->value,
        ]);
    })->toThrow(InvalidArgumentException::class);
});

it('accepts optional reference_number and notes', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);
    $payment = $action->execute($invoice, [
        'amount' => 50000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
        'reference_number' => 'REF-XYZ',
        'notes' => 'Late payment',
    ]);

    expect($payment->reference_number)->toBe('REF-XYZ')
        ->and($payment->notes)->toBe('Late payment');
});

it('handles missing optional fields gracefully', function (): void {
    $invoice = Invoice::factory()->create([
        'total' => 100000,
        'status' => InvoiceStatus::Sent,
    ]);

    $action = resolve(RecordPayment::class);
    $payment = $action->execute($invoice, [
        'amount' => 50000,
        'payment_date' => now()->format('Y-m-d'),
        'payment_method' => PaymentMethod::CreditCard->value,
    ]);

    expect($payment->reference_number)->toBeNull()
        ->and($payment->notes)->toBeNull();
});
