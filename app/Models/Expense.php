<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

final class Expense extends Model
{
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
        'currency',
        'status',
        'receipt_path',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'status' => ExpenseStatus::class,
        'currency' => Currency::class,
        'amount' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'date' => 'date',
    ];

    /**
     * @return BelongsTo<User, Expense>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExpenseCategory, Expense>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return BelongsTo<Department, Expense>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, Expense>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return MorphMany<Attachment>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all activities (audit log) for this expense.
     *
     * @return MorphMany<Activity>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->symbol().' '.number_format($this->amount / 100, 2),
        );
    }

    /**
     * Transition the expense to a new status, validating via the state machine.
     *
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
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        // TODO: Filter expenses by department_id
        return $query->where('department_id', $departmentId);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeWithStatus(Builder $query, ExpenseStatus $status): Builder
    {
        // TODO: Filter expenses by status
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeSubmittedInMonth(Builder $query, int $year, int $month): Builder
    {
        // TODO: Filter expenses submitted in a specific year/month for budget calculations
        return $query->whereYear('submitted_at', $year)
            ->whereMonth('submitted_at', $month);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ExpenseStatus::Submitted);
    }
}
