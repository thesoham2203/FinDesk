<?php

declare(strict_types=1);

/**
 * ExpenseCategoryForm Component
 *
 * WHAT: Livewire component for creating or editing an expense category.
 *       Used for both create (no model) and edit (with model) dual-use mode.
 *
 * WHY: Categories are configured by admins. This form allows setting:
 *      - Name (required, unique)
 *      - Description (optional notes about the category)
 *      - Max Amount (optional cap per expense, in DOLLARS for user input)
 *      - Requires Receipt (boolean flag)
 *
 *      IMPORTANT: maxAmount is entered in DOLLARS by user but stored in CENTS
 *      in the database. This form converts: display ($400) ↔ store (40000).
 *
 * IMPLEMENT:
 *      1. Properties: $categoryId (?int), $name, $description, $maxAmount, $requiresReceipt
 *      2. Add #[Validate] or #[Rule] attributes on properties
 *      3. mount(?ExpenseCategory $category) method:
 *         - If editing, populate properties from $category
 *         - CRITICAL: Convert max_amount from cents to dollars: $model->max_amount / 100
 *      4. save() method:
 *         - Validate properties
 *         - Convert maxAmount: (int)(floatval($this->maxAmount) * 100)
 *         - Create or update category
 *         - Redirect to index with success message
 *
 * KEY CONCEPTS:
 * - Dual-use component: create/edit mode
 * - Money conversion: $amount_cents = int($amount_dollars * 100)
 * - wire:model for two-way binding
 * - Wire loading states: wire:loading on submit button
 */

namespace App\Livewire\Admin;

use App\Models\ExpenseCategory;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class ExpenseCategoryForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('nullable|numeric|min:0')]
    public string $maxAmount = '';

    #[Validate('required|boolean')]
    public bool $requiresReceipt = false;

    public ?int $categoryId = null;

    /**
     * Mount component, optionally with existing category for editing.
     */
    public function mount(?ExpenseCategory $category = null): void
    {
        if ($category) {
            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->description = $category->description;
            $this->maxAmount = $category->max_amount ? (string) ($category->max_amount / 100) : '';
            $this->requiresReceipt = $category->requires_receipt;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'maxAmount' => 'nullable|numeric|min:0',
            'requiresReceipt' => 'required|boolean',
        ];
    }

    /**
     * Save category (create or update).
     *
     * TODO: Implement save logic:
     *       1. $this->validate() to check all properties
     *       2. Convert maxAmount from dollars to cents:
     *          $maxAmountCents = $this->maxAmount ? (int)(floatval($this->maxAmount) * 100) : null
     *       3. If $this->categoryId is set (editing):
     *          * $category = ExpenseCategory::findOrFail($this->categoryId)
     *          * $category->update([...data...])
     *       4. Otherwise (creating):
     *          * ExpenseCategory::create([...data...])
     *       5. session()->flash('success', 'Category saved')
     *       6. $this->redirect(route('admin.categories.index'), navigate: true)
     */
    public function save(): void
    {
        // TODO: Implement

        $this->validate();

        $maxAmountCents = $this->maxAmount ? (int) ((float) ($this->maxAmount) * 100) : null;

        if ($this->categoryId) {
            $category = ExpenseCategory::findOrFail($this->categoryId);
            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'max_amount' => $maxAmountCents,
                'requires_receipt' => $this->requiresReceipt,
            ]);
        } else {
            ExpenseCategory::create([
                'name' => $this->name,
                'description' => $this->description,
                'max_amount' => $maxAmountCents,
                'requires_receipt' => $this->requiresReceipt,
            ]);
        }

        session()->flash('success', 'Category saved');
        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.expense-category-form');
    }
}
