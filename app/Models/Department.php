<?php

declare(strict_types=1);

/**
 * Department Model
 *
 * WHAT: Organizational units (Sales, Engineering, Admin, etc.) with monthly budgets.
 *
 * WHY: FinDesk tracks expenses per department. Departments have budget limits for
 *      reporting and policy enforcement. Organization manages departments centrally.
 *
 * IMPLEMENT: Complete. HasManyThrough relates expenses (Department → Users → Expenses).
 *            formattedBudget displays from cents (50000_00 paise = ₹500.00).
 *            scopeWithBudgetUsage is a stub—implement in Day 4 to add subquery for
 *            current month's spent total.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Has Many Through: https://laravel.com/docs/13.x/eloquent-relationships#has-many-through
 * - Query Builder Subqueries: https://laravel.com/docs/13.x/queries#subqueries
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

final class Department extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'monthly_budget',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'monthly_budget' => 'integer',
    ];

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all expenses for users in this department (HasManyThrough).
     * This is a key relationship: Department → Users → Expenses.
     *
     * @return HasManyThrough<Expense>
     */
    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, User::class);
    }

    public function formattedBudget(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '₹ '.number_format($this->monthly_budget / 100, 2),
        );
    }

    /**
     * Scope that adds budget usage (current month's expenses) as a subquery.
     *
     * TODO: Implement subquery to calculate current month's total expenses for budget reporting.
     *       Example:
     *       $query->addSelect(['monthly_spent' => Expense::query()
     *           ->whereBelongsTo($this)
     *           ->whereYear('submitted_at', now()->year)
     *           ->whereMonth('submitted_at', now()->month)
     *           ->select(\DB::raw('sum(amount)'))
     *       ]);
     *
     * @param  Builder<Department>  $query
     * @return Builder<Department>
     */
    public function scopeWithBudgetUsage(Builder $query): Builder
    {
        // TODO: [Implement budget usage subquery]

        $query->addSelect(['monthly_spent' => Expense::query()
            ->whereBelongsTo($this)
            ->whereYear('submitted_at', now()->year)
            ->whereMonth('submitted_at', now()->month)
            ->select(DB::raw('sum(amount)')),
        ]);

        return $query;
    }
}
