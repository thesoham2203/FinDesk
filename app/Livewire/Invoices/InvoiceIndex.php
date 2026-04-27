<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class InvoiceIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $clientFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    /**
     * @var array<int, array{
     * description: string,
     *     quantity: string,
     *     unit_price: string,
     *     tax_rate_id: string,
     *     line_total: mixed,
     *     tax_amount: mixed
     * }>
     *
     * @return Paginator<Invoice>
     * @return LengthAwarePaginator<Invoice>
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['client', 'creator'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where('invoice_number', 'like', sprintf('%%%s%%', $this->search)))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->when($this->clientFilter !== '', fn (Builder $query): Builder => $query->where('client_id', $this->clientFilter))
            ->when($this->dateFrom !== '', fn (Builder $query): Builder => $query->whereDate('issue_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query): Builder => $query->whereDate('issue_date', '<=', $this->dateTo))
            ->latest();

        return $query->paginate(15);
    }

    /**
     * TODO: Return all clients for filter dropdown.
     *
     * @return EloquentCollection<Client>
     */
    #[Computed]
    public function clients(): EloquentCollection
    {
        return Client::all();
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-index');
    }
}
