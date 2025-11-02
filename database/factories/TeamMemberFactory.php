<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => \App\Models\Team::factory(),
            'user_id' => \App\Models\User::factory(),
            'role' => 'member',
            'game_role' => null,
            'skill_level' => 'intermediate',
            'individual_skill_score' => 50,
            'status' => 'active',
            'joined_at' => now(),
            'last_active_at' => now(),
        ];
    }
}
