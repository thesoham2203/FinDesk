<?php

declare(strict_types=1);

use App\Models\TaxRate;
use App\Rules\UniqueDefaultTaxRate;

test('setting is_default to false passes validation', function (): void {
    $rule = new UniqueDefaultTaxRate();
    $fails = [];

    $rule->validate('is_default', false, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('setting is_default to true passes when no other default exists', function (): void {
    $rule = new UniqueDefaultTaxRate();
    $fails = [];

    $rule->validate('is_default', true, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('setting is_default to true fails when another default exists', function (): void {
    TaxRate::factory()->create(['is_default' => true]);

    $rule = new UniqueDefaultTaxRate();
    $fails = [];

    $rule->validate('is_default', true, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
    expect($fails[0])->toContain('already set as default');
});

test('updating existing default tax rate passes when excluding itself', function (): void {
    $existingDefault = TaxRate::factory()->create(['is_default' => true]);

    $rule = new UniqueDefaultTaxRate(excludeId: $existingDefault->id);
    $fails = [];

    $rule->validate('is_default', true, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->toBeEmpty();
});

test('updating to default fails when another default already exists', function (): void {
    $existingDefault = TaxRate::factory()->create(['is_default' => true]);
    $otherRate = TaxRate::factory()->create(['is_default' => false]);

    $rule = new UniqueDefaultTaxRate(excludeId: $otherRate->id);
    $fails = [];

    $rule->validate('is_default', true, function ($message) use (&$fails): void {
        $fails[] = $message;
    });

    expect($fails)->not->toBeEmpty();
});
