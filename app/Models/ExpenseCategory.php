<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
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
     * @var array<string, string>
     */
    protected $casts = [
        'max_amount' => 'integer',
        'requires_receipt' => 'boolean',
    ];

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedMaxAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->max_amount === null
            ? 'No limit'
            : '₹'.number_format($this->max_amount / 100, 2),
        );
    }
}
