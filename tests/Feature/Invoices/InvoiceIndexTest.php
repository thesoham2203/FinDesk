<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Livewire\Invoices\InvoiceIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the invoice index component', function (): void {
    Livewire::test(InvoiceIndex::class)
        ->assertStatus(200);
});

it('lists invoices', function (): void {
    $client = Client::factory()->create();
    Invoice::factory()->create(['client_id' => $client->id, 'invoice_number' => 'INV-001']);
    Invoice::factory()->create(['client_id' => $client->id, 'invoice_number' => 'INV-002']);

    Livewire::test(InvoiceIndex::class)
        ->assertSee('INV-001')
        ->assertSee('INV-002');
});

it('filters invoices by search', function (): void {
    Invoice::factory()->create(['invoice_number' => 'INV-ABC']);
    Invoice::factory()->create(['invoice_number' => 'INV-XYZ']);

    Livewire::test(InvoiceIndex::class)
        ->set('search', 'ABC')
        ->assertSee('INV-ABC')
        ->assertDontSee('INV-XYZ');
});

it('filters invoices by status', function (): void {
    Invoice::factory()->create(['status' => InvoiceStatus::Draft, 'invoice_number' => 'INV-DRAFT']);
    Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'invoice_number' => 'INV-SENT']);

    Livewire::test(InvoiceIndex::class)
        ->set('statusFilter', InvoiceStatus::Draft->value)
        ->assertSee('INV-DRAFT')
        ->assertDontSee('INV-SENT');
});

it('filters invoices by client', function (): void {
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();
    Invoice::factory()->create(['client_id' => $client1->id, 'invoice_number' => 'INV-C1']);
    Invoice::factory()->create(['client_id' => $client2->id, 'invoice_number' => 'INV-C2']);

    Livewire::test(InvoiceIndex::class)
        ->set('clientFilter', (string) $client1->id)
        ->assertSee('INV-C1')
        ->assertDontSee('INV-C2');
});

it('filters invoices by date range', function (): void {
    Invoice::factory()->create(['issue_date' => '2023-01-01', 'invoice_number' => 'INV-OLD']);
    Invoice::factory()->create(['issue_date' => '2023-06-01', 'invoice_number' => 'INV-MID']);
    Invoice::factory()->create(['issue_date' => '2023-12-01', 'invoice_number' => 'INV-NEW']);

    Livewire::test(InvoiceIndex::class)
        ->set('dateFrom', '2023-05-01')
        ->set('dateTo', '2023-07-01')
        ->assertSee('INV-MID')
        ->assertDontSee('INV-OLD')
        ->assertDontSee('INV-NEW');
});
