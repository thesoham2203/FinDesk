<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Livewire\Invoices\InvoiceForm;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the invoice form component', function () {
    Livewire::test(InvoiceForm::class)
        ->assertStatus(200);
});

it('creates a new invoice', function () {
    $client = Client::factory()->create();
    $taxRate = TaxRate::factory()->create(['percentage' => 10, 'is_active' => true]);

    Livewire::test(InvoiceForm::class)
        ->set('clientId', (string) $client->id)
        ->set('lineItems.0.description', 'Test Item')
        ->set('lineItems.0.quantity', '2')
        ->set('lineItems.0.unit_price', '100')
        ->set('lineItems.0.tax_rate_id', (string) $taxRate->id)
        ->call('save')
        ->assertDispatched('flash', type: 'success');

    $this->assertDatabaseHas('invoices', [
        'client_id' => $client->id,
        'subtotal' => 20000, // 2 * 100 * 100
        'tax_total' => 2000,  // 10% of 20000
        'total' => 22000,
    ]);
});

it('calculates totals correctly when adding line items', function () {
    $component = Livewire::test(InvoiceForm::class);

    $component->set('lineItems.0.quantity', '1')
        ->set('lineItems.0.unit_price', '100');

    $component->call('addLineItem');

    $component->set('lineItems.1.quantity', '2')
        ->set('lineItems.1.unit_price', '50');

    expect($component->get('subtotal'))->toBe(20000); // 10000 + 10000
});

it('removes a line item', function () {
    $component = Livewire::test(InvoiceForm::class);
    $component->call('addLineItem');

    expect($component->get('lineItems'))->toHaveCount(2);

    $component->call('removeLineItem', 0);

    expect($component->get('lineItems'))->toHaveCount(1);
});

it('cannot remove the last line item', function () {
    Livewire::test(InvoiceForm::class)
        ->call('removeLineItem', 0)
        ->assertDispatched('flash', type: 'error');
});

it('updates an existing draft invoice', function () {
    $invoice = Invoice::factory()->hasLineItems(1)->create(['status' => InvoiceStatus::Draft]);

    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->set('notes', 'Updated Notes')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $invoice->refresh();
    expect($invoice->notes)->toBe('Updated Notes');
});

it('cannot update a non-draft invoice', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);

    Livewire::test(InvoiceForm::class, ['invoice' => $invoice])
        ->assertStatus(403);
});
