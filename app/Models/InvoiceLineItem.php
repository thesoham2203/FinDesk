<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
