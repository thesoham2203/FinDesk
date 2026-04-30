<?php

declare(strict_types=1);

namespace App\Livewire\Client\Invoices;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.client-app')]
final class InvoiceIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    /**
     * @return LengthAwarePaginator<int, Invoice>
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->where('client_id', auth('client')->id())
            ->with(['client', 'creator'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where('invoice_number', 'like', sprintf('%%%s%%', $this->search)))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->latest();

        return $query->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.client.invoices.invoice-index');
    }
}
