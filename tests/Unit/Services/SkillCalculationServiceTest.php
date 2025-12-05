<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SkillCalculationService;
use App\Services\SteamApiService;
use Mockery;

/**
 * Unit tests for SkillCalculationService
 *
 * Tests the skill calculation formulas implemented in Phase 2:
 * - K/D normalization curve
 * - CS2 enhanced formula with 5 weighted metrics
 * - Fallback formula using playtime + achievements
 * - Score to level conversion thresholds
 *
 * These tests do NOT use RefreshDatabase as they test pure calculation
 * methods that don't require database access.
 *
 * @see docs/auto-skill-calculation/02-skill-formulas.md
 */
class SkillCalculationServiceTest extends TestCase
{
    protected SkillCalculationService $service;
    protected $steamApiServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->steamApiServiceMock = Mockery::mock(SteamApiService::class);
        $this->service = new SkillCalculationService($this->steamApiServiceMock);
    }

    /*
    |--------------------------------------------------------------------------
    | K/D Ratio Normalization Tests
    |--------------------------------------------------------------------------
    |
    | Tests the non-linear K/D normalization curve from Phase 2 spec:
    | - 0.5 -> 25 points
    | - 1.0 -> 50 points
    | - 1.5 -> 67.5 points
    | - 2.0 -> 85 points
    | - 3.0+ -> 100 points (capped)
    |
    */

    /** @test */
    public function normalize_kd_returns_25_for_half_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 0.5);

        $this->assertEquals(25, $result);
    }

    /** @test */
    public function normalize_kd_returns_50_for_1_0_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 1.0);

        $this->assertEquals(50, $result);
    }

    /** @test */
    public function normalize_kd_returns_67_5_for_1_5_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 1.5);

        $this->assertEquals(67.5, $result);
    }

    /** @test */
    public function normalize_kd_returns_85_for_2_0_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 2.0);

        $this->assertEquals(85, $result);
    }

    /** @test */
    public function normalize_kd_returns_100_for_3_0_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 3.0);

        $this->assertEquals(100, $result);
    }

    /** @test */
    public function normalize_kd_caps_at_100_for_high_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        // Test various high K/D values
        $this->assertEquals(100, $method->invoke($this->service, 5.0));
        $this->assertEquals(100, $method->invoke($this->service, 10.0));
        $this->assertEquals(100, $method->invoke($this->service, 100.0));
    }

    /** @test */
    public function normalize_kd_returns_0_for_0_kd(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 0);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function normalize_kd_interpolates_correctly_between_thresholds(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeKD');
        $method->setAccessible(true);

        // Test K/D = 0.25 (should be 12.5)
        $this->assertEquals(12.5, $method->invoke($this->service, 0.25));

        // Test K/D = 0.75 (should be 37.5)
        $this->assertEquals(37.5, $method->invoke($this->service, 0.75));

        // Test K/D = 1.25 (should be 58.75)
        $this->assertEquals(58.75, $method->invoke($this->service, 1.25));

        // Test K/D = 2.5 (should be 92.5)
        $this->assertEquals(92.5, $method->invoke($this->service, 2.5));
    }

    /*
    |--------------------------------------------------------------------------
    | Score to Level Conversion Tests
    |--------------------------------------------------------------------------
    |
    | Tests the score-to-skill-level conversion thresholds:
    | - 80-100: expert
    | - 60-79: advanced
    | - 40-59: intermediate
    | - 0-39: beginner
    |
    */

    /** @test */
    public function convert_score_to_level_returns_expert_for_80_plus(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertScoreToLevel');
        $method->setAccessible(true);

        $this->assertEquals('expert', $method->invoke($this->service, 80));
        $this->assertEquals('expert', $method->invoke($this->service, 90));
        $this->assertEquals('expert', $method->invoke($this->service, 100));
    }

    /** @test */
    public function convert_score_to_level_returns_advanced_for_60_to_79(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertScoreToLevel');
        $method->setAccessible(true);

        $this->assertEquals('advanced', $method->invoke($this->service, 60));
        $this->assertEquals('advanced', $method->invoke($this->service, 70));
        $this->assertEquals('advanced', $method->invoke($this->service, 79));
        $this->assertEquals('advanced', $method->invoke($this->service, 79.9));
    }

    /** @test */
    public function convert_score_to_level_returns_intermediate_for_40_to_59(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertScoreToLevel');
        $method->setAccessible(true);

        $this->assertEquals('intermediate', $method->invoke($this->service, 40));
        $this->assertEquals('intermediate', $method->invoke($this->service, 50));
        $this->assertEquals('intermediate', $method->invoke($this->service, 59));
        $this->assertEquals('intermediate', $method->invoke($this->service, 59.9));
    }

    /** @test */
    public function convert_score_to_level_returns_beginner_for_below_40(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertScoreToLevel');
        $method->setAccessible(true);

        $this->assertEquals('beginner', $method->invoke($this->service, 39));
        $this->assertEquals('beginner', $method->invoke($this->service, 20));
        $this->assertEquals('beginner', $method->invoke($this->service, 10));
        $this->assertEquals('beginner', $method->invoke($this->service, 0));
    }

    /** @test */
    public function convert_score_to_level_handles_edge_cases(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertScoreToLevel');
        $method->setAccessible(true);

        // Exact boundary tests
        $this->assertEquals('beginner', $method->invoke($this->service, 39.99));
        $this->assertEquals('intermediate', $method->invoke($this->service, 40.0));
        $this->assertEquals('intermediate', $method->invoke($this->service, 59.99));
        $this->assertEquals('advanced', $method->invoke($this->service, 60.0));
        $this->assertEquals('advanced', $method->invoke($this->service, 79.99));
        $this->assertEquals('expert', $method->invoke($this->service, 80.0));
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback Formula Tests
    |--------------------------------------------------------------------------
    |
    | Tests the fallback formula for games without detailed stats:
    | - Playtime Score: min(hours / 1000 * 60, 60) -> max 60 points
    | - Achievement Score: percentage * 0.4 -> max 40 points
    | - Total: Playtime + Achievement (max 100)
    |
    */

    /** @test */
    public function calculate_fallback_skill_with_phase_2_example(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        // Test from Phase 2 specification example:
        // 200 hours, 35% achievements
        // Playtime: min(200/1000 * 60, 60) = 12
        // Achievements: 35 * 0.4 = 14
        // Total: 26 -> beginner
        $result = $method->invoke($this->service, 200, 35);

        $this->assertEquals(26.0, $result['skill_score']);
        $this->assertEquals('beginner', $result['skill_level']);
        $this->assertEquals(12.0, $result['breakdown']['playtime_score']);
        $this->assertEquals(14.0, $result['breakdown']['achievement_score']);
    }

    /** @test */
    public function calculate_fallback_skill_caps_playtime_at_60(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        // 2000 hours should still cap at 60 points
        $result = $method->invoke($this->service, 2000, 0);

        $this->assertEquals(60.0, $result['skill_score']);
        $this->assertEquals(60.0, $result['breakdown']['playtime_score']);
    }

    /** @test */
    public function calculate_fallback_skill_caps_achievements_at_40(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        // 100% achievements = 40 points
        $result = $method->invoke($this->service, 0, 100);

        $this->assertEquals(40.0, $result['skill_score']);
        $this->assertEquals(40.0, $result['breakdown']['achievement_score']);
    }

    /** @test */
    public function calculate_fallback_skill_max_score_is_100(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        // Max playtime (60) + max achievements (40) = 100
        $result = $method->invoke($this->service, 1000, 100);

        $this->assertEquals(100.0, $result['skill_score']);
        $this->assertEquals('expert', $result['skill_level']);
    }

    /** @test */
    public function calculate_fallback_skill_returns_beginner_for_minimal_data(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        // 10 hours, 5% achievements
        // Playtime: 10/1000 * 60 = 0.6
        // Achievements: 5 * 0.4 = 2
        // Total: 2.6 -> beginner
        $result = $method->invoke($this->service, 10, 5);

        $this->assertLessThan(20, $result['skill_score']);
        $this->assertEquals('beginner', $result['skill_level']);
    }

    /** @test */
    public function calculate_fallback_skill_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateFallbackSkill');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 100, 50);

        $this->assertArrayHasKey('skill_score', $result);
        $this->assertArrayHasKey('skill_level', $result);
        $this->assertArrayHasKey('calculation_method', $result);
        $this->assertArrayHasKey('breakdown', $result);

        $this->assertEquals('fallback', $result['calculation_method']);
        $this->assertArrayHasKey('playtime_hours', $result['breakdown']);
        $this->assertArrayHasKey('playtime_score', $result['breakdown']);
        $this->assertArrayHasKey('achievements', $result['breakdown']);
        $this->assertArrayHasKey('achievement_score', $result['breakdown']);
        $this->assertArrayHasKey('note', $result['breakdown']);
    }

    /*
    |--------------------------------------------------------------------------
    | CS2 Enhanced Formula Tests
    |--------------------------------------------------------------------------
    |
    | Tests the enhanced CS2 formula with 5 weighted metrics:
    | - K/D Ratio: 30%
    | - Accuracy: 20%
    | - Win Rate: 20%
    | - Playtime: 20%
    | - Achievements: 10%
    |
    */

    /** @test */
    public function calculate_cs2_skill_with_phase_2_example(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        // Test from Phase 2 specification example
        $stats = [
            'total_kills' => 15000,
            'total_deaths' => 12000,
            'total_shots_hit' => 80000,
            'total_shots_fired' => 250000,
            'total_wins' => 400,
            'total_rounds_played' => 20000,
        ];
        $playtimeHours = 485;
        $achievementPct = 45;

        $result = $method->invoke($this->service, $stats, $playtimeHours, $achievementPct);

        // Expected calculations from spec:
        // K/D = 1.25 -> score 58.75 * 0.30 = 17.625
        // Accuracy = 32% -> 80 * 0.20 = 16.0
        // Win Rate = 400 / (20000/30) = 60% -> 60 * 0.20 = 12.0
        // Playtime = 485 / 10 = 48.5 * 0.20 = 9.7
        // Achievements = 45 * 0.10 = 4.5
        // Total = 59.825 (rounded to 59.8)

        $this->assertEqualsWithDelta(59.8, $result['skill_score'], 1.0);
        $this->assertEquals('intermediate', $result['skill_level']); // 59.8 is just under 60
    }

    /** @test */
    public function calculate_cs2_skill_verifies_all_weight_contributions(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        // Create stats that give predictable scores
        $stats = [
            'total_kills' => 1000,      // K/D = 1.0 -> 50 score
            'total_deaths' => 1000,
            'total_shots_hit' => 4000,  // Accuracy = 40% -> 100 score
            'total_shots_fired' => 10000,
            'total_wins' => 100,        // Win rate = 100% -> 100 score
            'total_rounds_played' => 3000, // ~100 matches
        ];
        $playtimeHours = 1000; // -> 100 score
        $achievementPct = 100; // -> 100 score

        $result = $method->invoke($this->service, $stats, $playtimeHours, $achievementPct);

        // K/D (50 * 0.30 = 15) + Accuracy (100 * 0.20 = 20) +
        // WinRate (100 * 0.20 = 20) + Playtime (100 * 0.20 = 20) +
        // Achievements (100 * 0.10 = 10) = 85
        $this->assertEqualsWithDelta(85.0, $result['skill_score'], 1.0);
        $this->assertEquals('expert', $result['skill_level']);
    }

    /** @test */
    public function calculate_cs2_skill_handles_zero_stats_safely(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        // All zeros - should not error (division by zero protection)
        $stats = [
            'total_kills' => 0,
            'total_deaths' => 0, // Should use 1 as minimum
            'total_shots_hit' => 0,
            'total_shots_fired' => 0, // Should use 1 as minimum
            'total_wins' => 0,
            'total_rounds_played' => 0, // Should use 1 as minimum
        ];

        $result = $method->invoke($this->service, $stats, 0, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('skill_score', $result);
        $this->assertIsFloat($result['skill_score']);
        $this->assertEquals('beginner', $result['skill_level']);
    }

    /** @test */
    public function calculate_cs2_skill_handles_missing_stat_keys(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        // Empty stats array - should use defaults
        $stats = [];

        $result = $method->invoke($this->service, $stats, 100, 50);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('skill_score', $result);
        $this->assertEquals('beginner', $result['skill_level']);
    }

    /** @test */
    public function calculate_cs2_skill_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        $stats = [
            'total_kills' => 1000,
            'total_deaths' => 800,
            'total_shots_hit' => 3000,
            'total_shots_fired' => 10000,
            'total_wins' => 50,
            'total_rounds_played' => 2000,
        ];

        $result = $method->invoke($this->service, $stats, 200, 30);

        // Verify main structure
        $this->assertArrayHasKey('skill_score', $result);
        $this->assertArrayHasKey('skill_level', $result);
        $this->assertArrayHasKey('calculation_method', $result);
        $this->assertArrayHasKey('breakdown', $result);

        $this->assertEquals('enhanced', $result['calculation_method']);

        // Verify breakdown structure
        $breakdown = $result['breakdown'];
        $this->assertArrayHasKey('kd_ratio', $breakdown);
        $this->assertArrayHasKey('kd_score', $breakdown);
        $this->assertArrayHasKey('accuracy', $breakdown);
        $this->assertArrayHasKey('accuracy_score', $breakdown);
        $this->assertArrayHasKey('win_rate', $breakdown);
        $this->assertArrayHasKey('win_rate_score', $breakdown);
        $this->assertArrayHasKey('playtime_hours', $breakdown);
        $this->assertArrayHasKey('playtime_score', $breakdown);
        $this->assertArrayHasKey('achievements', $breakdown);
        $this->assertArrayHasKey('weights', $breakdown);
    }

    /** @test */
    public function calculate_cs2_skill_weights_sum_to_1_0(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateCS2Skill');
        $method->setAccessible(true);

        $stats = [
            'total_kills' => 1000,
            'total_deaths' => 1000,
            'total_shots_hit' => 4000,
            'total_shots_fired' => 10000,
            'total_wins' => 100,
            'total_rounds_played' => 3000,
        ];

        $result = $method->invoke($this->service, $stats, 500, 50);
        $weights = $result['breakdown']['weights'];

        $totalWeight = $weights['kd'] + $weights['accuracy'] + $weights['win_rate'] +
                       $weights['playtime'] + $weights['achievements'];

        // Use delta assertion due to floating-point precision
        $this->assertEqualsWithDelta(1.0, $totalWeight, 0.0001);
    }

    /*
    |--------------------------------------------------------------------------
    | Unranked Result Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function get_unranked_result_returns_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUnrankedResult');
        $method->setAccessible(true);

        $result = $method->invoke($this->service);

        $this->assertEquals('unranked', $result['skill_level']);
        $this->assertNull($result['skill_score']);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('note', $result['breakdown']);
    }

    /*
    |--------------------------------------------------------------------------
    | Supported Games Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function get_supported_games_returns_three_games(): void
    {
        $games = $this->service->getSupportedGames();

        $this->assertCount(3, $games);
        $this->assertArrayHasKey(730, $games);    // CS2
        $this->assertArrayHasKey(570, $games);    // Dota 2
        $this->assertArrayHasKey(230410, $games); // Warframe
    }

    /** @test */
    public function get_supported_games_returns_correct_names(): void
    {
        $games = $this->service->getSupportedGames();

        $this->assertEquals('CS2', $games[730]);
        $this->assertEquals('Dota 2', $games[570]);
        $this->assertEquals('Warframe', $games[230410]);
    }

    /*
    |--------------------------------------------------------------------------
    | Enhanced Stats Check Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function has_enhanced_stats_returns_true_for_cs2(): void
    {
        $this->assertTrue($this->service->hasEnhancedStats(730));
    }

    /** @test */
    public function has_enhanced_stats_returns_false_for_dota2(): void
    {
        $this->assertFalse($this->service->hasEnhancedStats(570));
    }

    /** @test */
    public function has_enhanced_stats_returns_false_for_warframe(): void
    {
        $this->assertFalse($this->service->hasEnhancedStats(230410));
    }

    /** @test */
    public function has_enhanced_stats_returns_false_for_unknown_game(): void
    {
        $this->assertFalse($this->service->hasEnhancedStats(999999));
    }

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
