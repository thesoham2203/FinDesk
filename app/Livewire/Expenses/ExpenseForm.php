<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expense\CreateExpense;
use App\Actions\Expense\SubmitExpense;
use App\Actions\Expense\UpdateExpense;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
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

    #[Validate('required|numeric|min:1')]
    public string $amount = '';

    #[Validate('required|exists:expense_categories,id')]
    public string $categoryId = '';

    #[Validate('required|string|in:INR,USD,EUR,GBP')]
    public string $currency = 'INR';

    #[Validate('nullable|file|sometimes|mimes:jpg,jpeg,png,pdf')]
    public ?TemporaryUploadedFile $receipt = null;

    #[Validate('required|date')]
    public string $date = '';

    public ?string $existingReceiptPath = null;

    public ?int $expenseId = null;

    public function mount(?Expense $expense = null): void
    {
        throw_if($expense instanceof Expense && $expense->status !== ExpenseStatus::Draft, InvalidArgumentException::class, 'Only draft expenses can be edited.');

        if ($expense instanceof Expense) {
            $this->expenseId = $expense->id;
            $this->title = $expense->title;
            $this->description = $expense->description ?? '';
            $this->amount = (string) ($expense->amount / 100);
            $this->categoryId = (string) $expense->category_id;
            $this->currency = $expense->currency->value;
            $this->existingReceiptPath = $expense->receipt_path;
            $this->date = $expense->date->format('Y-m-d');
            $this->updatedCategoryId();
        }
    }

    public function save(bool $andSubmit = false): void
    {
        $this->validate();

        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (int) (((float) $this->amount) * 100),
            'category_id' => (int) $this->categoryId,
            'currency' => $this->currency,
            'date' => $this->date,
        ];
        if ($this->expenseId === null) {
            $expense = resolve(CreateExpense::class)->execute($user, $data, $this->receipt);
        } else {
            /** @var Expense $expense */
            $expense = Expense::query()->findOrFail($this->expenseId);
            $expense = resolve(UpdateExpense::class)->execute($expense, $data, $this->receipt);
        }

        if ($andSubmit) {
            resolve(SubmitExpense::class)->execute($expense);
        }

        session()->flash('success', 'Expense saved successfully.');
        $this->redirectRoute('expenses.show', $expense);
    }

    public function updatedCategoryId(): void
    {
        /** @var ExpenseCategory|null $category */
        $category = ExpenseCategory::query()->find((int) $this->categoryId);
        if ($category) {
            $this->maxAmount = $category->max_amount ? ((float) $category->max_amount / 100) : null;
            $this->requiresReceipt = $category->requires_receipt;
        }
    }

    /**
     * @return Collection<int, ExpenseCategory>
     */
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

        /** @var ExpenseCategory|null $category */
        $category = ExpenseCategory::query()->find((int) $this->categoryId);

        return $category;
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-form');
    }
}
