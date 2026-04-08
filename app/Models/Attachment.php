<?php

declare(strict_types=1);

/**
 * Attachment Model
 *
 * WHAT: File storage and metadata using polymorphic relationships.
 *
 * WHY: Multiple models need file uploads (Expense receipts, InvoiceLineItem docs).
 *      Instead of separate tables per model, use polymorphic relation for flexibility.
 *      attachable_type/attachable_id point to any model (Expense, InvoiceLineItem).
 *
 * IMPLEMENT: Complete. attachable() MorphTo loads the parent model.
 *            Stores original filename, MIME type, size, disk, and upload user.
 *
 * REFERENCE:
 * - Eloquent Relationships (Polymorphic): https://laravel.com/docs/13.x/eloquent-relationships#polymorphic-relationships
 * - File Storage: https://laravel.com/docs/13.x/filesystem
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Attachment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'user_id',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'size',
    ];

    /**
     * @return MorphTo<Model>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, Attachment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
