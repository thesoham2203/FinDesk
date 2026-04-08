<?php

declare(strict_types=1);

/**
 * Activity Model
 *
 * WHAT: Immutable event log tracking all actions (created, approved, rejected, paid) on any model.
 *
 * WHY: Audit trail for compliance/debugging. Uses polymorphic relationships to cover Expenses,
 *      Invoices, Payments—any model. Activities are never updated, only created. No updated_at.
 *
 * IMPLEMENT: The model structure is complete. In Day 5, Observers will fire ActivityCreated events.
 *            The subject() MorphTo relationship allows Activity::query()->with('subject')
 *            to eager-load the related Expense, Invoice, etc.
 *
 * REFERENCE:
 * - Polymorphic Relations: https://laravel.com/docs/13.x/eloquent-relationships#polymorphic-relationships
 * - Observers: https://laravel.com/docs/13.x/eloquent#events-using-observers
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Activity extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'description',
        'properties',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * @return BelongsTo<User, Activity>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent model (Expense, Invoice, Payment, etc.) that this activity is for.
     *
     * @return MorphTo<Model>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
