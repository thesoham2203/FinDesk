<?php

declare(strict_types=1);

/**
 * DepartmentFactory
 *
 * WHAT: Generates test Department records with realistic names and budgets.
 *
 * WHY: Tests for department-related features (budget tracking, expense grouping) need
 *      varied departments. Budget is in cents/paise (stored in database).
 *
 * IMPLEMENT: Complete. Generates random department names with budgets between
 *            ₹10,000–₹100,000 (stored as 1000000–10000000 paise).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\Models\Department
 */

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
final class DepartmentFactory extends Factory
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
            'description' => $this->faker->sentence(),
            'monthly_budget' => $this->faker->numberBetween(1000000, 10000000),
        ];
    }
}
