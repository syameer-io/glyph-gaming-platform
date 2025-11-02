<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MatchmakingService;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Models\User;
use App\Models\Profile;
use App\Models\PlayerGameRole;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for Phase 3: Team Composition & Role Matching
 *
 * Tests Jaccard similarity, flexible role matching, and gradual scoring.
 */
class MatchmakingServiceRoleTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function it_calculates_jaccard_similarity_correctly()
    {
        // Perfect overlap (100%)
        $score1 = $this->invokePrivateMethod($this->service, 'calculateJaccardSimilarity', [
            ['a', 'b'],
            ['a', 'b']
        ]);
        $this->assertEquals(1.0, $score1, 'Perfect overlap should return 1.0');

        // No overlap (0%)
        $score2 = $this->invokePrivateMethod($this->service, 'calculateJaccardSimilarity', [
            ['a', 'b'],
            ['c', 'd']
        ]);
        $this->assertEquals(0.0, $score2, 'No overlap should return 0.0');

        // Partial overlap (1/3 = 0.333...)
        // Sets: {a, b} and {b, c}
        // Intersection: {b} = 1
        // Union: {a, b, c} = 3
        // Jaccard: 1/3 = 0.333
        $score3 = $this->invokePrivateMethod($this->service, 'calculateJaccardSimilarity', [
            ['a', 'b'],
            ['b', 'c']
        ]);
        $this->assertEqualsWithDelta(0.333, $score3, 0.01, 'Partial overlap should return ~0.333');

        // Both empty sets (edge case)
        $score4 = $this->invokePrivateMethod($this->service, 'calculateJaccardSimilarity', [[], []]);
        $this->assertEquals(1.0, $score4, 'Both empty should return 1.0');

        // One empty set (edge case)
        $score5 = $this->invokePrivateMethod($this->service, 'calculateJaccardSimilarity', [
            ['a'],
            []
        ]);
        $this->assertEquals(0.0, $score5, 'One empty should return 0.0');
    }

    /** @test */
    public function it_gives_perfect_score_when_user_fills_all_needed_roles()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Team needs: awper, support
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                    'support' => 1,
                ],
            ],
        ]);

        // User can play both roles
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'preferred_roles' => ['awper', 'support'],
        ]);

        $score = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request]);

        $this->assertEquals(1.0, $score, 'User who can fill all needed roles should get perfect score');
    }

    /** @test */
    public function it_handles_team_with_no_role_requirements()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Team has no role requirements
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [],
        ]);

        // User has role preferences
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'preferred_roles' => ['dps'],
        ]);

        $score = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request]);

        $this->assertEquals(0.80, $score, 'Team with no requirements should return 0.80');
    }

    /** @test */
    public function it_handles_flexible_player_with_no_preferences()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Team needs specific roles
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [
                'desired_roles' => [
                    'dps' => 1,
                    'support' => 1,
                ],
            ],
        ]);

        // User has no role preferences (flexible)
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'preferred_roles' => [],
        ]);

        $score = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request]);

        $this->assertEquals(0.70, $score, 'Flexible player with no preferences should return 0.70');
    }

    /** @test */
    public function it_gives_low_score_for_no_role_overlap()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Team needs: dps, support
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [
                'desired_roles' => [
                    'dps' => 2,
                    'support' => 1,
                ],
            ],
        ]);

        // User only plays tank (no overlap)
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'preferred_roles' => ['tank'],
        ]);

        $score = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request]);

        $this->assertLessThan(0.50, $score, 'No overlap should give score < 0.50');
        $this->assertGreaterThanOrEqual(0.30, $score, 'Minimum score should be 0.30');
    }

    /** @test */
    public function it_scales_score_based_on_fill_ratio()
    {
        $server = Server::factory()->create();

        // Team needs: dps (2), support (1) = 3 total role slots
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [
                'desired_roles' => [
                    'dps' => 2,
                    'support' => 1,
                ],
            ],
        ]);

        // User 1 can fill 1 of 2 unique roles (50% fill)
        $user1 = User::factory()->create();
        Profile::factory()->create(['user_id' => $user1->id]);
        $request1 = MatchmakingRequest::factory()->create([
            'user_id' => $user1->id,
            'game_appid' => '730',
            'preferred_roles' => ['dps'],
        ]);

        $score1 = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request1]);

        // User 2 can fill 2 of 2 unique roles (100% fill)
        $user2 = User::factory()->create();
        Profile::factory()->create(['user_id' => $user2->id]);
        $request2 = MatchmakingRequest::factory()->create([
            'user_id' => $user2->id,
            'game_appid' => '730',
            'preferred_roles' => ['dps', 'support'],
        ]);

        $score2 = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request2]);

        $this->assertGreaterThan($score1, $score2, 'Full fill should score higher than partial fill');
        $this->assertEquals(1.0, $score2, 'Full fill should return 1.0');
    }

    /** @test */
    public function it_expands_expert_player_roles_for_flexibility()
    {
        $user = User::factory()->create();

        // Create profile with high skill score (expert level)
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'skill_score' => 85, // Expert level
                    ],
                ],
            ],
        ]);

        // User has only one preferred role
        PlayerGameRole::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'primary_role' => 'awper',
            'secondary_role' => null,
        ]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
        ]);

        $flexRoles = $this->invokePrivateMethod($this->service, 'getUserFlexibleRoles', [
            $user,
            '730',
            $request
        ]);

        // Expert with 1 role should get expanded to common CS2 roles
        $this->assertGreaterThan(1, count($flexRoles), 'Expert player should have expanded roles');
        $this->assertContains('awper', $flexRoles, 'Should contain original role');
    }

    /** @test */
    public function it_handles_multi_role_flexible_player()
    {
        $server = Server::factory()->create();
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Team needs: awper, support
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                    'support' => 1,
                ],
            ],
        ]);

        // User has 3+ roles (flexible player)
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'preferred_roles' => ['tank', 'dps', 'healer'], // No direct match but flexible
        ]);

        $score = $this->invokePrivateMethod($this->service, 'calculateRoleMatchForTeam', [$team, $request]);

        $this->assertEquals(0.60, $score, 'Multi-role player with no direct match should get 0.60');
    }

    /** @test */
    public function it_returns_common_roles_for_cs2()
    {
        $commonRoles = $this->invokePrivateMethod($this->service, 'getCommonRolesForGame', ['730']);

        $this->assertIsArray($commonRoles);
        $this->assertNotEmpty($commonRoles);
        $this->assertContains('awper', $commonRoles);
        $this->assertContains('entry', $commonRoles);
        $this->assertContains('support', $commonRoles);
    }

    /** @test */
    public function it_returns_default_flex_for_unknown_game()
    {
        $commonRoles = $this->invokePrivateMethod($this->service, 'getCommonRolesForGame', ['999999']);

        $this->assertEquals(['flex'], $commonRoles);
    }

    /**
     * Helper to invoke private methods for testing
     */
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
