<?php

declare(strict_types=1);

/**
 * Organization Model
 *
 * WHAT: Single-row model storing organization-wide settings and configuration.
 *
 * WHY: FinDesk is installed per-organization. This table holds company settings:
 *      name, default currency, fiscal year start, branding (logo path).
 *      Only ONE row should exist per installation. Use Organization::current() cache
 *      to fetch it throughout the app.
 *
 * IMPLEMENT: Complete. Implement the static current() method to cache the organization:
 *            - Call cache()->rememberForever('org', fn() => self::first())
 *            - Clear cache in Observer when organization is updated (Day 5)
 *            - Use this everywhere instead of Organization::first()
 *
 * REFERENCE:
 * - Eloquent Relationships: https://laravel.com/docs/13.x/eloquent-relationships
 * - Caching: https://laravel.com/docs/13.x/cache
 * - Enums: App\\Enums\\Currency
 */

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Organization extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'logo_path',
        'default_currency',
        'fiscal_year_start',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'default_currency' => Currency::class,
        'fiscal_year_start' => 'integer',
    ];

    /**
     * Fetch the organization singleton, cached indefinitely.
     *
     * The cache is cleared when the organization is updated (see Observer).
     */
    public static function current(): self
    {
        return cache()->rememberForever('organization', fn () => self::first());
    }
}
