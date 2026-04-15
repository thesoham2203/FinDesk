<?php

declare(strict_types=1);

/**
 * ExpenseDetail Component
 *
 * WHAT: Scaffold for the single expense detail page.
 *
 * WHY: This page will host the receipt preview, status timeline, activity log, and conditional
 *      actions for later workflow steps such as submit, approve, reject, and delete.
 *
 * IMPLEMENT: Load the expense, authorize access, and wire the conditional actions.
 *            The current version only defines the component shape and view contract.
 *
 * KEY CONCEPTS:
 * - #[Locked] properties
 * - Route model binding
 * - Conditional Livewire actions
 * - Detail page composition
 */

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ExpenseDetail extends Component
{
    #[Locked]
    public int $expenseId = 0;

    public ?Expense $expense = null;

    public function mount(Expense $expense): void
    {
        // TODO:
        // 1. Authorize the view action
        // 2. Load the expense with category, user, department, reviewer, and activities
        // 3. Populate the locked identifier and component state
        $this->authorize('view', $expense);
        $this->expenseId = $expense->id;
        $this->expense = $expense->load([
            'category',
            'user',
            'department',
            'reviewer',
            'activities',
        ]);
    }

    public function submit(): void
    {
        // TODO:
        // 1. Ensure the current user owns the expense
        // 2. Verify the expense is still Draft
        // 3. Check budget and submit through the action class
        Gate::authorize('submit', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be submitted.');
        }
        $this->expense->transitionTo(ExpenseStatus::Submitted);
    }

    public function delete(): void
    {
        // TODO:
        // 1. Authorize delete access
        // 2. Ensure the expense is still Draft
        // 3. Remove the record and receipt file
        // 4. Redirect to the list page
        Gate::authorize('delete', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be deleted.');
        }

        $this->expense->delete();

        $this->redirectRoute('expenses.index');
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-detail');
    }
}
