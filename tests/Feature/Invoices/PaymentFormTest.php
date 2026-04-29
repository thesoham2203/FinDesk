<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Livewire\Invoices\PaymentForm;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the payment form component', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(PaymentForm::class, ['invoiceId' => $invoice->id])
        ->assertStatus(200);
});

it('can record a payment', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'total' => 10000, // $100.00
    ]);

    Livewire::test(PaymentForm::class, ['invoiceId' => $invoice->id])
        ->set('amount', '50.00')
        ->set('paymentMethod', PaymentMethod::BankTransfer->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('payment-recorded')
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);
    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'amount' => 5000,
    ]);
});

it('can record full payment', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'total' => 10000,
    ]);

    Livewire::test(PaymentForm::class, ['invoiceId' => $invoice->id])
        ->set('amount', '100.00')
        ->set('paymentMethod', PaymentMethod::CreditCard->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid);
});

it('validates overpayment', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'total' => 10000,
    ]);

    Livewire::test(PaymentForm::class, ['invoiceId' => $invoice->id])
        ->set('amount', '150.00')
        ->set('paymentMethod', PaymentMethod::Cash->value)
        ->call('save')
        ->assertDispatched('flash', type: 'error');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
});

it('validates required fields', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(PaymentForm::class, ['invoiceId' => $invoice->id])
        ->set('amount', '')
        ->set('paymentMethod', '')
        ->call('save')
        ->assertHasErrors(['amount' => 'required', 'paymentMethod' => 'required']);
});
