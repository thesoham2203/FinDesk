<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Carbon\CarbonInterface;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $logo_path
 * @property Currency $default_currency
 * @property int $fiscal_year_start
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
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
     * @var array<string, string>
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
        return cache()->rememberForever('organization', static fn (): self => self::query()->firstOrCreate(
            [],
            [
                'name' => config('app.name', 'FinDesk'),
                'default_currency' => Currency::INR,
                'fiscal_year_start' => 4,
            ],
        ));
    }
}
