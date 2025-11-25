<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameLobby>
 */
class GameLobbyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $joinMethods = ['steam_lobby', 'lobby_code', 'server_address', 'steam_connect'];
        $joinMethod = $this->faker->randomElement($joinMethods);

        $data = [
            'user_id' => User::factory(),
            'game_id' => $this->faker->numberBetween(730, 570), // CS2 or Dota 2 app IDs
            'join_method' => $joinMethod,
            'is_active' => true,
            'expires_at' => $this->faker->boolean(70) ? now()->addHours($this->faker->numberBetween(1, 24)) : null,
        ];

        // Add method-specific fields
        switch ($joinMethod) {
            case 'steam_lobby':
                $data['steam_app_id'] = $data['game_id'];
                $data['steam_lobby_id'] = $this->faker->numerify('####################');
                $data['steam_profile_id'] = $this->faker->numerify('#################');
                break;

            case 'lobby_code':
                $data['lobby_code'] = $this->faker->regexify('[A-Z0-9]{6}');
                break;

            case 'server_address':
                $data['server_ip'] = $this->faker->ipv4();
                $data['server_port'] = $this->faker->numberBetween(25000, 28000);
                break;

            case 'steam_connect':
                $data['server_ip'] = $this->faker->ipv4();
                $data['server_port'] = $this->faker->numberBetween(25000, 28000);
                break;
        }

        return $data;
    }

    /**
     * Indicate that the lobby is inactive/expired
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the lobby is persistent (no expiration)
     */
    public function persistent(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }
}
