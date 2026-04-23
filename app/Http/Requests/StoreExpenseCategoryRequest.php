<?php

declare(strict_types=1);

/**
 * StoreExpenseCategoryRequest
 *
 * WHAT: Validates data when CREATING a new expense category.
 *
 * WHY: Form Requests centralize validation logic — instead of writing rules
 *      directly in Livewire components, we extract them here. This makes rules
 *      reusable, testable independently, and consistent across the application.
 *
 * IMPLEMENT: Add authorize() method body to check Gate::allows('manage-categories').
 *            Validation rules are complete for Day 3. Day 4+ may add conditional
 *            rules via withValidator() or after() hook if needed.
 *
 * KEY CONCEPTS:
 * - Form Request Validation: https://laravel.com/docs/13.x/validation#form-request-validation
 * - Authorization: https://laravel.com/docs/13.x/authorization#creating-policies
 * - Money Conversion: User inputs dollars (e.g., "500.00"), convert to cents (50000)
 *   before storing. This is consistent across all money fields in the app.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::authorize('manage-categories');

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
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_amount' => ['nullable', 'integer', 'min:1'],
            'requires_receipt' => ['required', 'boolean'],
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
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'max_amount.min' => 'Maximum amount must be greater than 0.',
            'requires_receipt.required' => 'Please specify if receipts are required.',
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
            'max_amount' => 'maximum amount',
            'requires_receipt' => 'requires receipt',
        ];
    }
}
