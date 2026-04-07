<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = now()->year;
        $count = Invoice::query()->whereYear('created_at', $year)->count() + 1;

        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'invoice_number' => sprintf('INV-%d-%04d', $year, $count),
            'status' => 'draft',
            'issue_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 days', '+30 days')->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'currency' => 'INR',
        ];
    }
}
