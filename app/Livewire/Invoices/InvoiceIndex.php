<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        $query = Invoice::with(['client', 'creator'])
            ->when($this->search, fn ($q) => $q->where('invoice_number', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->clientFilter, fn ($q) => $q->where('client_id', $this->clientFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('issue_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('issue_date', '<=', $this->dateTo))
            ->orderByDesc('created_at');

        return $query->paginate(15);
    }

    /**
     * TODO: Return all clients for filter dropdown.
     *
     * @return \Illuminate\Database\Eloquent\Collection<Client>
     */
    #[Computed]
    public function clients()
    {
        return Client::all();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.invoices.invoice-index');
    }
}
