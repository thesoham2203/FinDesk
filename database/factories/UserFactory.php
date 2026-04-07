<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'role' => UserRole::Employee,
            'department_id' => Department::factory(),
            'manager_id' => null,
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Admin,
            'department_id' => null,
            'manager_id' => null,
        ]);
    }

    public function manager(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Manager,
            'department_id' => Department::factory(),
            'manager_id' => null,
        ]);
    }

    public function employee(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Employee,
            'department_id' => Department::factory(),
            'manager_id' => null,
        ]);
    }

    public function accountant(): self
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Accountant,
            'department_id' => null,
            'manager_id' => null,
        ]);
    }
}
