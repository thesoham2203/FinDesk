<?php

declare(strict_types=1);

use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use App\Rules\TaxRateNotInUse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TaxRateNotInUse Rule', function (): void {
    it('passes when tax rate has no line items', function (): void {
        $taxRate = TaxRate::factory()->create();
        $rule = new TaxRateNotInUse();
        
        $failed = false;
        $rule->validate('tax_rate_id', $taxRate->id, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('fails when tax rate has line items', function (): void {
        $taxRate = TaxRate::factory()->create();
        InvoiceLineItem::factory()->count(2)->create(['tax_rate_id' => $taxRate->id]);

        $rule = new TaxRateNotInUse();
        $failed = false;
        
        $rule->validate('tax_rate_id', $taxRate->id, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('fails when tax rate does not exist', function (): void {
        $rule = new TaxRateNotInUse();
        $failed = false;
        
        $rule->validate('tax_rate_id', 99999, function () use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('implements validation rule interface', function (): void {
        $rule = new TaxRateNotInUse();

        expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
    });
});
