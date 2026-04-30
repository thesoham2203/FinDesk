<?php

declare(strict_types=1);

/**
 * CategoryNotInUse Rule
 *
 * WHAT: Custom validation rule that checks if an expense category has any expenses
 *       associated with it. Used when DELETING a category.
 *
 * WHY: The HLD specifies: "Cannot delete a category that has expenses associated
 *      with it." This is enforcement at the validation layer. If someone tries to
 *      delete, the rule fails with a clear error message.
 *
 * IMPLEMENT: In the validate() method, query the category by $value (the category ID),
 *            then check if it has any related expenses using the expenses() relationship.
 *            If count > 0, call $fail() with a custom message.
 *
 * KEY CONCEPTS:
 * - Rule Objects: https://laravel.com/docs/13.x/validation#custom-validation-rules
 * - Implement ValidationRule interface with validate() method
 * - Use $fail closure to report failures
 * - Can use relationships to check data integrity
 */

namespace App\Rules;

use App\Models\ExpenseCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class CategoryNotInUse implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The attribute being validated
     * @param  mixed  $value  The value being validated (category ID)
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail  Closure to fail validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail('Category not found.');

            return;
        }

        $category = ExpenseCategory::query()->find((int) $value);

        if (! $category) {
            $fail('Category not found.');
        } elseif ($category->expenses()->exists()) {
            $expenseCount = $category->expenses()->count();

            $fail(sprintf('This category cannot be deleted because it has %s expenses.', $expenseCount));
        }
    }
}
