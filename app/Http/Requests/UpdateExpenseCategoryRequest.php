<?php

declare(strict_types=1);

/**
 * UpdateExpenseCategoryRequest
 *
 * WHAT: Validates data when UPDATING an existing expense category.
 *
 * WHY: Almost identical to StoreExpenseCategoryRequest, except the 'name' field's
 *      unique rule must ignore the current category's ID. This allows the user
 *      to keep the same name if they're just editing other fields.
 *
 * IMPLEMENT: Add authorize() method body. Update the name unique rule to
 *            ignore the current category ID using rule() method or
 *            Rule::unique()->ignore() pattern.
 *
 * KEY CONCEPTS:
 * - Rule::unique with ignore: https://laravel.com/docs/13.x/validation#rule-unique
 * - Access route parameters via $this->route('paramName')
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implement authorization check
        // return Gate::allows('manage-categories');
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_categories')->ignore($this->route('expense_category')),
            ],
            'description' => 'nullable|string|max:1000',
            'max_amount' => 'nullable|integer|min:1',
            'requires_receipt' => 'required|boolean',
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
            'name.unique' => 'A different category with this name already exists.',
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
