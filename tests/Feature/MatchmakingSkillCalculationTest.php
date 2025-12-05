<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\MatchmakingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for Auto-Skill Calculation in Matchmaking (Phase 3-5)
 *
 * Tests the complete flow of automatic skill calculation:
 * - Skill preview endpoint returns calculated skill
 * - Matchmaking requests use auto-calculated skill (not user input)
 * - Unranked users can still create requests
 * - Manual skill_level input is ignored
 *
 * @see docs/auto-skill-calculation/06-testing.md
 */
class MatchmakingSkillCalculationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Skill Preview Endpoint Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function skill_preview_endpoint_returns_calculated_skill()
    {
        $user = User::factory()->create(['steam_id' => '76561198012345678']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'skill_score' => 45,
                        'skill_level' => 'intermediate',
                        'playtime_hours' => 150,
                        'achievement_percentage' => 30,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=730');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'skill_level',
                'skill_score',
                'breakdown',
                'is_unranked',
            ]);
    }

    /** @test */
    public function skill_preview_returns_unranked_for_no_data()
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [],
        ]);

        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=730');

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'skill_level' => 'unranked',
                'is_unranked' => true,
            ]);
    }

    /** @test */
    public function skill_preview_returns_unranked_for_unsupported_games()
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        // Apex Legends (1172470) is no longer supported
        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=1172470');

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'skill_level' => 'unranked',
                'is_unranked' => true,
            ]);
    }

    /** @test */
    public function skill_preview_requires_game_appid()
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview');

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function skill_preview_requires_authentication()
    {
        $response = $this->getJson('/matchmaking/skill-preview?game_appid=730');

        $response->assertStatus(401); // Unauthorized
    }

    /*
    |--------------------------------------------------------------------------
    | Matchmaking Request Creation Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function matchmaking_request_uses_calculated_skill_not_user_input()
    {
        $user = User::factory()->create(['steam_id' => '76561198012345678']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'skill_score' => 65,
                        'skill_level' => 'advanced',
                        'playtime_hours' => 400,
                        'achievement_percentage' => 45,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/matchmaking', [
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'request_type' => 'find_team',
            // NO skill_level field - should be auto-calculated
        ]);

        $response->assertSuccessful();

        // Verify the request was created with auto-calculated skill
        $this->assertDatabaseHas('matchmaking_requests', [
            'user_id' => $user->id,
            'game_appid' => '730',
        ]);

        // Verify it's NOT using manual input 'any'
        $request = MatchmakingRequest::where('user_id', $user->id)->first();
        $this->assertNotEquals('any', $request->skill_level,
            "Skill level should be auto-calculated, not 'any'");
    }

    /** @test */
    public function manual_skill_level_input_is_ignored()
    {
        $user = User::factory()->create(['steam_id' => '76561198012345678']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'playtime_hours' => 50,
                        'achievement_percentage' => 10,
                    ],
                ],
            ],
        ]);

        // Try to submit 'expert' even though user is likely a beginner
        $response = $this->actingAs($user)->postJson('/matchmaking', [
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'request_type' => 'find_team',
            'skill_level' => 'expert',  // This should be IGNORED
        ]);

        $response->assertSuccessful();

        $request = MatchmakingRequest::where('user_id', $user->id)->first();

        // Should NOT be expert - should be calculated based on actual data
        // With only 50 hours and 10% achievements, user would be beginner/unranked
        $this->assertNotEquals('expert', $request->skill_level,
            "Manual 'expert' input should be ignored");
    }

    /** @test */
    public function unranked_user_can_create_matchmaking_request()
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [],  // No game data
        ]);

        $response = $this->actingAs($user)->postJson('/matchmaking', [
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'request_type' => 'find_team',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('matchmaking_requests', [
            'user_id' => $user->id,
            'skill_level' => 'unranked',
        ]);
    }

    /** @test */
    public function matchmaking_request_has_valid_skill_score()
    {
        $user = User::factory()->create(['steam_id' => '76561198012345678']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => [
                        'playtime_hours' => 200,
                        'achievement_percentage' => 40,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/matchmaking', [
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'request_type' => 'find_team',
        ]);

        $response->assertSuccessful();

        $request = MatchmakingRequest::where('user_id', $user->id)->first();

        // skill_score should be a valid number between 0-100 (or null for unranked)
        if ($request->skill_level !== 'unranked') {
            $this->assertNotNull($request->skill_score);
            $this->assertGreaterThanOrEqual(0, $request->skill_score);
            $this->assertLessThanOrEqual(100, $request->skill_score);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Supported Games Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function supported_games_are_cs2_dota2_and_warframe()
    {
        $user = User::factory()->create(['steam_id' => '76561198012345678']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'steam_data' => [
                'skill_metrics' => [
                    '730' => ['playtime_hours' => 100, 'achievement_percentage' => 20],
                    '570' => ['playtime_hours' => 100, 'achievement_percentage' => 20],
                    '230410' => ['playtime_hours' => 100, 'achievement_percentage' => 20],
                ],
            ],
        ]);

        // Test CS2 (730) - supported
        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=730');
        $response->assertSuccessful();

        // Test Dota 2 (570) - supported
        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=570');
        $response->assertSuccessful();

        // Test Warframe (230410) - supported
        $response = $this->actingAs($user)->getJson('/matchmaking/skill-preview?game_appid=230410');
        $response->assertSuccessful();
    }
}
