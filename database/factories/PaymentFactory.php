<?php

declare(strict_types=1);

/**
 * PaymentFactory
 *
 * WHAT: Generates test Payment records (payments received against invoices).
 *
 * WHY: One invoice can have multiple payments (partial payments). This factory creates
 *      realistic payment records with amounts, dates, methods, and reference numbers.
 *      After a payment is recorded, an Observer (Day 5) updates the invoice status.
 *
 * IMPLEMENT: Complete. amount in cents/paise. payment_method cast to PaymentMethod enum.
 *            reference_number is optional but recommended for matching statements.
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Enums: App\\Enums\\PaymentMethod
 * - Models: App\\Models\\Payment
 */

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => $this->faker->numberBetween(10000, 500000), // cents
            'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'reference_number' => $this->faker->optional()->bothify('REF-########'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
