<?php

declare(strict_types=1);

use App\Events\InvoiceOverdue;
use App\Models\Invoice;

describe('InvoiceOverdue Event', function (): void {
    it('can create an event with invoice', function (): void {
        $invoice = Invoice::factory()->create();

        $event = new InvoiceOverdue($invoice);

        expect($event->invoice->id)->toBe($invoice->id);
    });

    it('has correct properties', function (): void {
        $invoice = Invoice::factory()->create();

        $event = new InvoiceOverdue($invoice);

        expect($event)->toHaveProperties(['invoice']);
    });

    it('can be serialized for queuing', function (): void {
        $invoice = Invoice::factory()->create();

        $event = new InvoiceOverdue($invoice);

        // Test that the event can be serialized (for queue broadcasting)
        $serialized = serialize($event);
        expect($serialized)->toBeString();

        $unserialized = unserialize($serialized);
        expect($unserialized->invoice->id)->toBe($invoice->id);
    });

    it('contains the correct invoice data after serialization', function (): void {
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-2026-0001',
            'total' => 100000,
        ]);

        $event = new InvoiceOverdue($invoice);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        expect($unserialized->invoice->invoice_number)->toBe('INV-2026-0001')
            ->and($unserialized->invoice->total)->toBe(100000);
    });
});
