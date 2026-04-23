<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

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
     * @var list<string>
     */
    protected $appends = [
        'formatted_subtotal',
        'formatted_tax_total',
        'formatted_total',
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

    /**
     * Get the most recent payment for this invoice (Has One of Many).
     *
     * @return HasOne<Payment>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany('payment_date');
    }

    /**
     * Get all activities (audit log) for this invoice.
     *
     * @return MorphMany<Activity>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Transition the invoice to a new status, validating via the state machine.
     *
     * @throws InvalidArgumentException if transition is invalid
     */
    public function transitionTo(InvoiceStatus $newStatus): void
    {
        $allowedTransitions = $this->status->allowedTransitions();

        if (! in_array($newStatus, $allowedTransitions, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot transition invoice from %s to %s. Allowed transitions: %s',
                    $this->status->value,
                    $newStatus->value,
                    implode(', ', array_map(fn (InvoiceStatus $s) => $s->value, $allowedTransitions))
                )
            );
        }

        $this->update(['status' => $newStatus]);

        Activity::query()->create([
            'user_id' => auth()->id(),
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'description' => sprintf('Invoice status changed from %s to %s', $this->status->label(), $newStatus->label()),
        ]);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lineItems()->sum('line_total');
        $taxTotal = $this->lineItems()->sum('tax_amount');
        $total = $subtotal + $taxTotal;

        $this->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ]);
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->subtotal / 100, 2),
        );
    }

    protected function formattedTaxTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->tax_total / 100, 2),
        );
    }

    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->total / 100, 2),
        );
    }

    /**
     * Amount still due (total - sum of payments).
     * Computed value, not stored in database.
     *
     * @return Attribute<int, never>
     */
    protected function amountDue(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->total - $this->payments()->sum('amount'),
        );
    }
}
