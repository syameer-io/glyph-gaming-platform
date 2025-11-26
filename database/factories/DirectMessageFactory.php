<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DirectMessage>
 */
class DirectMessageFactory extends Factory
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
            'sender_id' => User::factory(),
            'content' => fake()->sentence(rand(3, 15)),
            'is_edited' => false,
            'edited_at' => null,
            'read_at' => null,
        ];
    }

    /**
     * Define a message for a specific conversation.
     *
     * @param Conversation $conversation The conversation this message belongs to
     * @param User|null $sender The sender (must be a participant). If null, randomly picks one.
     * @return static
     */
    public function forConversation(Conversation $conversation, ?User $sender = null): static
    {
        // If no sender specified, randomly pick one of the participants
        if ($sender === null) {
            $sender = fake()->randomElement([
                $conversation->userOne,
                $conversation->userTwo,
            ]);
        }

        return $this->state(fn (array $attributes) => [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);
    }

    /**
     * Define a message that has been edited.
     *
     * @return static
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
            'edited_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Define a message that has been read.
     *
     * @return static
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween(
                $attributes['created_at'] ?? '-1 hour',
                'now'
            ),
        ]);
    }

    /**
     * Define an unread message.
     *
     * @return static
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Define a message with longer content.
     *
     * @return static
     */
    public function longContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->paragraphs(rand(2, 4), true),
        ]);
    }

    /**
     * Define a message sent recently.
     *
     * @return static
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
