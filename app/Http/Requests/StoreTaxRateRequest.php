<?php

declare(strict_types=1);

/**
 * StoreTaxRateRequest
 *
 * WHAT: Validates data when CREATING a new tax rate.
 *
 * WHY: Tax rates are critical for invoice calculations. Percentages must be
 *      between 0 and 100. The is_default flag determines which rate is
 *      pre-selected when creating invoice line items.
 *
 * IMPLEMENT: Add authorize() method body. The 'is_default' rule doesn't need
 *            UniqueDefaultTaxRate custom rule in this Form Request — that's
 *            enforced at the Livewire component level (component unsets
 *            previous default before creating new one).
 *
 * KEY CONCEPTS:
 * - Percentage validation: 0-100 range
 * - Default logic: managed in component, not Form Request
 * - Form Requests are for data type/format validation, not business logic
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreTaxRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Gate::authorize('manage-tax-rates');

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
            'name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_default' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
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
            'name.required' => 'Tax rate name is required.',
            'percentage.required' => 'Percentage is required.',
            'percentage.numeric' => 'Percentage must be a valid number.',
            'percentage.min' => 'Percentage must be at least 0.',
            'percentage.max' => 'Percentage cannot exceed 100.',
            'is_default.required' => 'Please specify if this is the default rate.',
            'is_active.required' => 'Please specify if this rate is active.',
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
            'is_default' => 'default status',
            'is_active' => 'active status',
        ];
    }
}
