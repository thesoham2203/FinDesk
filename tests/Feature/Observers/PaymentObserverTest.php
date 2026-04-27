<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
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
        $invoice = Invoice::factory()->create(['total' => 100000]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        expect($payment->exists)->toBeTrue();
    });

    it('payment creation transitions invoice to fully paid from sent status', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        $invoice->refresh();
        expect($invoice->status->value)->toBe(InvoiceStatus::Paid->value);
    });

    it('payment creation transitions invoice to partially paid from sent status', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 60000,
        ]);

        $invoice->refresh();
        expect($invoice->status->value)->toBe(InvoiceStatus::PartiallyPaid->value);
    });

    it('payment creation transitions invoice to partially paid even when past due date', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'due_date' => now()->subDays(1),
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        // Even though invoice is past due, a partial payment transitions to PartiallyPaid
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 30000,
        ]);

        $invoice->refresh();
        // PartiallyPaid takes priority over Overdue in observer logic
        expect($invoice->status->value)->toBe(InvoiceStatus::PartiallyPaid->value);
    });

    it('deleting a payment removes it from invoice', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Paid,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);

        expect($invoice->payments)->toHaveCount(1);
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment->delete();

        $invoice->refresh();
        expect($invoice->payments)->toHaveCount(0);
    });

    it('deleting payment from paid with remaining balance', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Paid,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 60000,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 40000,
        ]);
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment->delete();
        $invoice->refresh();

        // After deleting, only 60k paid of 100k total
        expect($invoice->payments()->sum('amount'))->toBe(60000);
    });

    it('deleting all payments from invoice', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Paid,
        ]);

        $payment1 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $payment1->delete();
        $invoice->refresh();
        expect($invoice->payments()->sum('amount'))->toBe(50000);

        $payment2->delete();
        $invoice->refresh();
        expect($invoice->payments()->sum('amount'))->toBe(0);
    });

    it('deleting payment from cancelled stays cancelled', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Cancelled,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100000,
        ]);
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment->delete();
        $invoice->refresh();

        expect($invoice->status->value)->toBe(InvoiceStatus::Cancelled->value);
    });

    it('multiple payments creation and deletion workflow', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        // Add first payment
        $payment1 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        $invoice->refresh();
        expect($invoice->payments()->sum('amount'))->toBe(50000);

        // Add second payment
        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
        ]);

        $invoice->refresh();
        expect($invoice->payments()->sum('amount'))->toBe(100000);

        // Delete second payment
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $payment2->delete();
        $invoice->refresh();

        expect($invoice->payments()->sum('amount'))->toBe(50000);
    });
});
