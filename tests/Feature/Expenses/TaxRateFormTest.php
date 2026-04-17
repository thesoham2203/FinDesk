<?php

declare(strict_types=1);

use App\Livewire\Admin\TaxRateForm;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the form component', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component->assertStatus(200);
});

it('creates a new tax rate', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'GST 18%')
        ->set('percentage', '18')
        ->set('isDefault', true)
        ->set('isActive', true)
        ->call('save');

    $this->assertDatabaseHas('tax_rates', [
        'name' => 'GST 18%',
        'percentage' => 18.0,
        'is_default' => true,
        'is_active' => true,
    ]);
});

it('allows decimal percentages', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'VAT 18.5%')
        ->set('percentage', '18.5')
        ->set('isDefault', false)
        ->set('isActive', true)
        ->call('save');

    $taxRate = TaxRate::where('name', 'VAT 18.5%')->first();
    expect($taxRate->percentage)->toBe(18.5);
});

it('edits an existing tax rate', function (): void {
    $taxRate = TaxRate::factory()->create([
        'name' => 'Original',
        'percentage' => 10,
        'is_default' => false,
    ]);

    $component = Livewire::test(TaxRateForm::class, ['taxRate' => $taxRate]);

    expect($component->get('name'))->toBe('Original')
        ->and($component->get('percentage'))->toBe('10');
});

it('updates an existing tax rate', function (): void {
    $taxRate = TaxRate::factory()->create([
        'name' => 'Original',
        'percentage' => 10,
    ]);

    $component = Livewire::test(TaxRateForm::class, ['taxRate' => $taxRate]);

    $component
        ->set('name', 'Updated')
        ->set('percentage', '20')
        ->call('save');

    $taxRate->refresh();
    expect($taxRate->name)->toBe('Updated')
        ->and($taxRate->percentage)->toBe(20.0);
});

it('unsets previous default when marking new default', function (): void {
    $oldDefault = TaxRate::factory()->create(['is_default' => true]);
    $newRate = TaxRate::factory()->create(['is_default' => false]);

    $component = Livewire::test(TaxRateForm::class, ['taxRate' => $newRate]);

    $component
        ->set('isDefault', true)
        ->call('save');

    $oldDefault->refresh();
    expect($oldDefault->is_default)->toBeFalse();
});

it('validates required name field', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', '')
        ->set('percentage', '10')
        ->call('save')
        ->assertHasErrors('name');
});

it('validates required percentage field', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'GST')
        ->set('percentage', '')
        ->call('save')
        ->assertHasErrors('percentage');
});

it('validates percentage as numeric', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'Invalid')
        ->set('percentage', 'abc')
        ->call('save')
        ->assertHasErrors('percentage');
});

it('validates percentage minimum value', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'Negative')
        ->set('percentage', '-5')
        ->call('save')
        ->assertHasErrors('percentage');
});

it('validates percentage maximum value', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'Too High')
        ->set('percentage', '150')
        ->call('save')
        ->assertHasErrors('percentage');
});

it('allows zero percentage', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'Zero Rate')
        ->set('percentage', '0')
        ->set('isActive', true)
        ->call('save');

    $taxRate = TaxRate::where('name', 'Zero Rate')->first();
    expect($taxRate->percentage)->toBe(0.0);
});

it('validates name length', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', str_repeat('a', 256))
        ->set('percentage', '10')
        ->call('save')
        ->assertHasErrors('name');
});

it('can deactivate a tax rate', function (): void {
    $taxRate = TaxRate::factory()->create(['is_active' => true]);

    $component = Livewire::test(TaxRateForm::class, ['taxRate' => $taxRate]);

    $component
        ->set('isActive', false)
        ->call('save');

    $taxRate->refresh();
    expect($taxRate->is_active)->toBeFalse();
});

it('allows non-default tax rates', function (): void {
    $component = Livewire::test(TaxRateForm::class);

    $component
        ->set('name', 'Non-default')
        ->set('percentage', '10')
        ->set('isDefault', false)
        ->call('save');

    $taxRate = TaxRate::where('name', 'Non-default')->first();
    expect($taxRate->is_default)->toBeFalse();
});
