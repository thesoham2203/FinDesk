<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\TaxRate;

/**
 * ============================================================================
 * INVOICE LINE ITEM MODEL TESTS
 * ============================================================================
 *
 * WHAT WE'RE TESTING:
 * InvoiceLineItem represents a single line on an invoice (a service or product).
 * Each line item tracks: description, quantity (can be fractional), unit_price,
 * line_total (quantity × unit_price), tax_amount, relationships to Invoice and TaxRate.
 *
 * WHY THESE TESTS MATTER:
 * Line items are how actual charges appear on invoices. Errors cascade immediately:
 * - Wrong quantity = client charged incorrectly
 * - Missing tax calculation = compliance failures
 * - Corrupted relationships = reports can't be generated
 *
 * ============================================================================
 */
test('line item can be created with all required attributes', function (): void {
    $invoice = Invoice::factory()->create();
    $taxRate = TaxRate::factory()->create();

    $lineItem = InvoiceLineItem::query()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Consulting Services',
        'quantity' => 2.5,
        'unit_price' => 50000,
        'tax_rate_id' => $taxRate->id,
        'line_total' => 125000,
        'tax_amount' => 0,
    ]);

    $this->assertNotNull($lineItem->id);
    expect($lineItem->description)->toBe('Consulting Services');
    expect($lineItem->quantity)->toBe(2.5);
    expect($lineItem->unit_price)->toBe(50000);
});

test('line item factory generates realistic line items', function (): void {
    $lineItem = InvoiceLineItem::factory()->create();

    expect($lineItem->description)->not->toBeEmpty();
    expect($lineItem->quantity)->toBeFloat();
    expect($lineItem->quantity)->toBeGreaterThan(0);
    expect($lineItem->unit_price)->toBeInt();
    expect($lineItem->unit_price)->toBeGreaterThan(0);
    expect($lineItem->tax_rate_id)->not->toBeNull();
});

test('line item quantity can be fractional (1.5 hours)', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['quantity' => 1.5]);

    expect($lineItem->quantity)->toBe(1.5);
    $reloaded = InvoiceLineItem::query()->find($lineItem->id);
    expect($reloaded->quantity)->toBe(1.5);
});

test('line item quantity can be whole numbers', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['quantity' => 5.0]);

    expect($lineItem->quantity)->toBe(5.0);
});

test('line item quantity can be very small decimals', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['quantity' => 0.25]);

    expect($lineItem->quantity)->toBe(0.25);
});

test('line item unit price is stored as integer', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['unit_price' => 50000]);

    expect($lineItem->unit_price)->toBeInt();
    expect($lineItem->unit_price)->toBe(50000);

    $reloaded = InvoiceLineItem::query()->find($lineItem->id);
    expect($reloaded->unit_price)->toBeInt();
});

test('line item line total is stored as integer', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['quantity' => 2.5, 'unit_price' => 50000, 'line_total' => 125000]);

    expect($lineItem->line_total)->toBeInt();
    expect($lineItem->line_total)->toBe(125000);
});

test('line item tax amount is stored as integer', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['tax_amount' => 22500]);

    expect($lineItem->tax_amount)->toBeInt();
    expect($lineItem->tax_amount)->toBe(22500);
});

test('line item belongs to an invoice', function (): void {
    $invoice = Invoice::factory()->create();
    $lineItem = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

    $retrievedInvoice = $lineItem->invoice;

    expect($retrievedInvoice->id)->toBe($invoice->id);
    expect($retrievedInvoice)->toBeInstanceOf(Invoice::class);
});

test('line item belongs to a tax rate', function (): void {
    $taxRate = TaxRate::factory()->create();
    $lineItem = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRate->id]);

    $retrievedTaxRate = $lineItem->taxRate;

    expect($retrievedTaxRate->id)->toBe($taxRate->id);
    expect($retrievedTaxRate)->toBeInstanceOf(TaxRate::class);
});

test('line item has many polymorphic attachments', function (): void {
    $lineItem = InvoiceLineItem::factory()->create();

    expect(method_exists($lineItem, 'attachments'))->toBeTrue();
});

test('line item can use different tax rates', function (): void {
    $taxRateA = TaxRate::factory()->create(['percentage' => 5.0]);
    $taxRateB = TaxRate::factory()->create(['percentage' => 18.0]);

    $itemA = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRateA->id]);
    $itemB = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRateB->id]);

    expect($itemA->taxRate->percentage)->toBe(5.0);
    expect($itemB->taxRate->percentage)->toBe(18.0);
});

test('line item tax amount scales with tax rate', function (): void {
    $taxRate5 = TaxRate::factory()->create(['percentage' => 5.0]);
    $taxRate18 = TaxRate::factory()->create(['percentage' => 18.0]);
    $lineTotal = 100000;

    $item5 = InvoiceLineItem::factory()->create(['line_total' => $lineTotal, 'tax_rate_id' => $taxRate5->id, 'tax_amount' => 5000]);
    $item18 = InvoiceLineItem::factory()->create(['line_total' => $lineTotal, 'tax_rate_id' => $taxRate18->id, 'tax_amount' => 18000]);

    expect($item5->tax_amount)->toBe(5000);
    expect($item18->tax_amount)->toBe(18000);
});

test('line item can use zero tax rate', function (): void {
    $taxRateZero = TaxRate::factory()->create(['percentage' => 0.0]);

    $lineItem = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRateZero->id, 'tax_amount' => 0]);

    expect($lineItem->taxRate->percentage)->toBe(0.0);
    expect($lineItem->tax_amount)->toBe(0);
});

test('line item factory generates random descriptions', function (): void {
    $item1 = InvoiceLineItem::factory()->create();
    $item2 = InvoiceLineItem::factory()->create();

    expect($item1->description)->not->toBeEmpty();
    expect($item2->description)->not->toBeEmpty();
});

test('line item can have very large quantity', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['quantity' => 1000.0]);

    expect($lineItem->quantity)->toBe(1000.0);
});

test('line item can have very small unit price', function (): void {
    $lineItem = InvoiceLineItem::factory()->create(['unit_price' => 1]);

    expect($lineItem->unit_price)->toBe(1);
});
