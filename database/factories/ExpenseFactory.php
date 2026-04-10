<?php

declare(strict_types=1);

/**
 * ExpenseFactory
 *
 * WHAT: Generates test Expense records in various workflow states (Draft, Submitted, Approved, etc.).
 *
 * WHY: Tests for expense workflows need varied states. States represent workflow progression:
 *      Draft → Submitted → [Approved|Rejected] → Reimbursed.
 *
 * IMPLEMENT: Complete. Base definition creates a Draft expense. States set status and
 *            related timestamps (submitted_at, reviewed_at, reviewed_by, rejection_reason).
 *            Amount is in cents/paise. User automatically assigned to the department.
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Enums: App\Enums\ExpenseStatus
 * - Models: App\Models\Expense
 */

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $department = Department::factory();

        return [
            'user_id' => User::factory()->create(['department_id' => $department]),
            'department_id' => $department,
            'category_id' => ExpenseCategory::factory(),
            'title' => $this->faker->words(3, asText: true),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'currency' => 'INR',
            'status' => ExpenseStatus::Draft,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Submitted state — expense moved from Draft to Submitted.
     */
    public function submitted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Approved state — expense reviewed and approved by manager.
     */
    public function approved(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Approved,
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Rejected state — expense reviewed and rejected by manager.
     */
    public function rejected(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Rejected,
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Reimbursed state — expense approved and reimbursed to user.
     */
    public function reimbursed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ExpenseStatus::Reimbursed,
            'submitted_at' => now()->subDays(7),
            'reviewed_at' => now()->subDays(3),
            'reviewed_by' => User::factory(),
        ]);
    }
}
