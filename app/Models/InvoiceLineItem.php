<?php

declare(strict_types=1);

/**
 * InvoiceLineItem Model
 *
 * WHAT: Line-item detail row on an invoice (quantity, unit price, tax, totals).
 *
 * WHY: Invoices comprise multiple line items. Each tracks quantity, unit_price,
 *      optional tax rate, and calculated totals (line_total, tax_amount).
 *      Supports fractional quantities (e.g., 1.5 hours).
 *
 * IMPLEMENT: Complete. All calculations happen in Livewire (Day 6).
 *            formattedXxx accessors use hardcoded ₹ symbol—converted to multi-currency in Day 6.
 *            Attachments allow per-item supporting documents (e.g., receipt, invoice scan).
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Polymorphic Attachments: https://laravel.com/docs/13.x/eloquent-relationships#polymorphic-relationships
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class InvoiceLineItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate_id',
        'line_total',
        'tax_amount',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'integer',
        'line_total' => 'integer',
        'tax_amount' => 'integer',
    ];

    /**
     * @return BelongsTo<Invoice, InvoiceLineItem>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<TaxRate, InvoiceLineItem>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Get all attachments (supporting documents) for this line item.
     *
     * @return MorphMany<Attachment>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function formattedLineTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '₹ '.number_format($this->line_total / 100, 2),
        );
    }

    public function formattedUnitPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '₹ '.number_format($this->unit_price / 100, 2),
        );
    }
}
