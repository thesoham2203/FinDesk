<?php

declare(strict_types=1);


namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    private function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '₹'.number_format($this->amount / 100, 2),
        );
    }
}
