<?php

namespace Tests\Regression;

use Tests\TestCase;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MatchmakingRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function it_fixes_the_76_percent_bug()
    {
        // Reproduce original bug scenario

        // Team A: INTERMEDIATE skill, 2/5 members
        $teamIntermediate = Team::factory()->create([
            'skill_level' => 'intermediate',
            'current_size' => 2,
            'max_size' => 5,
            'game_appid' => '730',
        ]);

        // Team B: EXPERT skill, 2/5 members
        $teamExpert = Team::factory()->create([
            'skill_level' => 'expert',
            'current_size' => 2,
            'max_size' => 5,
            'game_appid' => '730',
        ]);

        // INTERMEDIATE user request
        $request = MatchmakingRequest::factory()->create([
            'skill_level' => 'intermediate',
            'game_appid' => '730',
        ]);

        // Calculate compatibility
        $intermediateScore = $this->service->calculateDetailedCompatibility($teamIntermediate, $request);
        $expertScore = $this->service->calculateDetailedCompatibility($teamExpert, $request);

        // BUG: Old algorithm showed both as 76%
        // FIX: New algorithm should show significant difference

        echo "\n=== BUG FIX VALIDATION ===\n";
        echo "INTERMEDIATE vs INTERMEDIATE: {$intermediateScore['total_score']}%\n";
        echo "INTERMEDIATE vs EXPERT: {$expertScore['total_score']}%\n";
        echo "Difference: " . abs($intermediateScore['total_score'] - $expertScore['total_score']) . "%\n\n";

        // Assertions
        $this->assertGreaterThan(80, $intermediateScore['total_score'],
            "INTERMEDIATE vs INTERMEDIATE should score high (got {$intermediateScore['total_score']}%)");

        $this->assertLessThan(70, $expertScore['total_score'],
            "INTERMEDIATE vs EXPERT should score low (got {$expertScore['total_score']}%)");

        $this->assertGreaterThan(20, abs($intermediateScore['total_score'] - $expertScore['total_score']),
            "Should be at least 20% difference between INTERMEDIATE and EXPERT matches");

        // Skill breakdown should show the difference
        $this->assertGreaterThan(90, $intermediateScore['breakdown']['skill'],
            "INTERMEDIATE vs INTERMEDIATE skill score should be >90%");

        $this->assertLessThan(25, $expertScore['breakdown']['skill'],
            "INTERMEDIATE vs EXPERT skill score should be <25%");
    }

    /** @test */
    public function it_produces_expected_skill_match_percentages()
    {
        $skillLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $results = [];

        $request = MatchmakingRequest::factory()->create([
            'skill_level' => 'intermediate',
        ]);

        foreach ($skillLevels as $teamSkill) {
            $team = Team::factory()->create(['skill_level' => $teamSkill]);
            $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

            $results[$teamSkill] = $compatibility['breakdown']['skill'];
        }

        echo "\n=== SKILL MATCH MATRIX (INTERMEDIATE User) ===\n";
        foreach ($results as $level => $score) {
            echo strtoupper($level) . ": {$score}%\n";
        }
        echo "\n";

        // Expected results from Phase 1 specification
        $this->assertEqualsWithDelta(100, $results['intermediate'], 1,
            "INTERMEDIATE vs INTERMEDIATE should be ~100%");

        $this->assertEqualsWithDelta(66.7, $results['advanced'], 1,
            "INTERMEDIATE vs ADVANCED should be ~67%");

        $this->assertEqualsWithDelta(66.7, $results['beginner'], 1,
            "INTERMEDIATE vs BEGINNER should be ~67%");

        $this->assertEqualsWithDelta(16.7, $results['expert'], 1,
            "INTERMEDIATE vs EXPERT should be ~17%");
    }

    /** @test */
    public function it_maintains_backward_compatibility_for_api_responses()
    {
        $team = Team::factory()->create();
        $request = MatchmakingRequest::factory()->create();

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Verify response structure hasn't changed
        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('total_score', $compatibility);
        $this->assertArrayHasKey('reasons', $compatibility);
        $this->assertArrayHasKey('breakdown', $compatibility);

        // Verify total_score is numeric and in valid range
        $this->assertIsNumeric($compatibility['total_score']);
        $this->assertGreaterThanOrEqual(0, $compatibility['total_score']);
        $this->assertLessThanOrEqual(100, $compatibility['total_score']);

        // Verify reasons is array
        $this->assertIsArray($compatibility['reasons']);

        // Verify breakdown contains all expected criteria (5 total - SIZE removed)
        $expectedCriteria = ['skill', 'composition', 'region', 'schedule', 'language'];
        foreach ($expectedCriteria as $criterion) {
            $this->assertArrayHasKey($criterion, $compatibility['breakdown']);
        }

        // Verify SIZE is NOT in breakdown (removed criterion)
        $this->assertArrayNotHasKey('size', $compatibility['breakdown'],
            'SIZE criterion should be removed from breakdown');
    }

    /** @test */
    public function it_ensures_distinct_scores_for_different_skill_levels()
    {
        $skillLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $scores = [];

        $request = MatchmakingRequest::factory()->create([
            'skill_level' => 'intermediate',
        ]);

        // Calculate scores for all skill levels
        foreach ($skillLevels as $teamSkill) {
            $team = Team::factory()->create(['skill_level' => $teamSkill]);
            $compatibility = $this->service->calculateDetailedCompatibility($team, $request);
            $scores[$teamSkill] = $compatibility['breakdown']['skill'];
        }

        // Verify that beginner and advanced (both 1 level away) have the same score
        $this->assertEquals($scores['beginner'], $scores['advanced'],
            "Beginner and Advanced should have same distance from Intermediate");

        // Verify perfect match for same skill level
        $this->assertEquals(100, $scores['intermediate'],
            "Same skill level should be 100%");

        // Verify 2+ level gap has significant penalty
        $this->assertLessThan(25, $scores['expert'],
            "Expert (2 levels away) should score very low");

        // Verify distinct scores exist (intermediate, 1-level-away, 2-levels-away)
        $this->assertNotEquals($scores['intermediate'], $scores['advanced'],
            "Same level should differ from 1-level gap");

        $this->assertNotEquals($scores['advanced'], $scores['expert'],
            "1-level gap should differ from 2-level gap");
    }

    /** @test */
    public function it_produces_symmetric_skill_matching()
    {
        // Test that skill distance is symmetric (A->B = B->A)
        $teamIntermediate = Team::factory()->create(['skill_level' => 'intermediate']);
        $teamExpert = Team::factory()->create(['skill_level' => 'expert']);

        $requestIntermediate = MatchmakingRequest::factory()->create(['skill_level' => 'intermediate']);
        $requestExpert = MatchmakingRequest::factory()->create(['skill_level' => 'expert']);

        // INTERMEDIATE user to EXPERT team
        $score1 = $this->service->calculateDetailedCompatibility($teamExpert, $requestIntermediate);

        // EXPERT user to INTERMEDIATE team
        $score2 = $this->service->calculateDetailedCompatibility($teamIntermediate, $requestExpert);

        // Skill scores should be equal (symmetric distance)
        $this->assertEquals($score1['breakdown']['skill'], $score2['breakdown']['skill'],
            "Skill matching should be symmetric: INT->EXP = EXP->INT");
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 5: Unranked Player Compatibility Tests
    |--------------------------------------------------------------------------
    |
    | Tests for automatic skill calculation (Phase 3-5):
    | - Unranked players get 50% neutral compatibility
    | - 'any' and 'unranked' are treated as intermediate in numeric conversion
    | - Ranked players maintain normal compatibility calculations
    |
    */

    /** @test */
    public function unranked_players_get_neutral_50_percent_compatibility()
    {
        // Create unranked player request
        $unrankedRequest = MatchmakingRequest::factory()->create([
            'skill_level' => 'unranked',
            'game_appid' => '730',
        ]);

        // Create expert team
        $expertTeam = Team::factory()->create([
            'skill_level' => 'expert',
            'game_appid' => '730',
            'current_size' => 2,
            'max_size' => 5,
        ]);

        // Create beginner team
        $beginnerTeam = Team::factory()->create([
            'skill_level' => 'beginner',
            'game_appid' => '730',
            'current_size' => 2,
            'max_size' => 5,
        ]);

        // Calculate compatibility - unranked should get 50% for skill component
        $expertCompatibility = $this->service->calculateDetailedCompatibility($expertTeam, $unrankedRequest);
        $beginnerCompatibility = $this->service->calculateDetailedCompatibility($beginnerTeam, $unrankedRequest);

        echo "\n=== UNRANKED PLAYER COMPATIBILITY ===\n";
        echo "Unranked vs Expert Team - Skill: {$expertCompatibility['breakdown']['skill']}%\n";
        echo "Unranked vs Beginner Team - Skill: {$beginnerCompatibility['breakdown']['skill']}%\n";

        // Unranked players should get exactly 50% skill compatibility with ALL teams
        $this->assertEquals(50.0, $expertCompatibility['breakdown']['skill'],
            "Unranked player should get 50% skill compatibility with Expert team");

        $this->assertEquals(50.0, $beginnerCompatibility['breakdown']['skill'],
            "Unranked player should get 50% skill compatibility with Beginner team");
    }

    /** @test */
    public function ranked_players_still_use_normal_skill_calculation()
    {
        // Create intermediate player request (ranked)
        $rankedRequest = MatchmakingRequest::factory()->create([
            'skill_level' => 'intermediate',
            'game_appid' => '730',
        ]);

        // Create intermediate team (same skill)
        $sameSkillTeam = Team::factory()->create([
            'skill_level' => 'intermediate',
            'game_appid' => '730',
            'current_size' => 2,
            'max_size' => 5,
        ]);

        // Create expert team (different skill)
        $differentSkillTeam = Team::factory()->create([
            'skill_level' => 'expert',
            'game_appid' => '730',
            'current_size' => 2,
            'max_size' => 5,
        ]);

        $sameSkillCompatibility = $this->service->calculateDetailedCompatibility($sameSkillTeam, $rankedRequest);
        $differentSkillCompatibility = $this->service->calculateDetailedCompatibility($differentSkillTeam, $rankedRequest);

        echo "\n=== RANKED PLAYER COMPATIBILITY ===\n";
        echo "Intermediate vs Intermediate - Skill: {$sameSkillCompatibility['breakdown']['skill']}%\n";
        echo "Intermediate vs Expert - Skill: {$differentSkillCompatibility['breakdown']['skill']}%\n";

        // Same skill should be high (100%)
        $this->assertGreaterThan(90, $sameSkillCompatibility['breakdown']['skill'],
            "Same skill level should have >90% compatibility");

        // Different skill should be low (with 2 level gap penalty)
        $this->assertLessThan(25, $differentSkillCompatibility['breakdown']['skill'],
            "2+ level skill gap should have <25% compatibility");

        // Ranked players should NOT get neutral 50%
        $this->assertNotEquals(50.0, $sameSkillCompatibility['breakdown']['skill'],
            "Ranked players should NOT get neutral 50%");
    }

    /** @test */
    public function unranked_numeric_conversion_returns_intermediate_value()
    {
        // Test the convertSkillLevelToNumeric method handles 'unranked' and 'any'
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('convertSkillLevelToNumeric');
        $method->setAccessible(true);

        // 'unranked' should map to 2 (intermediate)
        $unrankedValue = $method->invoke($this->service, 'unranked');
        $this->assertEquals(2, $unrankedValue, "'unranked' should convert to 2 (intermediate)");

        // 'any' should map to 2 (intermediate)
        $anyValue = $method->invoke($this->service, 'any');
        $this->assertEquals(2, $anyValue, "'any' should convert to 2 (intermediate)");

        // Standard levels should still work
        $this->assertEquals(1, $method->invoke($this->service, 'beginner'));
        $this->assertEquals(2, $method->invoke($this->service, 'intermediate'));
        $this->assertEquals(3, $method->invoke($this->service, 'advanced'));
        $this->assertEquals(4, $method->invoke($this->service, 'expert'));
    }
}
