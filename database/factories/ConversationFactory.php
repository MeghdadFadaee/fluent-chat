<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by_id' => User::factory(),
            'type' => Conversation::TypeDirect,
            'name' => null,
            'description' => null,
        ];
    }

    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Conversation::TypeDirect,
            'name' => null,
            'description' => null,
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Conversation::TypeGroup,
            'name' => fake()->randomElement([
                'Design Partners',
                'Product Launch',
                'Customer Success',
                'Engineering Standup',
                'Growth Studio',
            ]),
            'description' => fake()->sentence(8),
        ]);
    }
}
