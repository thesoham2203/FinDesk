<?php

declare(strict_types=1);

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
