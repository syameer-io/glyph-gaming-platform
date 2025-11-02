<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Team',
            'description' => fake()->sentence(),
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'server_id' => \App\Models\Server::factory(),
            'creator_id' => \App\Models\User::factory(),
            'max_size' => 5,
            'current_size' => 0,
            'skill_level' => 'intermediate',
            'status' => 'recruiting',
            'team_data' => [],
            'average_skill_score' => 50,
        ];
    }
}
