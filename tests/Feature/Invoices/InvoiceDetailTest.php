<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Livewire\Invoices\InvoiceDetail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the invoice detail component', function (): void {
    $invoice = Invoice::factory()->create();

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->assertStatus(200)
        ->assertSee($invoice->invoice_number);
});

it('can transition invoice to sent', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('send')
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
});

it('can cancel an invoice with a reason', function (): void {
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

it('validates cancel reason length', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->set('cancelReason', 'short')
        ->call('cancelInvoice')
        ->assertHasErrors(['cancelReason' => 'min']);
});

it('validates cancel reason is required', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->set('cancelReason', '')
        ->call('cancelInvoice')
        ->assertHasErrors(['cancelReason' => 'required']);
});

it('opens cancel modal when requested', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('openCancelModal')
        ->assertSet('showCancelModal', true);
});

it('returns early in cancelInvoice when user id is missing', function (): void {
    Gate::shouldReceive('authorize')->andReturnTrue();

    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    $component = Livewire::actingAs($this->admin)->test(InvoiceDetail::class, ['invoice' => $invoice]);
    auth()->logout();

    $component->set('cancelReason', 'Customer asked to cancel this invoice.')
        ->call('cancelInvoice');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);
    $this->assertDatabaseMissing('activities', [
        'subject_id' => $invoice->id,
        'description' => 'Invoice cancelled: Customer asked to cancel this invoice.',
    ]);
});
