<?php

declare(strict_types=1);

/**
 * TaxRateFactory
 *
 * WHAT: Generates test TaxRate records (GST, VAT, etc.) for invoice line item taxation.
 *
 * WHY: Invoice tests need tax rates. Rates can be active/inactive, default or not.
 *      States allow creating specific rate scenarios (default rate, inactive historical rates).
 *
 * IMPLEMENT: Complete. Base definition creates an inactive, non-default rate.
 *            States: default() marks is_default=true, inactive() marks is_active=false.
 *            Percentage is realistic (0%, 5%, 12%, 18%, 28% like India's GST).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\Models\TaxRate
 */

namespace Database\Factories;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
final class TaxRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'percentage' => $this->faker->randomElement([0.0, 5.0, 12.0, 18.0, 28.0]),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * Default tax rate (is_default=true).
     */
    public function default(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Inactive tax rate (is_active=false) — kept for historical accuracy.
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
