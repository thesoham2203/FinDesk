<?php

declare(strict_types=1);

use App\Livewire\Client\Invoices\InvoiceDetail;
use App\Models\Client;
use App\Models\Invoice;
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
