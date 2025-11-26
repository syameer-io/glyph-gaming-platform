<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
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
        // Create two users with ordered IDs for canonical ordering
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        // Ensure canonical ordering (smaller ID first)
        $userOneId = min($userOne->id, $userTwo->id);
        $userTwoId = max($userOne->id, $userTwo->id);

        return [
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
            'last_message_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Define a conversation between two specific users.
     *
     * @param User $userOne First user
     * @param User $userTwo Second user
     * @return static
     */
    public function between(User $userOne, User $userTwo): static
    {
        // Ensure canonical ordering
        $userOneId = min($userOne->id, $userTwo->id);
        $userTwoId = max($userOne->id, $userTwo->id);

        return $this->state(fn (array $attributes) => [
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    /**
     * Define a conversation with recent activity.
     *
     * @return static
     */
    public function withRecentActivity(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Define a conversation with no messages yet.
     *
     * @return static
     */
    public function withoutMessages(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => null,
        ]);
    }
}
