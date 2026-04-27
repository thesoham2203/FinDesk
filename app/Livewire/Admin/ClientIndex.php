<?php

declare(strict_types=1);

/**
 * ClientIndex Livewire Component
 *
 * WHAT: Lists all clients with search, create, edit, and delete functionality.
 *       Clients are the recipients of invoices.
 *
 * WHY: Clients are a prerequisite for creating invoices. This follows the same
 *      CRUD pattern as Departments (Day 2). Accessible to Admin, Manager, and Accountant.
 *
 * IMPLEMENT: Add wire:model.live search, paginated client list, delete confirmation,
 *            and edit/create navigation links.
 *
 * REFERENCE:
 * - Livewire Pagination: https://livewire.laravel.com/docs/pagination
 * - Livewire Properties: https://livewire.laravel.com/docs/properties
 */

namespace App\Livewire\Admin;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as Bob;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ClientIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /**
     * Filter by: name or email containing search term.
     * Show: Name, Email, Phone, Tax Number, Invoice Count, Actions.
     *
     * @return LengthAwarePaginator<Client>
     */
    #[Computed]
    public function clients(): LengthAwarePaginator
    {
        return Client::query()
            ->when($this->search, fn (Bob $query) => $query->where('name', 'like', sprintf('%%%s%%', $this->search))
                ->orWhere('email', 'like', sprintf('%%%s%%', $this->search))
            )
            ->withCount('invoices')
            ->paginate(15);
    }

    public function delete(int $id): void
    {
        $client = Client::query()->findOrFail($id);

        if ($client->invoices()->exists()) {
            $this->dispatch('flash', type: 'error', message: 'Cannot delete client with existing invoices.');

            return;
        }

        $client->delete();
        $this->dispatch('flash', type: 'success', message: 'Client deleted successfully.');
    }

    public function render(): View
    {
        return view('livewire.admin.client-index');
    }
}
