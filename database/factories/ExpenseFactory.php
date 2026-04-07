<?php

declare(strict_types=1);

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
            'submission_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ];
    }
}
