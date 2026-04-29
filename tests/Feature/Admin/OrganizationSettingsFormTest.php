<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\UserRole;
use App\Livewire\Admin\OrganizationSettingsForm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the organization settings form component', function () {
    Livewire::test(OrganizationSettingsForm::class)
        ->assertStatus(200);
});

it('can update organization settings', function () {
    Livewire::test(OrganizationSettingsForm::class)
        ->set('name', 'FinDesk Org')
        ->set('address', '123 Tech Lane')
        ->set('defaultCurrency', Currency::INR->value)
        ->set('fiscalYearStart', 4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $org = Organization::query()->first();
    expect($org->name)->toBe('FinDesk Org');
    expect($org->default_currency)->toBe(Currency::INR);
    expect($org->fiscal_year_start)->toBe(4);
});

it('validates required fields', function () {
    Livewire::test(OrganizationSettingsForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
