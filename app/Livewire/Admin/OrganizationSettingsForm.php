<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\Currency;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
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
            $this->address = $org->address ?? '';
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

        /** @var array{name: string, address: string|null, defaultCurrency: string, fiscal_year_start: int} $validated */
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'defaultCurrency' => 'required|string',
            'fiscalYearStart' => 'required|integer|min:1|max:12',
        ]);

        // Handle logo upload
        $logoPath = $this->logoPath;
        if ($this->logo instanceof UploadedFile) {
            $logoPath = $this->logo->store('org-logos', 'public');
            if ($logoPath === false) {
                $logoPath = $this->logoPath;
            }
        }

        Organization::query()->updateOrCreate(
            [], // Only one record
            [
                'name' => $validated['name'],
                'address' => $validated['address'],
                'logo_path' => $logoPath,
                'default_currency' => $validated['defaultCurrency'],
                'fiscal_year_start' => $validated['fiscalYearStart'],
            ]
        );

        // Clear the organization cache
        cache()->forget('organization');

        $this->dispatch('flash', type: 'success', message: 'Organization settings updated successfully');
    }

    /**
     * Get available currencies.
     *
     * @return Collection<string, string>
     */
    #[Computed]
    public function currencies(): Collection
    {
        return collect(Currency::cases())
            ->mapWithKeys(fn (Currency $currency): array => [$currency->value => $currency->label()]);
    }

    /**
     * Get available months for fiscal year start.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function months(): array
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
            'currencies' => $this->currencies(),
            'months' => $this->months(),
        ]);
    }
}
