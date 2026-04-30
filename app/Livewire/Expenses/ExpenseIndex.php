<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @return LengthAwarePaginator<int, Expense>
     */
    #[Computed]
    public function expenses(): LengthAwarePaginator
    {

        /** @var LengthAwarePaginator<int, Expense> $paginator */
        $paginator = Expense::query()
            ->with(['category', 'user', 'department'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(function (Builder $q): void {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== '', fn (Builder $query): Builder => $query->where('category_id', $this->categoryFilter))
            ->when($this->dateFrom !== '', fn (Builder $query): Builder => $query->where('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query): Builder => $query->where('date', '<=', $this->dateTo))
            ->when($this->amountMin !== '', fn (Builder $query): Builder => $query->where('amount', '>=', (int) (((float) $this->amountMin) * 100)))
            ->when($this->amountMax !== '', fn (Builder $query): Builder => $query->where('amount', '<=', (int) (((float) $this->amountMax) * 100)))->latest()
            ->paginate(10);

        return $paginator;
    }

    /**
     * @return Collection<int, ExpenseCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return ExpenseCategory::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, ExpenseStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return ExpenseStatus::cases();
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-index', [
            'expenses' => $this->expenses(),
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
        ]);
    }
}
