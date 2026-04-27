<?php

declare(strict_types=1);

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
        if (
            ! in_array($propertyName, [
                'statusFilter',
                'categoryFilter',
                'dateFrom',
                'dateTo',
                'amountMin',
                'amountMax',
            ], true)
        ) {
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
        return Expense::query()
            ->with(['category', 'user', 'department'])
            ->when($this->search !== '', fn (mixed $query): mixed => $query->where('title', 'like', '%'.$this->search.'%')
                ->orWhere('description', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== '', fn (mixed $query): mixed => $query->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== '', fn (mixed $query): mixed => $query->where('category_id', $this->categoryFilter))
            ->when($this->dateFrom !== '', fn (mixed $query): mixed => $query->where('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (mixed $query): mixed => $query->where('date', '<=', $this->dateTo))
            ->when($this->amountMin !== '', fn (mixed $query): mixed => $query->where('amount', '>=', (int) ($this->amountMin * 100)))
            ->when($this->amountMax !== '', fn (mixed $query): mixed => $query->where('amount', '<=', (int) ($this->amountMax * 100)))->latest()
            ->paginate(10);
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
