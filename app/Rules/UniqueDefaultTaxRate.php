<?php

declare(strict_types=1);

/**
 * UniqueDefaultTaxRate Rule
 *
 * WHAT: Ensures only one tax rate can be marked as default at a time.
 *
 * WHY: When a user marks a tax rate as default (for invoice line item pre-selection),
 *      we need to ensure no other tax rate is also marked as default. This rule
 *      enforces that constraint.
 *
 * IMPLEMENT: In the constructor, accept optional $excludeId (for update scenarios).
 *            In validate(), if $value is true (is_default = true), check if any
 *            other TaxRate has is_default=true. If found, fail. If $value is false,
 *            always pass (multiple rates can be non-default).
 *
 * ALTERNATIVE APPROACH: Instead of using this rule, the Livewire component could
 * automatically unset the previous default when creating a new one. The choice
 * depends on HLD specs — check if validation failure is preferred or auto-unset.
 *
 * KEY CONCEPTS:
 * - Rule Objects with optional parameters
 * - Conditional rule application: only validates when is_default=true
 * - Exclusion logic: ignore current record when updating
 */

namespace App\Rules;

use App\Models\TaxRate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class UniqueDefaultTaxRate implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  int|null  $excludeId  The tax rate ID to exclude when checking (for updates)
     */
    public function __construct(
        private ?int $excludeId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The attribute being validated (usually 'is_default')
     * @param  mixed  $value  The value being validated (true/false)
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail  Closure to fail validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN); // Ensure we have a boolean value
        if (! $value) {
            return;
        }

        $query = TaxRate::query()->where('is_default', true);
        if ($this->excludeId !== null) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('Another tax rate is already set as default. Please unset the other default before setting this one.');
        }
    }
}
