<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'type' => Message::TypeText,
            'body' => fake()->randomElement([
                fake()->sentence(8),
                fake()->sentence(12),
                fake()->paragraph(2),
                'I pushed a cleaner version. Can you take a look?',
                'This is ready from my side.',
                'Let me know what you think about the latest update.',
            ]),
            'metadata' => null,
            'edited_at' => null,
        ];
    }

    public function file(array $metadata = []): static
    {
        $name = $metadata['original_name'] ?? fake()->randomElement([
            'project-brief.pdf',
            'launch-notes.txt',
            'design-review.png',
        ]);

        return $this->state(fn (array $attributes) => [
            'type' => Message::TypeFile,
            'body' => $name,
            'metadata' => array_merge([
                'disk' => 'local',
                'path' => 'chat-attachments/'.fake()->uuid().'/'.$name,
                'original_name' => $name,
                'mime_type' => 'application/octet-stream',
                'size' => fake()->numberBetween(24_000, 2_400_000),
            ], $metadata),
        ]);
    }
}
