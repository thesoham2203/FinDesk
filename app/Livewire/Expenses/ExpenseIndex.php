<?php

declare(strict_types=1);

/**
 * ExpenseIndex Component
 *
 * WHAT: Scaffold for the authenticated "My Expenses" listing page.
 *
 * WHY: This page is the main entry point for expense tracking. It introduces URL-persisted
 *      filters, pagination, and role-aware scoping that will be implemented in the next pass.
 *
 * IMPLEMENT: Build the scoped query, wire up the filters, and render the table rows.
 *            The shape is here now so the Livewire learning flow has the correct structure.
 *
 * KEY CONCEPTS:
 * - WithPagination
 * - #[Url] filter persistence
 * - Computed properties
 * - Role-aware expense visibility
 */

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ExpenseIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $amountMin = '';

    #[Url]
    public string $amountMax = '';

    public function updatedSearch(): void
    {

        $this->resetPage();
    }

    public function updated(string $propertyName): void
    {
        if (! in_array($propertyName, [
            'statusFilter',
            'categoryFilter',
            'dateFrom',
            'dateTo',
            'amountMin',
            'amountMax',
        ], true)) {
            return;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {

        $this->reset([
            'search',
            'statusFilter',
            'categoryFilter',
            'dateFrom',
            'dateTo',
            'amountMin',
            'amountMax',
        ]);

        $this->resetPage();
    }

    #[Computed]
    public function expenses(): LengthAwarePaginator
    {
        Expense::query()->with(['category', 'user', 'department'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter !== '', function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->when($this->dateFrom !== '', function ($query) {
                $query->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function ($query) {
                $query->whereDate('date', '<=', $this->dateTo);
            })
            ->when($this->amountMin !== '', function ($query) {
                $query->where('amount', '>=', (int) ($this->amountMin * 100));
            })
            ->when($this->amountMax !== '', function ($query) {
                $query->where('amount', '<=', (int) ($this->amountMax * 100));
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return new LengthAwarePaginator([], 0, 10);
    }

    #[Computed]
    public function categories(): Collection
    {
        return ExpenseCategory::query()->orderBy('name')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return ExpenseStatus::cases();
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-index');
    }
}
