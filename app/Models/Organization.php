<?php

declare(strict_types=1);

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
        return cache()->rememberForever('organization', fn () => self::query()->first());
    }
}
