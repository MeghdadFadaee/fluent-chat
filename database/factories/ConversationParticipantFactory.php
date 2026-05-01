<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
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
            'role' => ConversationParticipant::RoleMember,
            'joined_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'last_read_at' => fake()->optional(0.85)->dateTimeBetween('-2 weeks', 'now'),
            'muted_until' => null,
            'pinned_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ConversationParticipant::RoleAdmin,
        ]);
    }
}
