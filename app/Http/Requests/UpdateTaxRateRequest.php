<?php

declare(strict_types=1);

/**
 * UpdateTaxRateRequest
 *
 * WHAT: Validates data when UPDATING an existing tax rate.
 *
 * WHY: Same validation rules as StoreTaxRateRequest. Separated into two
 *      classes for consistency with Laravel conventions and potential
 *      future divergence (e.g., different authorization for create vs update).
 *
 * IMPLEMENT: Add authorize() method body. Rules are identical to StoreTaxRateRequest.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateTaxRateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Implement authorization check
        // return Gate::allows('manage-tax-rates');
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
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'is_default' => 'required|boolean',
            'is_active' => 'required|boolean',
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
