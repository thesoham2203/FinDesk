<?php

declare(strict_types=1);

/**
 * ExpenseCategoryFactory
 *
 * WHAT: Generates test ExpenseCategory records with varied budget caps and receipt requirements.
 *
 * WHY: Expense submission tests need categories with different policy rules.
 *      Some categories mandate receipt uploads, others have per-expense limits.
 *
 * IMPLEMENT: Complete. max_amount is nullable (some categories have no limit).
 *            Amount in cents/paise. requires_receipt is random (50% of categories require it).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Models: App\Models\ExpenseCategory
 */

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
final class ExpenseCategoryFactory extends Factory
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
            'max_amount' => $this->faker->randomElement([null, 100000, 500000, 1000000]),
            'requires_receipt' => $this->faker->boolean(),
        ];
    }
}
