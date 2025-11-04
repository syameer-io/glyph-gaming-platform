<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use App\Models\MatchmakingRequest;

class MatchmakingTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test server
        $server = Server::factory()->create([
            'name' => 'Test Gaming Hub',
        ]);

        // Create diverse teams for testing
        $this->createTestTeams($server);

        // Create test users and requests
        $this->createTestRequests($server);

        echo "\n=== Matchmaking Test Data Seeded ===\n";
        echo "Server: {$server->name} (ID: {$server->id})\n";
        echo "Teams created: 5\n";
        echo "Users created: 3\n";
        echo "Matchmaking requests created: 3\n\n";
    }

    protected function createTestTeams(Server $server): void
    {
        // Perfect match team
        Team::factory()->create([
            'name' => 'Perfect Match Team',
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'current_size' => 3,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['awper' => 1, 'support' => 1],
                'preferred_region' => 'NA',
                'activity_time' => ['evening'],
                'languages' => ['en'],
            ],
        ]);

        // High skill team (expert)
        Team::factory()->create([
            'name' => 'Elite Squad',
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'expert',
            'current_size' => 4,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['igl' => 1],
                'preferred_region' => 'NA',
                'activity_time' => ['evening', 'night'],
                'languages' => ['en'],
            ],
        ]);

        // Low skill team (beginner)
        Team::factory()->create([
            'name' => 'Newbie Friendly',
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'beginner',
            'current_size' => 2,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['dps' => 2, 'support' => 1],
                'preferred_region' => 'NA',
                'activity_time' => ['morning', 'afternoon'],
                'languages' => ['en'],
            ],
        ]);

        // Different region team
        Team::factory()->create([
            'name' => 'ASIA Competitive',
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'current_size' => 3,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['awper' => 1],
                'preferred_region' => 'ASIA',
                'activity_time' => ['morning'], // ASIA morning = NA evening
                'languages' => ['en', 'zh'],
            ],
        ]);

        // Full team (edge case)
        Team::factory()->create([
            'name' => 'Full Roster',
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'current_size' => 5,
            'max_size' => 5,
            'status' => 'full',
        ]);
    }

    protected function createTestRequests(Server $server): void
    {
        // Standard intermediate user
        $user1 = User::factory()->create(['name' => 'Test User - Intermediate']);
        MatchmakingRequest::factory()->create([
            'user_id' => $user1->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => ['awper'],
            'server_preferences' => [$server->id],
            'availability_hours' => ['evening'],
            'additional_requirements' => [
                'preferred_region' => 'NA',
                'languages' => ['en'],
            ],
            'status' => 'pending',
        ]);

        // Expert user
        $user2 = User::factory()->create(['name' => 'Test User - Expert']);
        MatchmakingRequest::factory()->create([
            'user_id' => $user2->id,
            'game_appid' => '730',
            'skill_level' => 'expert',
            'preferred_roles' => ['igl', 'awper'],
            'server_preferences' => [$server->id],
            'availability_hours' => ['evening', 'night'],
            'additional_requirements' => [
                'preferred_region' => 'NA',
                'languages' => ['en'],
            ],
            'status' => 'pending',
        ]);

        // Flexible beginner
        $user3 = User::factory()->create(['name' => 'Test User - Beginner']);
        MatchmakingRequest::factory()->create([
            'user_id' => $user3->id,
            'game_appid' => '730',
            'skill_level' => 'beginner',
            'preferred_roles' => [],
            'server_preferences' => [$server->id],
            'availability_hours' => ['flexible'],
            'additional_requirements' => [
                'preferred_region' => 'NA',
                'languages' => ['en'],
            ],
            'status' => 'pending',
        ]);
    }
}
