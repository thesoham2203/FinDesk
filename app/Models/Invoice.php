<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * @property-read int $id
 * @property int $client_id
 * @property string $created_by
 * @property string $invoice_number
 * @property InvoiceStatus $status
 * @property CarbonInterface $issue_date
 * @property CarbonInterface $due_date
 * @property string|null $notes
 * @property int $subtotal
 * @property int $tax_total
 * @property int $total
 * @property Currency $currency
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read string $formatted_subtotal
 * @property-read string $formatted_tax_total
 * @property-read string $formatted_total
 * @property-read int $amount_due
 */
final class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InvoiceLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the most recent payment for this invoice (Has One of Many).
     *
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany('payment_date');
    }

    /**
     * Get all activities (audit log) for this invoice.
     *
     * @return MorphMany<Activity, $this>
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
            'user_id' => Auth::id(),
            'subject_type' => self::class,
            'subject_id' => $this->id,
            'description' => sprintf('Invoice status changed from %s to %s', $this->status->label(), $newStatus->label()),
        ]);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (int) $this->lineItems()->sum('line_total');
        $taxTotal = (int) $this->lineItems()->sum('tax_amount');
        $total = $subtotal + $taxTotal;

        $this->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'currency' => Currency::class,
            'subtotal' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->subtotal / 100, 2),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedTaxTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->tax_total / 100, 2),
        );
    }

    /**
     * @return Attribute<string, never>
     */
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
            get: fn (): int => $this->total - (int) $this->payments()->sum('amount'),
        );
    }
}
