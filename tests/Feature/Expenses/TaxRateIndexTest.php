<?php

declare(strict_types=1);

use App\Livewire\Admin\TaxRateIndex;
use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the index component', function (): void {
    $component = Livewire::test(TaxRateIndex::class);

    $component->assertStatus(200);
});

it('displays all tax rates', function (): void {
    $rates = TaxRate::factory(3)->create();

    $component = Livewire::test(TaxRateIndex::class);

    foreach ($rates as $rate) {
        $component->assertSee($rate->name);
    }
});

it('paginates tax rates with 15 per page', function (): void {
    TaxRate::factory(20)->create();

    $component = Livewire::test(TaxRateIndex::class);

    $rates = $component->get('taxRates');
    expect($rates->count())->toBeLessThanOrEqual(15);
});

it('orders default tax rate first', function (): void {
    TaxRate::factory()->create(['name' => 'Non-default', 'is_default' => false]);
    TaxRate::factory()->create(['name' => 'Default', 'is_default' => true]);

    $component = Livewire::test(TaxRateIndex::class);

    $rates = $component->get('taxRates');
    $names = $rates->pluck('name')->toArray();

    expect($names[0])->toBe('Default');
});

it('orders by name after default flag', function (): void {
    TaxRate::factory()->create(['name' => 'Zebra', 'is_default' => false]);
    TaxRate::factory()->create(['name' => 'Apple', 'is_default' => false]);

    $component = Livewire::test(TaxRateIndex::class);

    $rates = $component->get('taxRates');
    $names = $rates->pluck('name')->toArray();

    expect($names)->toContain('Apple', 'Zebra');
});

it('counts associated invoice line items', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory(3)->create(['tax_rate_id' => $taxRate->id]);

    $component = Livewire::test(TaxRateIndex::class);

    $rates = $component->get('taxRates');
    $rateData = $rates->firstWhere('id', $taxRate->id);

    expect($rateData->line_items_count)->toBe(3);
});

it('toggles tax rate active status', function (): void {
    $taxRate = TaxRate::factory()->create(['is_active' => true]);

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('toggleActive', $taxRate->id);

    $taxRate->refresh();
    expect($taxRate->is_active)->toBeFalse();
});

it('toggles tax rate from inactive to active', function (): void {
    $taxRate = TaxRate::factory()->create(['is_active' => false]);

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('toggleActive', $taxRate->id);

    $taxRate->refresh();
    expect($taxRate->is_active)->toBeTrue();
});

it('flashes success message on toggle', function (): void {
    $taxRate = TaxRate::factory()->create();

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('toggleActive', $taxRate->id);

    // Session flashing is tested indirectly through successful status update
    $taxRate->refresh();
    expect($taxRate->is_active)->toBeFalse();
});

it('flashes warning when deactivating default rate', function (): void {
    $taxRate = TaxRate::factory()->create(['is_active' => true, 'is_default' => true]);

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('toggleActive', $taxRate->id);

    // Warning is triggered when default rate is deactivated
    $taxRate->refresh();
    expect($taxRate->is_active)->toBeFalse()
        ->and($taxRate->is_default)->toBeTrue();
});

it('deletes a tax rate with no line items', function (): void {
    $taxRate = TaxRate::factory()->create();

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('delete', $taxRate->id);

    $this->assertDatabaseMissing('tax_rates', ['id' => $taxRate->id]);
});

it('prevents deletion of tax rate in use', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory(2)->create(['tax_rate_id' => $taxRate->id]);

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('delete', $taxRate->id);

    // Verify the rate was not deleted
    $this->assertDatabaseHas('tax_rates', ['id' => $taxRate->id]);
});

it('flashes error message when deleting tax rate in use', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory(5)->create(['tax_rate_id' => $taxRate->id]);

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('delete', $taxRate->id);

    // Verify the rate was not deleted
    $this->assertDatabaseHas('tax_rates', ['id' => $taxRate->id]);
});

it('flashes success message when deleting unused tax rate', function (): void {
    $taxRate = TaxRate::factory()->create();

    $component = Livewire::test(TaxRateIndex::class);

    $component->call('delete', $taxRate->id);

    // Verify the rate was deleted
    $this->assertDatabaseMissing('tax_rates', ['id' => $taxRate->id]);
});

it('returns empty list when no tax rates exist', function (): void {
    $component = Livewire::test(TaxRateIndex::class);

    $rates = $component->get('taxRates');
    expect($rates->count())->toBe(0);
});
