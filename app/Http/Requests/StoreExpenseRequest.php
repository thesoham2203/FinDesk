<?php

declare(strict_types=1);

/**
 * StoreExpenseRequest
 *
 * WHAT: Validates data when SUBMITTING a new expense (Day 4).
 *
 * WHY: This is scaffolded NOW but will be USED on Day 4 in the ExpenseForm Livewire
 *      component. It's the most complex validation in the system because:
 *      1. Category rules (max_amount, requires_receipt) are conditional
 *      2. Department budget check is business logic
 *      3. Receipt upload validation is file-specific
 *
 * IMPLEMENT:
 *      1. Basic data validation (title, description, amount, category_id, currency, receipt)
 *      2. Add withValidator() hook OR use after() to check:
 *         - If category has max_amount, check expense amount <= max_amount
 *         - If category has requires_receipt=true, enforce receipt is required
 *      3. ExpenseWithinBudget custom rule could be added here or in component
 *
 * KEY CONCEPTS:
 * - Conditional validation: https://laravel.com/docs/13.x/validation#conditionally-adding-rules
 * - Form Request withValidator(): Custom validation logic after type checking
 * - File uploads: https://laravel.com/docs/13.x/validation#validated-data
 * - Money in cents: User enters dollars, convert before/after validation
 */

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

final class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::authorize('create-expenses');

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|integer|min:1',
            'category_id' => 'required|exists:expense_categories,id',
            'currency' => 'required|string|in:USD,EUR,GBP,INR',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Expense title is required.',
            'amount.required' => 'Amount is required.',
            'amount.integer' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than 0.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'currency.required' => 'Currency is required.',
            'receipt.mimes' => 'Receipt must be a JPG, PNG, or PDF file.',
            'receipt.max' => 'Receipt file cannot exceed 5MB.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $category = ExpenseCategory::find($this->category_id);
            if ($category->max_amount && $this->amount > $category->max_amount) {
                $validator->errors()->add('amount', 'Exceeds category maximum...');
            }
            if ($category->requires_receipt && ! $this->hasFile('receipt')) {
                $validator->errors()->add('receipt', 'Receipt is required...');
            }

        });
    }
}
