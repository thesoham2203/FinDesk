<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Observers\PaymentObserver;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

final readonly class FakePaymentObserverInvoiceStatus
{
    public function __construct(private array $transitions) {}

    public function allowedTransitions(): array
    {
        return $this->transitions;
    }
}

final readonly class FakePaymentObserverPayments
{
    public function __construct(private int $sum) {}

    public function sum(): int
    {
        return $this->sum;
    }
}

final class FakePaymentObserverInvoice
{
    public ?InvoiceStatus $transitionedTo = null;

    public function __construct(
        public object $status,
        public int $total,
        public ?CarbonInterface $due_date,
        private readonly int $paymentsSum,
    ) {}

    public function payments(): FakePaymentObserverPayments
    {
        return new FakePaymentObserverPayments($this->paymentsSum);
    }

    public function transitionTo(InvoiceStatus $status): void
    {
        $this->transitionedTo = $status;
    }
}

function paymentObserverPaymentWithInvoice(FakePaymentObserverInvoice $invoice): Payment
{
    $payment = Payment::factory()->make();
    $payment->setRelation('invoice', $invoice);

    return $payment;
}

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

    it('payment creation transitions invoice to overdue when unpaid and due date is past', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'due_date' => now()->subDay(),
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 0,
        ]);

        $invoice->refresh();
        expect($invoice->status->value)->toBe(InvoiceStatus::Overdue->value);
    });

    it('paid amount is more than the total amount transitions to paid', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
        ]);
        $invoice->forceFill(['status' => InvoiceStatus::Sent->value])->save();

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 120000,
        ]);

        $invoice->refresh();
        expect($invoice->status->value)->toBe(InvoiceStatus::Paid->value);
    });

    it('deleting a payment from a sent invoice can transition it to partially paid', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Sent,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 60000,
        ]);

        $paymentToDelete = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 40000,
        ]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $paymentToDelete->delete();

        $invoice->refresh();
        expect($invoice->status->value)->toBe(InvoiceStatus::Paid->value)
            ->and($invoice->payments()->sum('amount'))->toBe(60000);
    });

    it('deleting payment keeps status when no transition is allowed', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 100000,
            'status' => InvoiceStatus::Viewed,
            'due_date' => now()->subDay(),
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 70000,
        ]);

        $paymentToDelete = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 30000,
        ]);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $paymentToDelete->delete();

        $invoice->refresh();
        expect($invoice->payments()->sum('amount'))->toBe(70000)
            ->and($invoice->status->value)->toBe(InvoiceStatus::Paid->value);
    });

    it('created with future due date', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'due_date' => now()->addMonth(),
            'status' => InvoiceStatus::Sent,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 0,
        ]);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Sent);
    });

    it('created with overdue but unallowed transition', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'due_date' => now()->subDay(),
            'status' => InvoiceStatus::Paid,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 0,
        ]);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    });

    it('created with overpaid but unallowed transition', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'status' => InvoiceStatus::Cancelled,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1500,
        ]);

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled);
    });

    it('deleted with future due date', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'due_date' => now()->addMonth(),
            'status' => InvoiceStatus::Sent,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 200,
        ]);

        $invoice->refresh();
        // Sum = 700. Status = PartiallyPaid.
        expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);

        // Manually reset to Sent so we can test the transition to PartiallyPaid on deletion
        $invoice->status = InvoiceStatus::Sent;
        $invoice->save();

        // Refresh the payment's invoice relationship to ensure it sees the "Sent" status
        $payment2->unsetRelation('invoice');

        $payment2->delete();

        // Sum = 500. totalPaid > 0. PartiallyPaid allowed from Sent.
        expect($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid);
    });

    it('deleted with total paid zero transitions to draft if allowed', function (): void {
        $invoice = new FakePaymentObserverInvoice(
            status: new FakePaymentObserverInvoiceStatus([InvoiceStatus::Draft]),
            total: 1000,
            due_date: null,
            paymentsSum: 0,
        );

        new PaymentObserver()->deleted(paymentObserverPaymentWithInvoice($invoice));

        expect($invoice->transitionedTo)->toBe(InvoiceStatus::Draft);
    });

    it('deleted with overdue transition allowed', function (): void {
        $invoice = new FakePaymentObserverInvoice(
            status: new FakePaymentObserverInvoiceStatus([InvoiceStatus::Overdue]),
            total: 1000,
            due_date: now()->subDay(),
            paymentsSum: 500,
        );

        new PaymentObserver()->deleted(paymentObserverPaymentWithInvoice($invoice));

        expect($invoice->transitionedTo)->toBe(InvoiceStatus::Overdue);
    });

    it('deleted with overdue but unallowed transition', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'due_date' => now()->subDay(),
            'status' => InvoiceStatus::Sent,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Paid);

        $payment2->delete();

        // sum = 500. isPast = true. Overdue transition from Paid is NOT allowed.
        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    });

    it('deleted with paid transition allowed', function (): void {
        $invoice = Invoice::factory()->create([
            'total' => 1000,
            'status' => InvoiceStatus::Sent,
        ]);

        // First payment makes it Paid
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);

        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Paid);

        // Second payment makes it overpaid (Total = 1500)
        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);

        // Manually reset status to Sent in DB
        $invoice->status = InvoiceStatus::Sent;
        $invoice->save();

        // Refresh the payment's invoice relationship to ensure it sees the "Sent" status
        $payment2->unsetRelation('invoice');

        // Delete the second payment.
        // Sum becomes 1000. 1000 >= 1000.
        // Status is Sent, so Paid transition is allowed.
        $payment2->delete();

        expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
    });
});
