<?php

declare(strict_types=1);

/**
 * ExpenseForm Component
 *
 * WHAT: Class-based Livewire form scaffold for creating and editing expenses.
 *
 * WHY: Day 4 introduces the main expense submission workflow. This component shows the
 *      form shape, state properties, and Livewire hooks that will eventually drive the
 *      create/update/submit actions.
 *
 * IMPLEMENT: Wire up validation, mount/edit state, file uploads, category-aware hints,
 *            and action dispatch. This file intentionally keeps the methods as scaffolds.
 *
 * KEY CONCEPTS:
 * - Class-based Livewire components
 * - WithFileUploads
 * - Computed properties
 * - Livewire validation attributes
 * - Money input in dollars before conversion to cents
 */

namespace App\Livewire\Expenses;

use App\Actions\Expense\CreateExpense;
use App\Actions\Expense\SubmitExpense;
use App\Actions\Expense\UpdateExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class ExpenseForm extends Component
{
    use WithFileUploads;

    public ?float $maxAmount = null;

    public ?bool $requiresReceipt = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|numeric|min:0.01')]
    public string $amount = '';

    #[Validate('required|exists:expense_categories,id')]
    public string $categoryId = '';

    #[Validate('required|string|in:INR,USD,EUR,GBP')]
    public string $currency = 'INR';

    #[Validate('nullable|file|mimes:jpg,jpeg,png,pdf|max:5120')]
    public ?TemporaryUploadedFile $receipt = null;

    #[Validate('required|date')]
    public ?DateTimeImmutable $date = null;

    public ?string $existingReceiptPath = null;

    public ?int $expenseId = null;

    public function mount(?Expense $expense = null): void
    {
        if ($expense !== null && $expense->status !== ExpenseStatus::Draft) {
            throw new InvalidArgumentException('Only draft expenses can be edited.');
        }

        if ($expense !== null) {
            $this->expenseId = $expense->id;
            $this->title = $expense->title;
            $this->description = $expense->description ?? '';
            $this->amount = (string) ($expense->amount / 100);
            $this->categoryId = (string) $expense->category_id;
            $this->currency = $expense->currency->value;
            $this->existingReceiptPath = $expense->receipt_path;
            $this->date = $expense->date;
            $this->updatedCategoryId();
        }
    }

    public function save(bool $andSubmit = false): void
    {
        $this->validate();
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (int) ($this->amount * 100),
            'category_id' => (int) $this->categoryId,
            'currency' => $this->currency,
            'date' => $this->date,
        ];
        if ($this->expenseId === null) {
            $expense = app(CreateExpense::class)->execute(Auth::user(), $data, $this->receipt);
        } else {
            $expense = Expense::query()->findOrFail($this->expenseId);
            $expense = app(UpdateExpense::class)->execute($expense, $data, $this->receipt);
        }
        if ($andSubmit) {
            app(SubmitExpense::class)->execute($expense);
        }
        session()->flash('success', 'Expense saved successfully.');
        $this->redirectRoute('expenses.show', $expense);
    }

    public function updatedCategoryId(): void
    {
        $category = ExpenseCategory::query()->find((int) $this->categoryId);
        $this->maxAmount = $category->max_amount;
        $this->requiresReceipt = $category->requires_receipt;
        $this->addError('amount', $category->max_amount);
        $this->addError('receipt', $category->requires_receipt ? 'Receipt is required for this category.' : '');
    }

    #[Computed]
    public function categories(): Collection
    {
        return ExpenseCategory::query()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedCategory(): ?ExpenseCategory
    {
        // TODO: Return the currently selected category for rule hints.
        if ($this->categoryId === '') {
            return null;
        }

        return ExpenseCategory::query()->find((int) $this->categoryId);
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-form');
    }
}
