<?php

declare(strict_types=1);

/**
 * UserFactory
 *
 * WHAT: Generates test User records with different roles and organizational relationships.
 *
 * WHY: Tests need realistic user data. This factory provides flexible role/department
 *      assignment via state methods (admin(), manager(), employee(), accountant()).
 *
 * IMPLEMENT: Complete. Base definition creates an Employee in a random department.
 *            States override role and department_id/manager_id appropriately.
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Enums: App\Enums\UserRole
 */

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
        return $this->state(function (array $attributes): array {
            $department = Department::inRandomOrder()->first();
            $departmentId = $department?->id ?? Department::factory()->create()->id;
            $manager = User::where('role', UserRole::Manager)
                ->where('department_id', $departmentId)
                ->first();

            return [
                'role' => UserRole::Employee,
                'department_id' => $departmentId,
                'manager_id' => $manager?->id,
            ];
        });
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
