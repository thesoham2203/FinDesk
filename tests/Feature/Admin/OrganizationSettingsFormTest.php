<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\UserRole;
use App\Livewire\Admin\OrganizationSettingsForm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the organization settings form component', function (): void {
    Livewire::test(OrganizationSettingsForm::class)
        ->assertStatus(200);
});

it('can update organization settings', function (): void {
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

it('loads existing organization settings on mount', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Existing Org',
        'address' => '1 Main Street',
        'logo_path' => 'org-logos/existing.png',
        'default_currency' => Currency::USD->value,
        'fiscal_year_start' => 7,
    ]);

    Livewire::test(OrganizationSettingsForm::class)
        ->assertSet('name', $organization->name)
        ->assertSet('address', $organization->address)
        ->assertSet('logoPath', $organization->logo_path)
        ->assertSet('defaultCurrency', $organization->default_currency->value)
        ->assertSet('fiscalYearStart', $organization->fiscal_year_start);
});

it('stores an uploaded logo when saving settings', function (): void {
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png');

    Livewire::test(OrganizationSettingsForm::class)
        ->set('name', 'FinDesk Org')
        ->set('address', '123 Tech Lane')
        ->set('defaultCurrency', Currency::INR->value)
        ->set('fiscalYearStart', 4)
        ->set('logo', $logo)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('flash', type: 'success');

    $organization = Organization::query()->first();

    expect($organization->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($organization->logo_path);
});

it('validates required fields', function (): void {
    Livewire::test(OrganizationSettingsForm::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
