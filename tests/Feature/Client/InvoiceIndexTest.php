<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Livewire\Client\Invoices\InvoiceIndex;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('client invoice index renders and filters invoices', function (): void {
    $client = Client::factory()->create();
    $matchingInvoice = Invoice::factory()->for($client)->create([
        'invoice_number' => 'INV-2026-0001',
        'status' => InvoiceStatus::Sent,
    ]);
    $nonMatchingInvoice = Invoice::factory()->for($client)->create([
        'invoice_number' => 'INV-2026-0002',
        'status' => InvoiceStatus::Draft,
    ]);
    $otherClient = Client::factory()->create();
    Invoice::factory()->for($otherClient)->create([
        'invoice_number' => 'INV-2026-9999',
        'status' => InvoiceStatus::Sent,
    ]);

    Livewire::actingAs($client, 'client')
        ->test(InvoiceIndex::class)
        ->set('search', '2026-0001')
        ->set('statusFilter', InvoiceStatus::Sent->value)
        ->assertSee($matchingInvoice->invoice_number)
        ->assertDontSee($nonMatchingInvoice->invoice_number)
        ->assertDontSee('INV-2026-9999');
});
