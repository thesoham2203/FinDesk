<?php

declare(strict_types=1);

/**
 * OrganizationFactory
 *
 * WHAT: Generates the single Organization record (company-wide settings).
 *
 * WHY: FinDesk is installed per-organization. This factory creates the singleton record
 *      with organization name, address, logo path, default currency, and fiscal year start.
 *
 * IMPLEMENT: Complete. Only one row should exist in the organizations table per installation.
 *            Use Organization::current() to fetch it (cached static method).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\\Models\\Organization
 * - Enums: App\\Enums\\Currency
 */

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
final class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'logo_path' => null,
            'default_currency' => 'INR',
            'fiscal_year_start' => 4, // April for India
        ];
    }
}
