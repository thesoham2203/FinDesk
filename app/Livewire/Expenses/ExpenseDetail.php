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

use App\Actions\Expense\SubmitExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Rules\ExpenseWithinBudget;
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
        $this->authorize('update', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be submitted.');
        }

        // Check budget constraint
        $budgetExceeded = false;
        $budgetRule = new ExpenseWithinBudget($this->expense->department_id, $this->expense->amount);
        $budgetRule->validate('amount', $this->expense->amount, function (string $message) use (&$budgetExceeded): void {
            $budgetExceeded = true;
            $this->addError('budget', $message);

        });

        if ($budgetExceeded) {
            return;
        }

        app(SubmitExpense::class)->execute($this->expense);
        session()->flash('success', 'Expense submitted successfully.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);

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
