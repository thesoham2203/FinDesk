<?php

declare(strict_types=1);

/**
 * TaxRateIndex Component
 *
 * WHAT: Livewire component that lists all tax rates. Shows which one is default,
 *       which are active/inactive, and a count of invoice line items using each rate.
 *
 * WHY: Tax rates are configured by admins. This CRUD interface manages them with
 *      special behaviors:
 *      1. Only one tax rate can be default (for invoice pre-selection)
 *      2. Tax rates can be deactivated (not deleted) to preserve invoice history
 *      3. Cannot delete a rate in use on any invoice
 *
 * IMPLEMENT:
 *      1. Set up pagination trait
 *      2. Create #[Computed] taxRates() method:
 *         - Query TaxRate::withCount('lineItems')
 *         - Order by is_default desc, then name asc
 *         - Paginate with 15 per page
 *      3. Create toggleActive(int $id) method:
 *         - Find tax rate, toggle is_active, save
 *         - Optional: if deactivating and it's default, flash warning
 *      4. Create delete(int $id) method:
 *         - Check if rate has line items (use TaxRateNotInUse rule or direct check)
 *         - If in use: flash error with count
 *         - If not in use: delete and flash success
 *
 * KEY CONCEPTS:
 * - Toggle actions: wire:click to update boolean flags
 * - Status badges: visual indicators for default/active/inactive
 * - Referential integrity: check relationships before deleting
 */

namespace App\Livewire\Admin;

use App\Models\TaxRate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class TaxRateIndex extends Component
{
    use WithPagination;

    #[Computed]
    public function taxRates(): LengthAwarePaginator
    {
        return TaxRate::query()->withCount('lineItems')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->paginate(15);
    }

    public function toggleActive(int $id): void
    {
        $taxRate = TaxRate::findOrFail($id);
        $taxRate->is_active = ! $taxRate->is_active;
        $taxRate->save();

        if (! $taxRate->is_active && $taxRate->is_default) {
            session()->flash('warning', 'Default tax rate is now inactive');
        } else {
            session()->flash('success', 'Tax rate status updated');
        }
    }

    public function delete(int $id): void
    {
        $taxRate = TaxRate::findOrFail($id);

        if ($taxRate->lineItems()->count() > 0) {
            session()->flash('error', "Cannot delete: used on {$taxRate->lineItems()->count()} invoices");
        } else {
            $taxRate->delete();
            session()->flash('success', 'Tax rate deleted');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.tax-rate-index');
    }
}
