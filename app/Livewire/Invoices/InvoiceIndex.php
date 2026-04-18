<?php

declare(strict_types=1);

/**
 * InvoisceIndex Livewire Component
 *
 * WHAT: Lists all invoices with filters (status, client, date range).
 *       Similar to ExpenseIndex but for invoices.
 *
 * WHY: Users need to view, filter, and manage invoices. Accessible to Admin,
 *      Manager, and Accountant. Filters help find invoices quickly.
 *
 * IMPLEMENT: Add search, status filter, client filter, date range filters.
 *            Show invoice number, client, status badge, dates, total, and actions.
 *
 * REFERENCE:
 * - Livewire URL Properties: https://livewire.laravel.com/docs/properties#url
 * - Livewire Pagination: https://livewire.laravel.com/docs/pagination
 */

namespace App\Livewire\Invoices;

use App\Models\Client;
use App\Models\Invoice;
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
     * TODO: Query all invoices with client and creator relationships.
     * Apply all filters (search, status, client, date range).
     * Show: invoice_number, client name, status badge, issue_date, due_date, total (formatted), actions.
     * Paginate by 15 per page.
     *
     * @return Paginator<Invoice>
     */
    #[Computed]
    public function invoices(): Paginator
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
