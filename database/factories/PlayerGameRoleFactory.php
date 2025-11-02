<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerGameRole>
 */
class PlayerGameRoleFactory extends Factory
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
            'primary_role' => 'support',
            'secondary_role' => null,
            'experience_level' => 'intermediate',
            'overall_skill_rating' => 50,
            'preferred_roles' => ['support'],
        ];
    }
}
