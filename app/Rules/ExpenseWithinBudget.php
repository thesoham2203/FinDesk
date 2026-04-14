<?php

declare(strict_types=1);

/**
 * ExpenseWithinBudget Rule
 *
 * WHAT: Custom validation rule that checks if submitting an expense would cause
 *       the department's monthly total to exceed its budget.
 *
 * WHY: The HLD specifies: "Cannot submit an expense that would cause department's
 *      monthly total to exceed department budget." This is arguably the most
 *      complex validation in the system because it requires:
 *      1. Knowing the department and its monthly_budget
 *      2. Querying the sum of existing expenses for that department in current month
 *      3. Adding the new expense amount and checking if it exceeds budget
 *
 * IMPLEMENT: In the constructor, accept departmentId and amount (in cents).
 *            In validate(), query the sum of approved/submitted expenses for this
 *            department in the current month and compare to budget.
 *
 * KEY CONCEPTS:
 * - Rule Objects with state: __construct to pass parameters
 * - DB Facade for aggregate queries (SUM) with inline comment explaining why
 * - Date manipulation to define "current month"
 * - Month/year filtering: WHERE YEAR(created_at) = YEAR(now()), WHERE MONTH(created_at) = MONTH(now())
 *
 * USAGE (in Livewire or Form Request):
 *   'amount' => ['required', 'integer', new ExpenseWithinBudget($departmentId, $amount)]
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Support\Facades\DB;

final class ExpenseWithinBudget implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  int  $departmentId  The department ID
     * @param  int  $amount  The expense amount in cents
     */
    public function __construct(
        private readonly int $departmentId,
        private readonly int $amount,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The attribute being validated (usually 'amount')
     * @param  mixed  $value  The value being validated
     * @param  Closure(string): void  $fail  Closure to fail validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get department and its budget
        $department = \App\Models\Department::find($this->departmentId);
        if (! $department) {
            $fail('Department not found.');

            return;
        }

        // Using DB Facade for efficient aggregate query with complex WHERE conditions
        // to avoid multiple round-trips to the database
        $currentTotal = DB::table('expenses')
            ->where('department_id', $this->departmentId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'submitted'])
            ->sum('amount');

        $currentTotal = $currentTotal ?? 0;

        if ($currentTotal + $this->amount > $department->monthly_budget) {
            $fail("This expense would cause the department's monthly total to exceed the budget.");
        }
    }
}
