<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

test('organization can be created with factory', function (): void {
    $org = Organization::factory()->create();

    expect($org)->not->toBeNull()
        ->and($org->id)->toBeInt()
        ->and($org->name)->toBeString();
});

test('organization has required attributes', function (): void {
    $org = Organization::factory()->create([
        'name' => 'Test Organization',
        'address' => '123 Business St',
        'logo_path' => '/logos/test.png',
        'default_currency' => Currency::USD,
        'fiscal_year_start' => 1,
    ]);

    expect($org->toArray())
        ->toHaveKeys([
            'id',
            'name',
            'address',
            'logo_path',
            'default_currency',
            'fiscal_year_start',
            'created_at',
            'updated_at',
        ])
        ->and($org->name)->toBe('Test Organization')
        ->and($org->address)->toBe('123 Business St')
        ->and($org->logo_path)->toBe('/logos/test.png')
        ->and($org->fiscal_year_start)->toBe(1);
});

test('organization default currency is cast to currency enum', function (): void {
    $org = Organization::factory()->create([
        'default_currency' => Currency::GBP,
    ]);

    expect($org->default_currency)->toBeInstanceOf(Currency::class)
        ->and($org->default_currency)->toBe(Currency::GBP);
});

test('organization fiscal year start is cast to integer', function (): void {
    $org = Organization::factory()->create([
        'fiscal_year_start' => 4,
    ]);

    expect($org->fiscal_year_start)->toBeInt()
        ->and($org->fiscal_year_start)->toBe(4);
});

test('organization::current returns cached organization on first call', function (): void {
    Cache::forget('organization');
    
    $org = Organization::factory()->create([
        'name' => 'Cached Org',
    ]);

    $cached = Organization::current();

    expect($cached)->not->toBeNull()
        ->and($cached->name)->toBe('Cached Org')
        ->and($cached->id)->toBe($org->id);
});

test('organization::current returns cached value on subsequent calls', function (): void {
    Cache::forget('organization');
    
    $org = Organization::factory()->create([
        'name' => 'Org for Cache Test',
    ]);

    // First call caches it
    $first = Organization::current();
    
    // Update the database directly
    $org->update(['name' => 'Updated Name']);
    
    // Second call should still return cached value
    $second = Organization::current();

    expect($first->name)->toBe('Org for Cache Test')
        ->and($second->name)->toBe('Org for Cache Test');
});

test('organization::current uses cache key organization', function (): void {
    Cache::forget('organization');
    
    Organization::factory()->create();
    
    Organization::current();

    expect(Cache::has('organization'))->toBeTrue();
});

test('organization fillable fields are correct', function (): void {
    $fillable = (new Organization())->getFillable();

    expect($fillable)->toContain(
        'name',
        'address',
        'logo_path',
        'default_currency',
        'fiscal_year_start'
    );
});

test('organization can be created with various currency types', function (): void {
    $usd = Organization::factory()->create(['default_currency' => Currency::USD]);
    $eur = Organization::factory()->create(['default_currency' => Currency::EUR]);
    $gbp = Organization::factory()->create(['default_currency' => Currency::GBP]);

    expect($usd->default_currency)->toBe(Currency::USD)
        ->and($eur->default_currency)->toBe(Currency::EUR)
        ->and($gbp->default_currency)->toBe(Currency::GBP);
});

test('organization can be created with different fiscal year starts', function (): void {
    $org1 = Organization::factory()->create(['fiscal_year_start' => 1]);
    $org2 = Organization::factory()->create(['fiscal_year_start' => 4]);
    $org3 = Organization::factory()->create(['fiscal_year_start' => 7]);

    expect($org1->fiscal_year_start)->toBe(1)
        ->and($org2->fiscal_year_start)->toBe(4)
        ->and($org3->fiscal_year_start)->toBe(7);
});

test('organization can be updated', function (): void {
    $org = Organization::factory()->create();
    
    $org->update([
        'name' => 'New Name',
        'address' => 'New Address',
    ]);

    $org->refresh();

    expect($org->name)->toBe('New Name')
        ->and($org->address)->toBe('New Address');
});

test('organization has casts property defined', function (): void {
    $org = new Organization();
    $casts = $org->getCasts();

    expect($casts)->toHaveKeys(['default_currency', 'fiscal_year_start'])
        ->and($casts['default_currency'])->toBe(Currency::class)
        ->and($casts['fiscal_year_start'])->toBe('integer');
});
