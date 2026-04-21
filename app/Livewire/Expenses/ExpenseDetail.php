<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expense\ApproveExpense;
use App\Actions\Expense\ReimburseExpense;
use App\Actions\Expense\RejectExpense;
use App\Actions\Expense\SubmitExpense;
use App\Enums\ExpenseStatus;
use App\Models\Attachment;
use App\Models\Expense;
use App\Rules\ExpenseWithinBudget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExpenseDetail extends Component
{
    #[Locked]
    public int $expenseId = 0;

    public ?Expense $expense = null;

    public string $rejectionReason = '';

    public bool $showRejectModal = false;

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
        Gate::authorize('delete', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be deleted.');
        }

        $this->expense->delete();

        $this->redirectRoute('expenses.index');
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->expense);
        $this->expense = app(ApproveExpense::class)->execute($this->expense, Auth::user());
        session()->flash('success', 'Expense approved successfully.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);
    }

    public function openRejectModal(): void
    {
        $this->authorize('reject', $this->expense);
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->expense);
        $this->validate(
            ['rejectionReason' => 'required|string|min:10'],
            ['rejectionReason.required' => 'Please provide a valid reason'],
            ['rejectionReason.min' => 'Minimum 10 characters please']
        );
        $this->expense = app(RejectExpense::class)->execute(
            $this->expense,
            Auth::user(),
            $this->rejectionReason
        );

        $this->rejectionReason = '';
        $this->showRejectModal = false;
        session()->flash('success', 'Expense rejected successfully');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);

    }

    public function reimburse(): void
    {
        $this->authorize('reimburse', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Approved && $this->expense->status !== ExpenseStatus::PartiallyPaid) {
            $this->addError('status', 'Only approved or partially paid expenses can be reimbursed.');

            return;
        }

        $this->expense = app(ReimburseExpense::class)->execute($this->expense, Auth::user());

        session()->flash('success', 'Expense marked as reimbursed.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);
    }

    public function markPartiallyPaid(): void
    {
        $this->authorize('partiallyPaid', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Approved) {
            $this->addError('status', 'Only approved expenses can be marked as partially paid.');

            return;
        }

        $this->expense->transitionTo(ExpenseStatus::PartiallyPaid);
        $this->expense->save();

        session()->flash('success', 'Expense marked as partially paid.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);
    }

    public function downloadAttachment(Attachment $attachment): StreamedResponse
    {
        // Authorize the download (same check as the controller)
        Gate::authorize('view', $attachment);

        // Return the file download
        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-detail');
    }
}
