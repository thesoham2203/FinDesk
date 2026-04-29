<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_type' => Expense::class,
            'subject_id' => 1,
            'description' => $this->faker->sentence(),
            'properties' => [],
        ];
    }
}
