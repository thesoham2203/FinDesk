<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TaxRate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'percentage',
        'is_default',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'percentage' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<InvoiceLineItem>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * @param  Builder<TaxRate>  $query
     * @return Builder<TaxRate>
     */
    #[Scope(visible: false)]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<TaxRate>  $query
     * @return Builder<TaxRate>
     */
    #[Scope(visible: false)]
    protected function default(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
