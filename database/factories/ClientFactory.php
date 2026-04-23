<?php

declare(strict_types=1);

/**
 * ClientFactory
 *
 * WHAT: Generates test Client records (external invoice recipients).
 *
 * WHY: Invoice tests need client data. Clients are companies/individuals who receive
 *      invoices from the organization. Track contact info, address, and tax number.
 *
 * IMPLEMENT: Complete. Generates realistic company names, emails, phone numbers,
 *            addresses, and fake GST/VAT numbers.
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\Models\Client
 */

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
final class ClientFactory extends Factory
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
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'tax_number' => $this->faker->bothify('###########'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
