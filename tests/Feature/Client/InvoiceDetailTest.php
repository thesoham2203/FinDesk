<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Livewire\Client\Invoices\InvoiceDetail;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

test('client can download an invoice pdf', function (): void {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->for($client)->create();

    $filename = sprintf('invoice-%s-%s.pdf', $invoice->invoice_number, now()->format('Y-m-d'));

    Livewire::actingAs($client, 'client')
        ->test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('downloadPdf')
        ->assertFileDownloaded($filename);
});

test('client can mark a sent invoice as viewed', function (): void {
    $client = Client::factory()->create();
    User::factory()->create(['id' => $client->id]);
    $invoice = Invoice::factory()->for($client)->sent()->create();

    Livewire::actingAs($client, 'client')
        ->test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('markAsViewed');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Viewed);

    $activity = Activity::query()
        ->where('subject_type', Invoice::class)
        ->where('subject_id', $invoice->id)
        ->where('description', 'Invoice marked as viewed by client')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Invoice marked as viewed by client')
        ->and($activity->properties['client_id'])->toBe($client->id);
});

test('client cannot mark a non-sent invoice as viewed', function (): void {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->for($client)->create(['status' => InvoiceStatus::Draft]);

    Livewire::actingAs($client, 'client')
        ->test(InvoiceDetail::class, ['invoice' => $invoice])
        ->call('markAsViewed')
        ->assertDispatched('flash', type: 'error');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
});
