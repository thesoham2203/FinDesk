<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\TaxRateIndex;
use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the tax rate index component', function (): void {
    Livewire::test(TaxRateIndex::class)
        ->assertStatus(200);
});

it('lists tax rates', function (): void {
    TaxRate::factory()->create(['name' => 'GST 18%']);
    TaxRate::factory()->create(['name' => 'VAT 20%']);

    Livewire::test(TaxRateIndex::class)
        ->assertSee('GST 18%')
        ->assertSee('VAT 20%');
});

it('toggles tax rate activity', function (): void {
    $taxRate = TaxRate::factory()->create(['is_active' => true]);

    Livewire::test(TaxRateIndex::class)
        ->call('toggleActive', $taxRate->id)
        ->assertDispatched('flash', type: 'success');

    $taxRate->refresh();
    expect($taxRate->is_active)->toBeFalse();
});

it('deletes a tax rate not in use', function (): void {
    $taxRate = TaxRate::factory()->create();

    Livewire::test(TaxRateIndex::class)
        ->call('delete', $taxRate->id)
        ->assertDispatched('flash', type: 'success', message: 'Tax rate deleted');

    expect(TaxRate::query()->find($taxRate->id))->toBeNull();
});

it('cannot delete tax rate in use', function (): void {
    $taxRate = TaxRate::factory()->create();
    InvoiceLineItem::factory()->create(['tax_rate_id' => $taxRate->id]);

    Livewire::test(TaxRateIndex::class)
        ->call('delete', $taxRate->id)
        ->assertDispatched('flash', type: 'error');

    expect(TaxRate::query()->find($taxRate->id))->not->toBeNull();
});
