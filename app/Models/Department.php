<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * @return HasMany<Expense>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function formattedBudget(): Attribute
    {
        return Attribute::make(
            get: fn(): string => '₹' . number_format($this->monthly_budget / 100, 2),
        );
    }
}
