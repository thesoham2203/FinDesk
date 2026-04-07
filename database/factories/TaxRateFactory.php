<?php

declare(strict_types=1);

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
}
