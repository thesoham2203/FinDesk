<?php

declare(strict_types=1);

/**
 * InvoiceLineItemFactory
 *
 * WHAT: Generates test InvoiceLineItem records (line details on invoices).
 *
 * WHY: Invoices comprise multiple line items. This factory creates items with
 *      quantity, unit_price, optional tax rates, and calculated totals.
 *      Supports fractional quantities (e.g., 1.5 hours for service billing).
 *
 * IMPLEMENT: Complete. quantity is decimal (can be fractional).
 *            All money in cents/paise. line_total and tax_amount calculated
 *            in Livewire during invoice editing (Day 6).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\\Models\\InvoiceLineItem
 */

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLineItem>
 */
final class InvoiceLineItemFactory extends Factory
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
            'description' => $this->faker->words(3, asText: true),
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => $this->faker->numberBetween(10000, 500000), // cents
            'tax_rate_id' => TaxRate::factory(),
            'line_total' => 0, // Calculated: quantity * unit_price
            'tax_amount' => 0, // Calculated: line_total * (tax_rate / 100)
        ];
    }
}
