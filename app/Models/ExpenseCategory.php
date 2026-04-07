<?php

declare(strict_types=1);

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
            get: fn(): string => $this->max_amount === null
            ? 'No limit'
            : '₹' . number_format($this->max_amount / 100, 2),
        );
    }
}
