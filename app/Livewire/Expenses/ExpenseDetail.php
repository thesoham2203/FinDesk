<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expense\ApproveExpense;
use App\Actions\Expense\ReimburseExpense;
use App\Actions\Expense\RejectExpense;
use App\Actions\Expense\SubmitExpense;
use App\Enums\ExpenseStatus;
use App\Models\Activity;
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

    public string $partialReimbursementAmount = '';

    public bool $showPartialReimbursementModal = false;

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

        throw_if($this->expense->status !== ExpenseStatus::Draft, InvalidArgumentException::class, 'Only draft expenses can be submitted.');

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

        resolve(SubmitExpense::class)->execute($this->expense);
        session()->flash('success', 'Expense submitted successfully.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);

    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->expense);

        throw_if($this->expense->status !== ExpenseStatus::Draft, InvalidArgumentException::class, 'Only draft expenses can be deleted.');

        $this->expense->delete();

        $this->redirectRoute('expenses.index');
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->expense);
        $this->expense = resolve(ApproveExpense::class)->execute($this->expense, Auth::user());
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
        $this->expense = resolve(RejectExpense::class)->execute(
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

        $this->expense = resolve(ReimburseExpense::class)->execute($this->expense);

        session()->flash('success', 'Expense marked as reimbursed.');
        $this->expense = $this->expense->fresh(['category', 'user', 'department', 'reviewer', 'activities']);
    }

    public function openPartialReimbursementModal(): void
    {
        $this->authorize('partiallyPaid', $this->expense);

        if ($this->expense->status !== ExpenseStatus::Approved) {
            $this->addError('status', 'Only approved expenses can be marked as partially paid.');

            return;
        }

        $this->showPartialReimbursementModal = true;
    }

    public function recordPartialReimbursement(): void
    {
        $this->authorize('partiallyPaid', $this->expense);

        $this->validate([
            'partialReimbursementAmount' => 'required|numeric|min:0.01|max:' . ($this->expense->amount / 100),
        ], [
            'partialReimbursementAmount.max' => 'Reimbursement amount cannot exceed the expense amount.',
        ]);

        // Convert from currency display (e.g., 100.50) to paise/cents (e.g., 10050)
        $amountInPaise = (int) round((float) ($this->partialReimbursementAmount) * 100);

        // Calculate new amounts
        $newDueAmount = $this->expense->amount - $amountInPaise;

        // Update expense with reimbursed and due amounts
        $this->expense->update([
            'reimbursed_amount' => $amountInPaise,
            'due_amount' => $newDueAmount,
        ]);

        // Transition to PartiallyPaid status
        $this->expense->transitionTo(ExpenseStatus::PartiallyPaid);
        $this->expense->save();

        // Log the activity
        Activity::query()->create([
            'user_id' => auth()->id(),
            'subject_type' => Expense::class,
            'subject_id' => $this->expense->id,
            'description' => 'Partial reimbursement recorded: ' . number_format($amountInPaise / 100, 2),
        ]);

        // Reset form and close modal
        session()->flash('success', 'Partial reimbursement recorded successfully.');
        $this->partialReimbursementAmount = '';
        $this->showPartialReimbursementModal = false;

        // Refresh expense data
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
