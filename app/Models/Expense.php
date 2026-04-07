<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Attributes\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->currency->label().number_format($this->amount / 100, 2),
        );
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
