<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MatchmakingRequest>
 */
class MatchmakingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'request_type' => 'find_team',
            'status' => 'active',
            'skill_level' => 'intermediate',
            'skill_score' => 50,
            'preferred_roles' => [],
            'expires_at' => now()->addHours(24),
        ];
    }
}
