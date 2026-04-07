<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\CreateExpenseAction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class ExpenseForm extends Component
{
    use WithFileUploads;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|numeric|min:0.01')]
    public string $amountInput = '';

    #[Validate('required|exists:expense_categories,id')]
    public int $categoryId = 0;

    #[Validate('nullable|file|mimes:jpg,jpeg,png,pdf|max:5120')]
    public ?TemporaryUploadedFile $receipt = null;

    /**
     * @var Collection<int, ExpenseCategory>
     */
    public Collection $categories;

    public bool $requiresReceipt = false;

    public function mount(): void
    {
        $this->categories = ExpenseCategory::query()->orderBy('name')->get();
    }

    public function updatedCategoryId(): void
    {
        if ($this->categoryId > 0) {
            $category = ExpenseCategory::query()->find($this->categoryId);
            $this->requiresReceipt = $category?->requires_receipt ?? false;
        }
    }

    public function submit(): void
    {
        $this->authorize('create', Expense::class);
        $this->validate();

        if ($this->requiresReceipt && ! $this->receipt instanceof TemporaryUploadedFile) {
            $this->addError('receipt', 'A receipt is required for this category.');

            return;
        }

        $amountInPaise = (int) round((float) $this->amountInput * 100);

        $receiptPath = null;
        if ($this->receipt instanceof TemporaryUploadedFile) {
            $receiptPath = $this->receipt->store('receipts', 'private');
        }

        resolve(CreateExpenseAction::class)->execute(
            user: auth()->user(),
            title: $this->title,
            description: $this->description,
            amount: $amountInPaise,
            currency: 'INR',
            categoryId: $this->categoryId,
            receiptPath: $receiptPath,
        );

        session()->flash('success', 'Expense saved as draft.');
        $this->redirect(route('expenses.create'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-form');
    }
}
