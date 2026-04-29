<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\TaxRateForm;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the tax rate form component', function () {
    Livewire::test(TaxRateForm::class)
        ->assertStatus(200);
});

it('can create a tax rate', function () {
    Livewire::test(TaxRateForm::class)
        ->set('name', 'GST 18%')
        ->set('percentage', '18')
        ->set('isDefault', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.tax-rates.index'));

    $this->assertDatabaseHas('tax_rates', [
        'name' => 'GST 18%',
        'percentage' => 18,
        'is_default' => true,
    ]);
});

it('can update a tax rate', function () {
    $taxRate = TaxRate::factory()->create();

    Livewire::test(TaxRateForm::class, ['taxRate' => $taxRate])
        ->set('name', 'Updated Tax Rate')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.tax-rates.index'));

    $taxRate->refresh();
    expect($taxRate->name)->toBe('Updated Tax Rate');
});

it('ensures only one default tax rate exists', function () {
    $existingDefault = TaxRate::factory()->create(['is_default' => true]);

    Livewire::test(TaxRateForm::class)
        ->set('name', 'New Default')
        ->set('percentage', '10')
        ->set('isDefault', true)
        ->call('save');

    $existingDefault->refresh();
    expect($existingDefault->is_default)->toBeFalse();

    $newDefault = TaxRate::where('name', 'New Default')->first();
    expect($newDefault->is_default)->toBeTrue();
});

it('validates required fields', function () {
    Livewire::test(TaxRateForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
