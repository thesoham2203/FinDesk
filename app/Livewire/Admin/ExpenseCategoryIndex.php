<?php

declare(strict_types=1);

/**
 * ExpenseCategoryIndex Component
 *
 * WHAT: Livewire component that lists all expense categories with their rules
 *       (max amount, requires receipt) and a count of associated expenses.
 *       Admin can create, edit, and delete categories.
 *
 * WHY: Categories are the foundation of expense organization. This CRUD interface
 *      lets admins manage them. Deletion is protected — categories with existing
 *      expenses cannot be deleted.
 *
 * IMPLEMENT:
 *      1. Set up pagination with WithPagination trait
 *      2. Add #[Url] public $search property for persistent search in URL
 *      3. Create #[Computed] categories() method:
 *         - Query ExpenseCategory::withCount('expenses')
 *         - Filter by search if provided
 *         - Paginate with 15 per page
 *      4. Create delete() method:
 *         - Query category by ID
 *         - Check if it has expenses (use CategoryNotInUse rule or direct check)
 *         - If has expenses: session()->flash('error', '...')
 *         - If no expenses: delete and session()->flash('success', 'Category deleted')
 *      5. Redirect to form for create/edit
 *
 * KEY CONCEPTS:
 * - WithPagination: https://livewire.laravel.com/docs/pagination
 * - Computed Properties: #[Computed] for efficient re-computation
 * - Flash Messages: session()->flash('key', 'message')
 * - Deletion Protection: Check relationships before deleting
 */

namespace App\Livewire\Admin;

use App\Models\ExpenseCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

final class ExpenseCategoryIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        $query = ExpenseCategory::query()->withCount('expenses')->orderBy('name', 'asc');

        if ($this->search !== '' && $this->search !== '0') {
            $query->where('name', 'like', sprintf('%%%s%%', $this->search))
                ->orWhere('description', 'like', sprintf('%%%s%%', $this->search));
        }

        return $query->paginate(15);
    }

    #[Validate]
    public function delete(int $id): void
    {
        $category = ExpenseCategory::query()->findOrFail($id);

        if ($category->expenses()->count() > 0) {
            session()->flash('error', sprintf('Cannot delete category with %s expenses', $category->expenses()->count()));
        } else {
            $category->delete();
            session()->flash('success', 'Category deleted');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.expense-category-index');
    }
}
