<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use Carbon\CarbonInterface;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

/**
 * @property-read int $id
 * @property string $user_id
 * @property int $category_id
 * @property int $department_id
 * @property string $title
 * @property string|null $description
 * @property int $amount
 * @property int $reimbursed_amount
 * @property int $due_amount
 * @property Currency $currency
 * @property ExpenseStatus $status
 * @property string|null $receipt_path
 * @property CarbonInterface|null $submitted_at
 * @property CarbonInterface|null $reviewed_at
 * @property string|null $reviewed_by
 * @property string|null $rejection_reason
 * @property CarbonInterface|null $date
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read string $formatted_amount
 * @property-read string $formatted_reimbursed_amount
 * @property-read string $formatted_due_amount
 */
final class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'department_id',
        'title',
        'description',
        'amount',
        'reimbursed_amount',
        'due_amount',
        'currency',
        'status',
        'receipt_path',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'date',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'category_id' => 'integer',
            'department_id' => 'integer',
            'status' => ExpenseStatus::class,
            'currency' => Currency::class,
            'amount' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'date' => 'date',
            'reimbursed_amount' => 'integer',
            'due_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all activities (audit log) for this expense.
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * @throws InvalidArgumentException if transition is invalid
     */
    public function transitionTo(ExpenseStatus $newStatus): void
    {
        $allowedTransitions = $this->status->allowedTransitions();

        if (! in_array($newStatus, $allowedTransitions, true)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot transition from %s to %s',
                $this->status->label(),
                $newStatus->label(),
            ));
        }

        $this->status = $newStatus;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->amount / 100, 2),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedReimbursedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->reimbursed_amount / 100, 2),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedDueAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->due_amount / 100, 2),
        );
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    protected function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    protected function scopeWithStatus(Builder $query, ExpenseStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    protected function scopeSubmittedInMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('submitted_at', $year)
            ->whereMonth('submitted_at', $month);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    protected function scopePending(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Submitted);
    }
}
