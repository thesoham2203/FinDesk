<?php

declare(strict_types=1);

/**
 * InvoiceFactory
 *
 * WHAT: Generates test Invoice records in various workflow states (Draft, Sent, Paid, etc.).
 *
 * WHY: Invoice tests need varied states. States represent invoice lifecycle:
 *      Draft → Sent → [Viewed|PartiallyPaid|Paid|Overdue|Cancelled].
 *
 * IMPLEMENT: Complete. Base definition creates a Draft invoice. States set status and
 *            related timestamps (automatically through state transitions).
 *            Auto-generates invoice_number as INV-YYYY-NNNN.
 *            All money columns in cents/paise.
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Enums: App\Enums\InvoiceStatus
 * - Models: App\Models\Invoice
 */

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

    /**
     * Sent state — invoice issued to client.
     */
    public function sent(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'sent',
        ]);
    }

    /**
     * Paid state — invoice fully paid by client.
     */
    public function paid(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'paid',
            'issue_date' => now()->subDays(30)->format('Y-m-d'),
            'due_date' => now()->subDays(10)->format('Y-m-d'),
        ]);
    }

    /**
     * Overdue state — invoice due date passed without payment.
     */
    public function overdue(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'overdue',
            'issue_date' => now()->subDays(60)->format('Y-m-d'),
            'due_date' => now()->subDays(20)->format('Y-m-d'),
        ]);
    }
}
