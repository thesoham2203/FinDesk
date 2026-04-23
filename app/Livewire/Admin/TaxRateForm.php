<?php

declare(strict_types=1);

/**
 * TaxRateForm Component
 *
 * WHAT: Livewire component for creating or editing a tax rate.
 *       Used for both create and edit modes.
 *
 * WHY: Tax rates are configured by admins. This form allows:
 *      - Name (e.g., "GST 18%", "VAT 20%")
 *      - Percentage (0-100, can be decimal like 18.5%)
 *      - Is Default (flag to mark as default for invoice line items)
 *      - Is Active (flag; inactive rates don't appear in dropdowns but exist in history)
 *
 *      BUSINESS RULE: Only one tax rate can be marked as default.
 *      When saving with is_default=true, unset any other default rate first.
 *
 * IMPLEMENT:
 *      1. Properties: $taxRateId (?int), $name, $percentage, $isDefault, $isActive
 *      2. Add #[Validate] attributes on properties
 *      3. mount(?TaxRate $taxRate) method:
 *         - If editing, populate from model
 *      4. save() method:
 *         - Validate
 *         - If is_default is true, unset previous default:
 *           TaxRate::where('is_default', true)->update(['is_default' => false])
 *         - Create or update the rate
 *         - Redirect to index with success
 *
 * KEY CONCEPTS:
 * - Only one default: enforce at component level (auto-unset previous)
 * - Computed class name: usable from component or view
 * - Percentage precision: store as float for decimal percentages
 */

namespace App\Livewire\Admin;

use App\Models\TaxRate;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class TaxRateForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|numeric|min:0|max:100')]
    public string $percentage = '';

    #[Validate('required|boolean')]
    public bool $isDefault = false;

    #[Validate('required|boolean')]
    public bool $isActive = true;

    public ?int $taxRateId = null;

    public function mount(?TaxRate $taxRate = null): void
    {
        // TODO: Implement
        if ($taxRate instanceof TaxRate) {
            $this->taxRateId = $taxRate->id;
            $this->name = $taxRate->name;
            $this->percentage = (string) $taxRate->percentage;
            $this->isDefault = $taxRate->is_default;
            $this->isActive = $taxRate->is_active;
        }
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isDefault) {
            TaxRate::query()->where('is_default', true)->update(['is_default' => false]);
        }

        if ($this->taxRateId) {
            $taxRate = TaxRate::query()->findOrFail($this->taxRateId);
            $taxRate->update([
                'name' => $this->name,
                'percentage' => $this->percentage,
                'is_default' => $this->isDefault,
                'is_active' => $this->isActive,
            ]);
        } else {
            TaxRate::query()->create([
                'name' => $this->name,
                'percentage' => $this->percentage,
                'is_default' => $this->isDefault,
                'is_active' => $this->isActive,
            ]);
        }

        session()->flash('success', 'Tax rate saved');
        $this->redirect(route('admin.tax-rates.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.tax-rate-form');
    }
}
