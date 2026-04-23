<?php

declare(strict_types=1);

use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use App\Rules\TaxRateNotInUse;

test('tax rate without line items passes validation', function (): void {
    $taxRate = TaxRate::factory()->create();
    $rule = new TaxRateNotInUse();

    $fails = [];
    $rule->validate('tax_rate_id', $taxRate->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('tax rate with line items fails validation', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRate->id]);

    $rule = new TaxRateNotInUse();
    $fails = [];
    $rule->validate('tax_rate_id', $taxRate->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
    expect($fails[0])->toContain('cannot be deleted');
});

test('tax rate with multiple line items fails validation with correct count', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory(3)->create(['tax_rate_id' => $taxRate->id]);

    $rule = new TaxRateNotInUse();
    $fails = [];
    $rule->validate('tax_rate_id', $taxRate->id, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
    expect($fails[0])->toContain('3');
});
