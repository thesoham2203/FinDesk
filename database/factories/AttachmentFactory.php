<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
final class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attachable_type' => Expense::class,
            'attachable_id' => 1,
            'user_id' => User::factory(),
            'path' => sprintf('attachments/%s.pdf', $this->faker->uuid()),
            'disk' => 'local',
            'original_name' => sprintf('%s %s.pdf', $this->faker->word(), $this->faker->word()),
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(100000, 5000000), // bytes
        ];
    }
}
