<?php

declare(strict_types=1);

/**
 * StoreInvoiceRequest
 *
 * WHAT: Validates data when CREATING a new invoice (Day 6).
 *
 * WHY: This is scaffolded NOW but will be USED on Day 6 in the InvoiceForm Livewire
 *      component. Invoice creation is complex:
 *      1. Client selection (must exist)
 *      2. Dates (due_date must be after or equal to issue_date)
 *      3. Line items (array validation: multiple rows with their own rules)
 *      4. Tax rate IDs must exist (nullable because tax can be 0%)
 *
 * IMPLEMENT:
 *      1. Validate client_id, issue_date, due_date, notes, currency
 *      2. Validate line_items array: required, min 1 row, each row validated
 *      3. Each line item: description, quantity, unit_price, tax_rate_id
 *      4. Consider custom rule to ensure at least one line item with valid data
 *
 * KEY CONCEPTS:
 * - Array validation: https://laravel.com/docs/13.x/validation#validating-arrays
 * - Date validation: https://laravel.com/docs/13.x/validation#date-formats
 * - Nested array rules: line_items.*.field syntax
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

final class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Check if user can create invoices: $this->user()->can('create', Invoice::class)
        //       or use Gate::allows('manage-invoices')
        Gate::authorize('manage-invoices');

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
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['required', 'string', 'in:USD,EUR,GBP,INR'],

            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'integer', 'min:1'],
            'line_items.*.tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
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
            'client_id.required' => 'Client is required.',
            'client_id.exists' => 'Selected client does not exist.',
            'issue_date.required' => 'Issue date is required.',
            'issue_date.date' => 'Issue date must be a valid date.',
            'due_date.required' => 'Due date is required.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be on or after the issue date.',
            'currency.required' => 'Currency is required.',
            'line_items.required' => 'At least one line item is required.',
            'line_items.min' => 'At least one line item is required.',
            'line_items.*.description.required' => 'Line item description is required.',
            'line_items.*.quantity.required' => 'Line item quantity is required.',
            'line_items.*.unit_price.required' => 'Line item unit price is required.',
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
            'client_id' => 'client',
            'issue_date' => 'issue date',
            'due_date' => 'due date',
            'line_items' => 'line items',
        ];
    }

    /**
     * TODO: Add withValidator() or after() hook if needed:
     *       - Ensure all line items together have at least one with non-zero amount
     *       - Validate that tax_rate_id (if provided) is active (if required)
     *       - Ensure invoice totals are calculated correctly (might be done in component)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lineItems = $this->input('line_items', []);
            $hasValidLineItem = false;

            foreach ($lineItems as $item) {
                $quantity = $item['quantity'] ?? 0;
                $unitPrice = $item['unit_price'] ?? 0;

                if ($quantity > 0 && $unitPrice > 0) {
                    $hasValidLineItem = true;
                    break;
                }
            }

            if (! $hasValidLineItem) {
                $validator->errors()->add('line_items', 'At least one line item must have a quantity and unit price greater than zero.');
            }
        });
    }
}
