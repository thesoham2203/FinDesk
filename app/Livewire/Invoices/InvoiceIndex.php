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
     * @return LengthAwarePaginator<int, Invoice>
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

        /** @var LengthAwarePaginator<int, Invoice> $paginator */
        $paginator = $query->paginate(15);

        return $paginator;
    }

    /**
     * @return EloquentCollection<int, Client>
     */
    #[Computed]
    public function clients(): EloquentCollection
    {
        return Client::all();
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-index', [
            'invoices' => $this->invoices(),
            'clients' => $this->clients(),
        ]);
    }
}
