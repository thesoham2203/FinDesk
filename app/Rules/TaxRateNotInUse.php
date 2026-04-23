<?php

declare(strict_types=1);

/**
 * TaxRateNotInUse Rule
 *
 * WHAT: Custom validation rule that checks if a tax rate is used on any invoice
 *       line item. Used when DELETING a tax rate.
 *
 * WHY: The HLD specifies: "Cannot delete a tax rate that is used on any invoice
 *      line item." Tax rates are immutable once invoices use them for accounting
 *      and audit purposes. This rule enforces that constraint.
 *
 * IMPLEMENT: In the validate() method, query the tax rate by $value (the tax rate ID),
 *            then check if it has any related invoice line items using the lineItems()
 *            relationship. If count > 0, call $fail() with an error message.
 *
 * KEY CONCEPTS:
 * - Rule Objects: https://laravel.com/docs/13.x/validation#custom-validation-rules
 * - Same pattern as CategoryNotInUse but for a different entity
 * - Check relationships to enforce referential integrity
 */

namespace App\Rules;

use App\Models\TaxRate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class TaxRateNotInUse implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated (tax rate ID)
     * @param  Closure(string): void  $fail  Closure to fail validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $taxRate = TaxRate::query()->withCount('lineItems')->find($value);
        if (! $taxRate) {
            $fail('Tax rate not found.');
        } elseif ($taxRate->line_items_count > 0) {
            $fail(sprintf('This tax rate cannot be deleted because it is used on %s invoice line items.', $taxRate->line_items_count));
        }
    }
}
