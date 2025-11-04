<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Server;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MatchmakingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function complete_matchmaking_flow_finds_best_teams()
    {
        // Create a server
        $server = Server::factory()->create();

        // Create 5 teams with different characteristics
        $perfectTeam = Team::factory()->create([
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

        $wrongSkillTeam = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'expert', // Wrong skill
            'current_size' => 3,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['awper' => 1],
                'preferred_region' => 'NA',
                'activity_time' => ['evening'],
                'languages' => ['en'],
            ],
        ]);

        $wrongRegionTeam = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'current_size' => 3,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => ['awper' => 1],
                'preferred_region' => 'ASIA', // Wrong region
                'activity_time' => ['evening'],
                'languages' => ['en'],
            ],
        ]);

        // Create matchmaking request
        $user = User::factory()->create();
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => ['awper'],
            'server_preferences' => [$server->id],
            'availability_hours' => ['evening'],
            'additional_requirements' => [
                'preferred_region' => 'NA',
                'languages' => ['en'],
            ],
            'status' => 'active',
        ]);

        // Find compatible teams
        $compatibleTeams = $this->service->findCompatibleTeams($request);

        // Should find teams above 50% threshold
        $this->assertGreaterThan(0, $compatibleTeams->count());

        // Verify top team has high compatibility score
        $topTeam = $compatibleTeams->first();
        $this->assertGreaterThan(75, $topTeam->compatibility_score);

        // Calculate perfect team score - should be in top 3
        $perfectTeamScore = $this->service->calculateDetailedCompatibility($perfectTeam, $request);
        $this->assertGreaterThan(80, $perfectTeamScore['total_score'],
            "Perfect match team should score >80%");

        // Wrong skill team should score lower due to skill mismatch (even though other factors match)
        $wrongSkillScore = $this->service->calculateDetailedCompatibility($wrongSkillTeam, $request);
        $this->assertLessThan($perfectTeamScore['total_score'], $wrongSkillScore['total_score'],
            "Wrong skill team should score lower than perfect match");

        // Expert vs Intermediate has low skill match but other factors can push it above 60%
        // The key is it scores lower than the perfect match
        $this->assertLessThan(75, $wrongSkillScore['total_score']);
    }

    /** @test */
    public function it_correctly_orders_teams_by_compatibility()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();

        // Create teams with incrementally worse matches
        $teams = [
            Team::factory()->create(['skill_level' => 'intermediate', 'server_id' => $server->id, 'status' => 'recruiting', 'game_appid' => '730']),
            Team::factory()->create(['skill_level' => 'advanced', 'server_id' => $server->id, 'status' => 'recruiting', 'game_appid' => '730']),
            Team::factory()->create(['skill_level' => 'expert', 'server_id' => $server->id, 'status' => 'recruiting', 'game_appid' => '730']),
        ];

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'skill_level' => 'intermediate',
            'server_preferences' => [$server->id],
            'game_appid' => '730',
        ]);

        $compatibleTeams = $this->service->findCompatibleTeams($request);

        // Should be ordered by compatibility (highest first)
        $scores = $compatibleTeams->pluck('compatibility_score')->toArray();

        for ($i = 0; $i < count($scores) - 1; $i++) {
            $this->assertGreaterThanOrEqual($scores[$i + 1], $scores[$i],
                "Teams not ordered by compatibility");
        }
    }

    /** @test */
    public function it_filters_out_teams_below_threshold()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();

        // Create team with very poor match
        $poorMatchTeam = Team::factory()->create([
            'server_id' => $server->id,
            'skill_level' => 'beginner',
            'current_size' => 5,
            'max_size' => 5, // Full team
            'status' => 'recruiting',
            'game_appid' => '730',
            'team_data' => [
                'preferred_region' => 'ASIA',
                'activity_time' => ['morning'],
            ],
        ]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'skill_level' => 'expert',
            'game_appid' => '730',
            'availability_hours' => ['night'],
            'additional_requirements' => [
                'preferred_region' => 'NA',
            ],
        ]);

        $compatibleTeams = $this->service->findCompatibleTeams($request);

        // Poor match should be filtered out (<50% threshold)
        $teamIds = $compatibleTeams->pluck('id')->toArray();
        $this->assertNotContains($poorMatchTeam->id, $teamIds);
    }

    /** @test */
    public function it_limits_results_to_top_10_teams()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();

        // Create 15 recruiting teams
        Team::factory()->count(15)->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'status' => 'recruiting',
        ]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
        ]);

        $compatibleTeams = $this->service->findCompatibleTeams($request);

        // Should return max 10 teams
        $this->assertLessThanOrEqual(10, $compatibleTeams->count());
    }
}
