<?php

declare(strict_types=1);

use App\Models\InvoiceLineItem;
use App\Models\TaxRate;

describe('TaxRate Model', function (): void {
    test('tax rate can be created with factory', function (): void {
        $taxRate = TaxRate::factory()->create([
            'name' => 'GST',
            'percentage' => 18.0,
            'is_default' => true,
            'is_active' => true,
        ]);

        expect($taxRate->name)->toBe('GST')
            ->and($taxRate->percentage)->toBe(18.0)
            ->and($taxRate->is_default)->toBeTrue()
            ->and($taxRate->is_active)->toBeTrue();
    });

    test('tax rate percentage is cast to float', function (): void {
        $taxRate = TaxRate::factory()->create(['percentage' => 5.0]);

        expect($taxRate->percentage)->toBeFloat()
            ->and($taxRate->percentage)->toBe(5.0);
    });

    test('tax rate is_default is cast to boolean', function (): void {
        $taxRate = TaxRate::factory()->create(['is_default' => true]);

        expect($taxRate->is_default)->toBeBool()
            ->and($taxRate->is_default)->toBeTrue();
    });

    test('tax rate is_active is cast to boolean', function (): void {
        $taxRate = TaxRate::factory()->create(['is_active' => false]);

        expect($taxRate->is_active)->toBeBool()
            ->and($taxRate->is_active)->toBeFalse();
    });

    test('tax rate has many line items', function (): void {
        $taxRate = TaxRate::factory()->create();
        $lineItem1 = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRate->id]);
        $lineItem2 = InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRate->id]);

        $lineItems = $taxRate->lineItems;

        expect($lineItems)->toHaveCount(2)
            ->and($lineItems->pluck('id')->toArray())
            ->toContain($lineItem1->id, $lineItem2->id);
    });

    test('active scope filters to active tax rates only', function (): void {
        $activeTaxRate = TaxRate::factory()->create(['is_active' => true]);
        $inactiveTaxRate = TaxRate::factory()->create(['is_active' => false]);

        $activeTaxRates = TaxRate::query()->active()->get();

        expect($activeTaxRates->pluck('id'))->toContain($activeTaxRate->id)
            ->and($activeTaxRates->pluck('id'))->not->toContain($inactiveTaxRate->id);
    });

    test('default scope filters to default tax rate', function (): void {
        $defaultTaxRate = TaxRate::factory()->create(['is_default' => true]);
        $nonDefaultTaxRate = TaxRate::factory()->create(['is_default' => false]);

        $defaultTaxRates = TaxRate::query()->default()->get();

        expect($defaultTaxRates->pluck('id'))->toContain($defaultTaxRate->id)
            ->and($defaultTaxRates->pluck('id'))->not->toContain($nonDefaultTaxRate->id);
    });

    test('can combine active and default scopes', function (): void {
        $activeDefault = TaxRate::factory()->create(['is_active' => true, 'is_default' => true]);
        $inactiveDefault = TaxRate::factory()->create(['is_active' => false, 'is_default' => true]);
        $activeNonDefault = TaxRate::factory()->create(['is_active' => true, 'is_default' => false]);

        $result = TaxRate::query()->active()->default()->get();

        expect($result->pluck('id'))->toContain($activeDefault->id)
            ->and($result->pluck('id'))->not->toContain($inactiveDefault->id)
            ->and($result->pluck('id'))->not->toContain($activeNonDefault->id);
    });

    test('tax rate with zero percentage', function (): void {
        $zeroTaxRate = TaxRate::factory()->create(['percentage' => 0.0]);

        expect($zeroTaxRate->percentage)->toBe(0.0);
    });

    test('tax rate with high percentage', function (): void {
        $highTaxRate = TaxRate::factory()->create(['percentage' => 28.0]);

        expect($highTaxRate->percentage)->toBe(28.0);
    });
});
