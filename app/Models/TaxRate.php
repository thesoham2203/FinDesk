<?php

declare(strict_types=1);

/**
 * TaxRate Model
 *
 * WHAT: Tax rates (GST, VAT, etc.) applied to invoice line items.
 *
 * WHY: Tax rates change over time and vary by region. Admins manage current rates.
 *      Historical rates are marked inactive (not deleted) so existing invoices remain
 *      accurate for audit and reporting.
 *
 * IMPLEMENT: Complete. Only one rate should have is_default=true.
 *            Scopes: active() filters to is_active=true, default() filters to is_default=true.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Query Scopes: https://laravel.com/docs/13.x/eloquent#query-scopes
 */

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
    private function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<TaxRate>  $query
     * @return Builder<TaxRate>
     */
    #[Scope(visible: false)]
    private function default(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
