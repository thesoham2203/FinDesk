<?php

declare(strict_types=1);

/**
 * Invoice Model
 *
 * WHAT: Header document issued to clients. Invoices have line items and track payments.
 *
 * WHY: FinDesk generates invoices for clients. Invoices follow a state machine
 *      (Draft → Sent → [Viewed|PartiallyPaid|Paid|Overdue|Cancelled]).
 *      All money columns denormalized for query performance.
 *      Status updates automatically when payments recorded (Observer pattern, Day 5).
 *
 * IMPLEMENT: Complete. latestPayment() uses HasOne ofMany pattern.
 *            amountDue = total - sum(payments) calculated in accessor, not stored.
 *            formattedXxx accessors use Currency::symbol() for multi-currency display.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Has One of Many: https://laravel.com/docs/13.x/eloquent-relationships#has-one-of-many
 * - Enums: App\\Enums\\InvoiceStatus, Currency
 */

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Attribute;
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

    public function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->currency->symbol() . ' ' . number_format($this->subtotal / 100, 2),
        );
    }

    public function formattedTaxTotal(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->currency->symbol() . ' ' . number_format($this->tax_total / 100, 2),
        );
    }

    public function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->currency->symbol() . ' ' . number_format($this->total / 100, 2),
        );
    }

    /**
     * Amount still due (total - sum of payments).
     * Computed value, not stored in database.
     *
     * @return Attribute<int, never>
     */
    public function amountDue(): Attribute
    {
        return Attribute::make(
            get: fn(): int => $this->total - $this->payments()->sum('amount'),
        );
    }

    /**
     * Transition the invoice to a new status, validating via the state machine.
     *
     * TODO: Implement state machine validation using InvoiceStatus::allowedTransitions()
     *       - Throw InvalidInvoiceTransition if the new status is not in allowed transitions
     *       - Call Activity::create() to log this transition (Day 5 — Observers)
     *
     * @throws InvalidArgumentException if transition is invalid
     */
    public function transitionTo(InvoiceStatus $newStatus): void
    {
        $allowedTransitions = $this->status->allowedTransitions();

        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot transition invoice from %s to %s. Allowed transitions: %s',
                    $this->status->value,
                    $newStatus->value,
                    implode(', ', array_map(fn(InvoiceStatus $s) => $s->value, $allowedTransitions))
                )
            );
        }

        $this->update(['status' => $newStatus]);

        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'description' => sprintf('Invoice status changed from %s to %s', $this->status->label(), $newStatus->label()),
        ]);
    }

    /**
     * Recalculate and update subtotal, tax_total, and total from line items.
     *
     * TODO: Implement calculation logic:
     *       - subtotal = sum of all line_items.line_total
     *       - tax_total = sum of all line_items.tax_amount
     *       - total = subtotal + tax_total
     *       - Save and dispatch event (Day 5)
     */
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
}
