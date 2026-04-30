<?php

declare(strict_types=1);

/**
 * PendingApprovals Component
 *
 * WHAT: Manager dashboard showing expenses from their department that are awaiting
 *       approval (status = Submitted). This is the 'pending approvals' queue.
 *
 * WHY: Managers need a clear view of all expenses pending their review.
 *      The component auto-refreshes every 30 seconds via wire:poll.
 *      Quick approve action for immediate response.
 *
 * AUTHORIZATION: Only managers see this page. The query is scoped to the manager's
 *                department. The HLD says: 'Manager sees only their department's expenses.'
 *
 * IMPLEMENT:
 *            - use WithPagination
 *            - #[Computed] pendingExpenses(): Collection
 *              Query Expense::where('status', ExpenseStatus::Submitted)
 *              Scope to manager's department: ->where('department_id', auth()->user()->department_id)
 *              Eager load: user, category
 *              Order by submitted_at asc (oldest first)
 *              Paginate: ->paginate(25)
 *
 *            - #[Computed] pendingCount(): int
 *              Count of pending expenses (for display and potential badge)
 *              Expense::where('status', ExpenseStatus::Submitted)
 *                  ->where('department_id', auth()->user()->department_id)
 *                  ->count()
 *
 *            - approve(int $expenseId): void
 *              Find expense, authorize, use ApproveExpense action, flash success
 *
 *            - render(): View
 *
 * KEY CONCEPTS:
 * - WithPagination: Built-in pagination support
 * - #[Computed]: Dynamic computed properties (replaces old $this->refreshOnly)
 * - wire:poll.30s: Auto-refresh every 30 seconds
 * - Scoped queries: Department filter ensures authorization
 * - Quick actions: Approve inline with one click
 *
 * REFERENCE:
 * - https://laravel.com/docs/13.x/eloquent#querying
 * - https://livewire.laravel.com/docs/pagination
 * - https://livewire.laravel.com/docs/computed-properties
 * - https://livewire.laravel.com/docs/polling
 */

namespace App\Livewire\Expenses;

use App\Actions\Expense\ApproveExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class PendingApprovals extends Component
{
    use WithPagination;

    /**
     * Get paginated pending expenses for the manager's department.
     *
     * @return LengthAwarePaginator<int, Expense>
     */
    #[Computed]
    public function pendingExpenses(): LengthAwarePaginator
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->department_id) {
            /** @var LengthAwarePaginator<int, Expense> $emptyPaginator */
            $emptyPaginator = new LengthAwarePaginator([], 0, 10);

            return $emptyPaginator;
        }

        /** @var LengthAwarePaginator<int, Expense> $paginator */
        $paginator = Expense::query()->where('status', ExpenseStatus::Submitted)
            ->where('department_id', $user->department_id)
            ->with(['user', 'category'])
            ->oldest('submitted_at')
            ->paginate(10);

        return $paginator;
    }

    /**
     * Get the count of pending expenses for the manager's department.
     */
    #[Computed]
    public function pendingCount(): int
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->department_id) {
            return 0;
        }

        return Expense::query()->where('status', ExpenseStatus::Submitted)
            ->where('department_id', $user->department_id)
            ->count();
    }

    /**
     * Approve a specific expense.
     */
    public function approve(int $expenseId): void
    {
        $expense = Expense::query()->findOrFail($expenseId);
        $this->authorize('approve', $expense);

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        resolve(ApproveExpense::class)->execute($expense, $user);
        session()->flash('success', 'Expense approved successfully.');
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.expenses.pending-approvals', [
            'pendingExpenses' => $this->pendingExpenses(),
            'pendingCount' => $this->pendingCount(),
        ]);
    }
}
