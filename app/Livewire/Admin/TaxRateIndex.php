<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\TaxRate;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class TaxRateIndex extends Component
{
    use WithPagination;

    /**
     * @return LengthAwarePaginator<TaxRate>
     */
    #[Computed]
    public function taxRates(): LengthAwarePaginator
    {
        return TaxRate::query()->withCount('lineItems')->orderBy('is_default', 'desc')->orderBy('name', 'asc')->paginate(15);
    }

    public function toggleActive(int $id): void
    {
        $taxRate = TaxRate::query()->findOrFail($id);
        $taxRate->is_active = ! $taxRate->is_active;
        $taxRate->save();

        if (! $taxRate->is_active && $taxRate->is_default) {
            $this->dispatch('flash', type: 'warning', message: 'Default tax rate is now inactive');
        } else {
            $this->dispatch('flash', type: 'success', message: 'Tax rate status updated');
        }
    }

    public function delete(int $id): void
    {
        $taxRate = TaxRate::query()->findOrFail($id);

        if ($taxRate->lineItems()->count() > 0) {
            $this->dispatch('flash', type: 'error', message: sprintf('Cannot delete: used on %s invoices', $taxRate->lineItems()->count()));
        } else {
            $taxRate->delete();
            $this->dispatch('flash', type: 'success', message: 'Tax rate deleted');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.tax-rate-index');
    }
}
