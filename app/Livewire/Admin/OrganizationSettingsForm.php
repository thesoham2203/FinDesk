<?php

declare(strict_types=1);

/**
 * OrganizationSettingsForm Component
 *
 * WHAT: Livewire component for editing organization-wide settings.
 *       Only one Organization record should exist (singleton pattern).
 *
 * WHY: Admins need to configure: name, address, logo, default currency, fiscal year start.
 *      This form provides the UI for these settings.
 */

namespace App\Livewire\Admin;

use App\Enums\Currency;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

final class OrganizationSettingsForm extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    #[Validate('nullable|image|max:2048')]
    public mixed $logo = null;

    public ?string $logoPath = null;

    #[Validate('required|string')]
    public string $defaultCurrency = '';

    #[Validate('required|integer|min:1|max:12')]
    public int $fiscalYearStart = 1;

    public function mount(): void
    {
        $this->authorize('update', Organization::class);

        $org = Organization::query()->first();
        if ($org) {
            $this->name = $org->name;
            $this->address = $org->address;
            $this->logoPath = $org->logo_path;
            $this->defaultCurrency = $org->default_currency->value;
            $this->fiscalYearStart = $org->fiscal_year_start;
        }
    }

    /**
     * Save organization settings.
     */
    public function save(): void
    {
        $this->authorize('update', Organization::class);

        $validated = $this->validate();

        // Handle logo upload
        $logoPath = $this->logoPath;
        if ($this->logo) {
            $logoPath = $this->logo->store('org-logos', 'public');
        }

        $org = Organization::query()->first();
        if (! $org) {
            $org = new Organization();
        }

        $org->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'logo_path' => $logoPath,
            'default_currency' => $validated['defaultCurrency'],
            'fiscal_year_start' => $validated['fiscalYearStart'],
        ]);

        // Clear the organization cache
        cache()->forget('organization');

        session()->flash('success', 'Organization settings updated successfully');
    }

    /**
     * Get available currencies.
     */
    public function getCurrenciesProperty(): Collection
    {
        return collect(Currency::cases())
            ->mapWithKeys(fn (Currency $currency): array => [$currency->value => $currency->label()]);
    }

    /**
     * Get available months for fiscal year start.
     */
    public function getMonthsProperty(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.organization-settings-form', [
            'currencies' => $this->currencies,
            'months' => $this->months,
        ]);
    }
}
