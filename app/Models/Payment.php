<?php

declare(strict_types=1);

/**
 * Payment Model
 *
 * WHAT: Records payments received against invoices (supports partial payments).
 *
 * WHY: One invoice can have multiple payments. They must be tracked individually for
 *      reconciliation and audit purposes. Sum of payments determines invoice status.
 *
 * IMPLEMENT: Complete. Observer (Day 5) fires PaymentCreated event to update parent
 *            invoice status after a payment is recorded.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Enums: App\\Enums\\PaymentMethod
 * - Observers: https://laravel.com/docs/13.x/eloquent#events-using-observers
 */

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'amount' => 'integer',
        'payment_method' => PaymentMethod::class,
        'payment_date' => 'date',
    ];

    /**
     * @return BelongsTo<Invoice, Payment>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '₹'.number_format($this->amount / 100, 2),
        );
    }
}
