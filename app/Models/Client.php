<?php

declare(strict_types=1);

/**
 * Client Model
 *
 * WHAT: External parties (companies/individuals) who receive invoices.
 *
 * WHY: FinDesk generates invoices for clients. Clients are NOT users of the system—
 *      they may receive invoice links or emails but don't log in. Track contact info
 *      (email, phone), address, and tax number (GST/VAT) for invoicing.
 *
 * IMPLEMENT: Complete. No additional methods needed.
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Client extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'notes',
    ];

    /**
     * @return HasMany<Invoice>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
