<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
