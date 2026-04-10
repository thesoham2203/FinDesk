<?php

declare(strict_types=1);

/**
 * AttachmentFactory
 *
 * WHAT: Generates test Attachment records (files attached to Expenses or InvoiceLineItems).
 *
 * WHY: Expenses require receipt uploads, line items might have supporting docs.
 *      This factory creates attachment metadata (but not actual files—those are mocked).
 *      Uses polymorphic relationship (attachable_type/id) to attach to any model.
 *
 * IMPLEMENT: Complete. Generates realistic file metadata (MIME types, sizes).
 *            Stores file path but doesn't create actual files (tests mock storage).
 *            disk defaults to 'local' (Laravel's default filesystem).
 *
 * REFERENCE:
 * - Laravel Factories: https://laravel.com/docs/13.x/eloquent-factories
 * - Polymorphic Relationships: https://laravel.com/docs/13.x/eloquent-relationships#polymorphic-relationships
 * - Models: App\\Models\\Attachment
 */

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
            'path' => 'attachments/'.$this->faker->uuid().'.pdf',
            'disk' => 'local',
            'original_name' => $this->faker->words(2, asText: true).'.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(100000, 5000000), // bytes
        ];
    }
}
