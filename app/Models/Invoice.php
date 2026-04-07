<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Invoice extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'created_by',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'notes',
        'subtotal',
        'tax_total',
        'total',
        'currency',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'status' => InvoiceStatus::class,
        'currency' => Currency::class,
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'total' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * @return BelongsTo<Client, Invoice>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, Invoice>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InvoiceLineItem>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * @return HasMany<Payment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->label().number_format($this->total / 100, 2),
        );
    }
}
