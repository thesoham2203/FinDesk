<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Livewire\Invoices\InvoiceDetail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the invoice detail component', function () {
    $invoice = Invoice::factory()->create();

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);
});

it('can transition invoice to sent', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('send')
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
});

it('can cancel an invoice with a reason', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->set('cancelReason', 'Customer requested cancellation of the order.')
        ->call('cancelInvoice')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);
    $this->assertDatabaseHas('activities', [
        'subject_id' => $invoice->id,
        'description' => 'Invoice cancelled: Customer requested cancellation of the order.',
    ]);
});

it('validates cancel reason length', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->set('cancelReason', 'short')
        ->call('cancelInvoice')
        ->assertHasErrors(['cancelReason' => 'min']);
});

it('validates cancel reason is required', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->set('cancelReason', '')
        ->call('cancelInvoice')
        ->assertHasErrors(['cancelReason' => 'required']);
});
