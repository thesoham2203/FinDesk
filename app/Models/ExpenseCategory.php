<?php

declare(strict_types=1);

/**
 * ExpenseCategory Model
 *
 * WHAT: Categories that classify expenses (Travel, Meals, Office Supplies, etc.).
 *
 * WHY: Categorization enables expense reporting and policy enforcement.
 *      Categories can mandate requirements (max_amount cap, requires_receipt flag)
 *      to enforce company policy.
 *
 * IMPLEMENT: Complete. formattedMaxAmount handles null (no limit) gracefully.
 *            Category validation rules are applied during expense submission (Day 6 — Livewire).
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Eloquent Mutators: https://laravel.com/docs/13.x/eloquent-mutators
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'max_amount',
        'requires_receipt',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'max_amount' => 'integer',
        'requires_receipt' => 'boolean',
    ];

    /**
     * @return HasMany<Expense>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function formattedMaxAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->max_amount === null
            ? 'No limit'
            : '₹'.number_format($this->max_amount / 100, 2),
        );
    }
}
