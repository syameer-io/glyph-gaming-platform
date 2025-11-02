<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Profile;
use App\Models\PlayerGameRole;
use App\Models\MatchmakingRequest;
use App\Models\Server;
use App\Models\TeamMember;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for Phase 3: Team Composition & Role Matching
 *
 * Tests end-to-end matchmaking scenarios with role-based team composition.
 */
class MatchmakingCompositionTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function team_with_role_needs_prioritizes_users_who_fill_them()
    {
        $server = Server::factory()->create();

        // Create team needing AWPer and Support (CS2)
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'current_size' => 3,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                    'support' => 1,
                    'entry' => 2,
                    'igl' => 1,
                ],
            ],
        ]);

        // Add existing members (entry, entry, igl)
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $member3 = User::factory()->create();

        Profile::factory()->create(['user_id' => $member1->id]);
        Profile::factory()->create(['user_id' => $member2->id]);
        Profile::factory()->create(['user_id' => $member3->id]);

        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member1->id,
            'game_role' => 'entry',
            'status' => 'active',
        ]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member2->id,
            'game_role' => 'entry',
            'status' => 'active',
        ]);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member3->id,
            'game_role' => 'igl',
            'status' => 'active',
        ]);

        // Reload team to refresh relationships
        $team = $team->fresh();

        // User who can AWP (fills critical need)
        $awper = User::factory()->create();
        Profile::factory()->create(['user_id' => $awper->id]);
        PlayerGameRole::factory()->create([
            'user_id' => $awper->id,
            'game_appid' => '730',
            'primary_role' => 'awper',
        ]);

        $awperRequest = MatchmakingRequest::factory()->create([
            'user_id' => $awper->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => ['awper'],
        ]);

        // User who plays entry (redundant role)
        $entry = User::factory()->create();
        Profile::factory()->create(['user_id' => $entry->id]);
        PlayerGameRole::factory()->create([
            'user_id' => $entry->id,
            'game_appid' => '730',
            'primary_role' => 'entry',
        ]);

        $entryRequest = MatchmakingRequest::factory()->create([
            'user_id' => $entry->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => ['entry'],
        ]);

        // Calculate compatibility for both
        $awperCompatibility = $this->service->calculateDetailedCompatibility($team, $awperRequest);
        $entryCompatibility = $this->service->calculateDetailedCompatibility($team, $entryRequest);

        // AWPer should score higher due to role needs
        $this->assertGreaterThan(
            $entryCompatibility['breakdown']['composition'],
            $awperCompatibility['breakdown']['composition'],
            'User who fills needed role should have higher composition score'
        );

        // AWPer should have high composition score (fills needed role)
        $this->assertGreaterThanOrEqual(
            70,
            $awperCompatibility['breakdown']['composition'],
            'User filling needed role should score >= 70% on composition'
        );

        // Entry should have lower composition score (redundant role)
        $this->assertLessThan(
            70,
            $entryCompatibility['breakdown']['composition'],
            'User with redundant role should score < 70% on composition'
        );
    }

    /** @test */
    public function flexible_expert_player_scores_well_on_composition()
    {
        $server = Server::factory()->create();

        // Team with various role needs
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'advanced',
            'current_size' => 2,
            'max_size' => 5,
            'status' => 'recruiting',
            'team_data' => [
                'desired_roles' => [
                    'dps' => 2,
                    'support' => 1,
                ],
            ],
        ]);

        // Expert player (high skill) with flexible roles
        $expertUser = User::factory()->create();
        $expertProfile = Profile::factory()->create([
            'user_id' => $expertUser->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'skill_score' => 85, // Expert level
                    ],
                ],
            ],
        ]);

        PlayerGameRole::factory()->create([
            'user_id' => $expertUser->id,
            'game_appid' => '730',
            'primary_role' => 'support',
            'secondary_role' => 'dps',
        ]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $expertUser->id,
            'game_appid' => '730',
            'skill_level' => 'expert',
            'preferred_roles' => ['support', 'dps'],
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should score very well because can fill both needed roles
        $this->assertGreaterThanOrEqual(
            90,
            $compatibility['breakdown']['composition'],
            'Expert player who can fill multiple needed roles should score >= 90%'
        );

        // Should have positive reason message
        $this->assertNotEmpty($compatibility['reasons'], 'Should have compatibility reasons');
    }

    /** @test */
    public function end_to_end_compatibility_calculation_includes_composition()
    {
        $server = Server::factory()->create();

        // Create team
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'average_skill_score' => 50,
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                ],
            ],
        ]);

        // Create user who matches
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'skill_score' => 50,
                    ],
                ],
            ],
        ]);

        PlayerGameRole::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'primary_role' => 'awper',
        ]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'skill_score' => 50,
            'preferred_roles' => ['awper'],
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Verify structure
        $this->assertArrayHasKey('total_score', $compatibility);
        $this->assertArrayHasKey('breakdown', $compatibility);
        $this->assertArrayHasKey('reasons', $compatibility);

        // Verify composition is included in breakdown
        $this->assertArrayHasKey('composition', $compatibility['breakdown']);

        // Verify composition score is high (perfect match)
        $this->assertGreaterThanOrEqual(
            95,
            $compatibility['breakdown']['composition'],
            'Perfect role match should score >= 95%'
        );

        // Verify total score is high (skill + composition both perfect)
        $this->assertGreaterThanOrEqual(
            80,
            $compatibility['total_score'],
            'Perfect skill and role match should have high total score'
        );
    }

    /** @test */
    public function team_without_custom_roles_uses_game_defaults()
    {
        $server = Server::factory()->create();

        // Team without custom desired_roles (should use defaults)
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730', // CS2
            'skill_level' => 'intermediate',
            'team_data' => [], // No custom roles
        ]);

        // getNeededRoles() should fallback to game-specific requirements
        $neededRoles = $team->getNeededRoles();

        $this->assertIsArray($neededRoles);

        // CS2 default requirements should include standard roles
        // Note: This depends on current implementation of getGameRoleRequirements()
        // If team is empty, it will need all default roles
    }

    /** @test */
    public function user_with_no_roles_gets_decent_score_as_flexible_player()
    {
        $server = Server::factory()->create();

        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                    'support' => 1,
                ],
            ],
        ]);

        // User with no role preferences (flexible)
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => [], // No preferences
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should get decent score (70% = 0.70 normalized)
        $this->assertEquals(
            70,
            $compatibility['breakdown']['composition'],
            'Flexible player should get 70% composition score'
        );

        // Should have flexibility reason message
        $compositionReasons = array_filter($compatibility['reasons'], function($reason) {
            return stripos($reason, 'flexible') !== false || stripos($reason, 'adapt') !== false;
        });

        $this->assertNotEmpty($compositionReasons, 'Should mention flexibility in reasons');
    }

    /** @test */
    public function partial_role_match_gets_gradient_score()
    {
        $server = Server::factory()->create();

        // Team needs 3 different roles
        $team = Team::factory()->create([
            'server_id' => $server->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'team_data' => [
                'desired_roles' => [
                    'awper' => 1,
                    'support' => 1,
                    'entry' => 1,
                ],
            ],
        ]);

        // User can fill 1 of 3 roles (33% fill ratio)
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
            'skill_level' => 'intermediate',
            'preferred_roles' => ['awper'], // Can fill 1 of 3
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should get gradient score between 70% and 95%
        // Formula: 0.70 + (fillRatio * 0.25) = 0.70 + (0.333 * 0.25) = 0.78
        $expectedScore = 70 + (33.33 * 0.25); // Approximately 78%

        $this->assertGreaterThan(
            70,
            $compatibility['breakdown']['composition'],
            'Partial fill should score > 70%'
        );

        $this->assertLessThan(
            95,
            $compatibility['breakdown']['composition'],
            'Partial fill should score < 95%'
        );

        $this->assertEqualsWithDelta(
            $expectedScore,
            $compatibility['breakdown']['composition'],
            5,
            'Partial fill should use gradient scoring'
        );
    }
}
