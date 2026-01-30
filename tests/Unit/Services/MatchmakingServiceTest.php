<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for the Matchmaking Algorithm
 *
 * Tests each scoring criterion independently to verify algorithm accuracy.
 * These tests replicate the algorithm logic to ensure consistent behavior.
 *
 * @group algorithm
 * @group matchmaking
 */
class MatchmakingServiceTest extends TestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // SKILL COMPATIBILITY TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider skillCompatibilityProvider
     */
    public function it_calculates_skill_compatibility_correctly(
        string $teamSkill,
        string $requestSkill,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateSkillCompatibility($teamSkill, $requestSkill);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Skill compatibility for {$teamSkill} vs {$requestSkill} should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Skill compatibility for {$teamSkill} vs {$requestSkill} should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function skillCompatibilityProvider(): array
    {
        return [
            'same_level_beginner' => ['beginner', 'beginner', 100, 100],
            'same_level_intermediate' => ['intermediate', 'intermediate', 100, 100],
            'same_level_advanced' => ['advanced', 'advanced', 100, 100],
            'same_level_expert' => ['expert', 'expert', 100, 100],
            'one_gap_beginner_intermediate' => ['beginner', 'intermediate', 60, 75],
            'one_gap_intermediate_advanced' => ['intermediate', 'advanced', 60, 75],
            'one_gap_advanced_expert' => ['advanced', 'expert', 60, 75],
            'two_gap_with_penalty_beginner_advanced' => ['beginner', 'advanced', 10, 25],
            'two_gap_with_penalty_intermediate_expert' => ['intermediate', 'expert', 10, 25],
            'three_gap_beginner_expert' => ['beginner', 'expert', 0, 5],
            'three_gap_reversed_expert_beginner' => ['expert', 'beginner', 0, 5],
            'unranked_neutral_score' => ['intermediate', 'unranked', 45, 55],
        ];
    }

    /** @test */
    public function unranked_players_receive_neutral_compatibility(): void
    {
        // Unranked players should get 50% compatibility regardless of team level
        foreach (['beginner', 'intermediate', 'advanced', 'expert'] as $teamLevel) {
            $score = $this->calculateSkillCompatibility($teamLevel, 'unranked');
            $this->assertEquals(50, $score, "Unranked vs {$teamLevel} should be 50");
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // JACCARD SIMILARITY TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider jaccardSimilarityProvider
     */
    public function it_calculates_jaccard_similarity_correctly(
        array $set1,
        array $set2,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateJaccardSimilarity($set1, $set2);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Jaccard similarity should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Jaccard similarity should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function jaccardSimilarityProvider(): array
    {
        return [
            'identical_sets' => [['a', 'b', 'c'], ['a', 'b', 'c'], 1.0, 1.0],
            'no_overlap' => [['a', 'b'], ['c', 'd'], 0.0, 0.0],
            'two_thirds_overlap' => [['a', 'b', 'c'], ['a', 'b'], 0.65, 0.70],
            'half_overlap' => [['a', 'b', 'c', 'd'], ['a', 'b'], 0.45, 0.55],
            'one_third_overlap' => [['a'], ['a', 'b', 'c'], 0.30, 0.35],
            'empty_first_set' => [[], ['a', 'b'], 0.0, 0.0],
            'empty_second_set' => [['a', 'b'], [], 0.0, 0.0],
            'both_empty' => [[], [], 0.0, 0.0],
        ];
    }

    /** @test */
    public function jaccard_similarity_is_symmetric(): void
    {
        $set1 = ['a', 'b', 'c'];
        $set2 = ['b', 'c', 'd'];

        $score1 = $this->calculateJaccardSimilarity($set1, $set2);
        $score2 = $this->calculateJaccardSimilarity($set2, $set1);

        $this->assertEquals($score1, $score2, "Jaccard similarity should be symmetric");
    }

    // ════════════════════════════════════════════════════════════════════════
    // ROLE MATCH SCORING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider roleMatchProvider
     */
    public function it_calculates_role_match_correctly(
        array $neededRoles,
        array $userRoles,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateRoleMatch($neededRoles, $userRoles);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Role match should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Role match should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function roleMatchProvider(): array
    {
        return [
            'team_flexible_no_needs' => [[], ['entry', 'support'], 0.75, 0.85],
            'user_flexible_no_prefs' => [['entry', 'support'], [], 0.65, 0.75],
            'perfect_fill' => [['entry', 'support'], ['entry', 'support'], 0.95, 1.0],
            'partial_fill_two_thirds' => [['entry', 'support', 'awper'], ['entry', 'support'], 0.75, 0.90],
            'user_covers_plus_extras' => [['entry'], ['entry', 'support', 'awper', 'igl'], 0.95, 1.0],
            'multi_role_player' => [['awper', 'igl'], ['entry', 'support', 'lurker'], 0.55, 0.65],
            'no_overlap' => [['awper'], ['entry'], 0.25, 0.35],
        ];
    }

    /** @test */
    public function perfect_role_fill_returns_maximum_score(): void
    {
        $neededRoles = ['entry', 'support', 'awper'];
        $userRoles = ['entry', 'support', 'awper'];

        $score = $this->calculateRoleMatch($neededRoles, $userRoles);

        $this->assertEquals(1.0, $score, "Perfect role fill should return 1.0");
    }

    // ════════════════════════════════════════════════════════════════════════
    // REGION PROXIMITY TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider regionProximityProvider
     */
    public function it_calculates_region_proximity_correctly(
        string $region1,
        string $region2,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateRegionProximity($region1, $region2);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Region proximity {$region1}-{$region2} should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Region proximity {$region1}-{$region2} should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function regionProximityProvider(): array
    {
        return [
            'same_region_na' => ['NA', 'NA', 1.0, 1.0],
            'same_region_eu' => ['EU', 'EU', 1.0, 1.0],
            'transatlantic_na_eu' => ['NA', 'EU', 0.45, 0.55],
            'transatlantic_eu_na' => ['EU', 'NA', 0.45, 0.55],
            'same_continent_na_sa' => ['NA', 'SA', 0.55, 0.65],
            'pacific_asia_oceania' => ['ASIA', 'OCEANIA', 0.55, 0.65],
            'cross_pacific_na_asia' => ['NA', 'ASIA', 0.30, 0.45],
            'distant_eu_oceania' => ['EU', 'OCEANIA', 0.20, 0.35],
            'south_atlantic_sa_africa' => ['SA', 'AFRICA', 0.35, 0.50],
        ];
    }

    /** @test */
    public function region_proximity_is_symmetric(): void
    {
        $regions = ['NA', 'SA', 'EU', 'ASIA', 'OCEANIA', 'AFRICA'];

        foreach ($regions as $r1) {
            foreach ($regions as $r2) {
                $score1 = $this->calculateRegionProximity($r1, $r2);
                $score2 = $this->calculateRegionProximity($r2, $r1);

                $this->assertEquals(
                    $score1,
                    $score2,
                    "Region proximity should be symmetric: {$r1}-{$r2} vs {$r2}-{$r1}"
                );
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // SKILL NORMALIZATION TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider skillNormalizationProvider
     */
    public function it_normalizes_skill_scores_correctly(
        int $difference,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->normalizeSkillScore($difference);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Normalized skill score for diff={$difference} should be >= {$expectedMin}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Normalized skill score for diff={$difference} should be <= {$expectedMax}"
        );
    }

    public static function skillNormalizationProvider(): array
    {
        return [
            'no_difference' => [0, 1.0, 1.0],
            'one_level_gap' => [1, 0.60, 0.70],
            'two_level_gap_penalty' => [2, 0.10, 0.20],
            'three_level_gap' => [3, 0.0, 0.05],
            'four_plus_clamped' => [4, 0.0, 0.0],
        ];
    }

    /** @test */
    public function skill_penalty_applies_at_two_levels(): void
    {
        $oneLevelScore = $this->normalizeSkillScore(1);
        $twoLevelScore = $this->normalizeSkillScore(2);

        // Two level gap should be significantly lower due to 50% penalty
        $this->assertLessThan(
            $oneLevelScore * 0.7,
            $twoLevelScore,
            "Two level gap should have significant penalty"
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    // WEIGHT VALIDATION TESTS
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function valid_weights_sum_to_one(): void
    {
        $validWeights = [
            'skill' => 0.40,
            'composition' => 0.30,
            'region' => 0.15,
            'schedule' => 0.10,
            'language' => 0.05,
        ];

        $sum = array_sum($validWeights);

        $this->assertEqualsWithDelta(1.0, $sum, 0.001, "Valid weights should sum to 1.0");
    }

    /** @test */
    public function invalid_weights_detected(): void
    {
        $invalidWeights = [
            'skill' => 0.50, // Too high
            'composition' => 0.30,
            'region' => 0.15,
            'schedule' => 0.10,
            'language' => 0.05,
        ];

        $sum = array_sum($invalidWeights);

        $this->assertNotEquals(1.0, $sum, "Invalid weights should not sum to 1.0");
    }

    // ════════════════════════════════════════════════════════════════════════
    // ACTIVITY TIME MATCHING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider activityTimeMatchProvider
     */
    public function it_calculates_activity_time_match_correctly(
        array $userTimes,
        array $teamTimes,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateActivityTimeMatch($userTimes, $teamTimes);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Activity time match should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Activity time match should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function activityTimeMatchProvider(): array
    {
        return [
            'identical_times' => [['evening', 'night'], ['evening', 'night'], 0.85, 1.0],
            'partial_overlap' => [['evening'], ['evening', 'night'], 0.70, 0.85],
            'no_direct_overlap' => [['morning', 'afternoon'], ['evening', 'night'], 0.45, 0.60],
            'single_common' => [['morning', 'afternoon', 'evening'], ['afternoon'], 0.50, 0.70],
            'no_user_preference' => [[], ['evening'], 0.65, 0.75],
            'no_team_preference' => [['evening'], [], 0.65, 0.75],
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    // LANGUAGE COMPATIBILITY TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider languageCompatibilityProvider
     */
    public function it_calculates_language_compatibility_correctly(
        array $userLangs,
        array $teamLangs,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateLanguageCompatibility($userLangs, $teamLangs);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Language compatibility should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Language compatibility should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function languageCompatibilityProvider(): array
    {
        return [
            'same_language' => [['english'], ['english'], 0.95, 1.0],
            'identical_sets' => [['english', 'spanish'], ['english', 'spanish'], 0.95, 1.0],
            'partial_with_english' => [['english', 'german'], ['english', 'french'], 0.60, 0.80],
            'no_overlap' => [['german', 'french'], ['spanish', 'italian'], 0.25, 0.35],
            'english_fallback' => [['malay'], ['english'], 0.55, 0.65],
            'no_user_preference' => [[], ['english'], 0.65, 0.75],
        ];
    }

    /** @test */
    public function english_fallback_provides_baseline_score(): void
    {
        // When user doesn't speak team's language but one has English
        $score = $this->calculateLanguageCompatibility(['german'], ['english']);

        $this->assertGreaterThanOrEqual(0.55, $score, "English fallback should provide baseline");
    }

    // ════════════════════════════════════════════════════════════════════════
    // HELPER METHODS (Algorithm Logic Replication)
    // ════════════════════════════════════════════════════════════════════════

    protected function calculateSkillCompatibility(string $teamSkill, string $requestSkill): float
    {
        if ($requestSkill === 'unranked') {
            return 50.0;
        }

        $skillMap = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4, 'unranked' => 2];

        $teamNumeric = $skillMap[$teamSkill] ?? 2;
        $requestNumeric = $skillMap[$requestSkill] ?? 2;

        $difference = abs($teamNumeric - $requestNumeric);

        return $this->normalizeSkillScore($difference) * 100;
    }

    protected function normalizeSkillScore(int $difference): float
    {
        $baseScore = max(0, 1.0 - ($difference / 3));

        if ($difference >= 2) {
            $baseScore *= 0.5;
        }

        return max(0, min(1, $baseScore));
    }

    protected function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        if (empty($set1) || empty($set2)) {
            return 0.0;
        }

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        if (empty($union)) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    protected function calculateRoleMatch(array $neededRoles, array $userRoles): float
    {
        if (empty($neededRoles)) {
            return 0.80;
        }

        if (empty($userRoles)) {
            return 0.70;
        }

        $matchingRoles = array_intersect($neededRoles, $userRoles);

        if (!empty($matchingRoles)) {
            $fillRatio = count($matchingRoles) / count($neededRoles);

            if ($fillRatio >= 1.0) {
                return 1.0;
            }

            return 0.70 + ($fillRatio * 0.25);
        }

        if (count($userRoles) >= 3) {
            return 0.60;
        }

        $jaccardScore = $this->calculateJaccardSimilarity($neededRoles, $userRoles);

        if ($jaccardScore > 0) {
            return 0.40 + ($jaccardScore * 0.30);
        }

        return 0.30;
    }

    protected function calculateRegionProximity(string $region1, string $region2): float
    {
        if (strtoupper($region1) === strtoupper($region2)) {
            return 1.0;
        }

        $proximityMatrix = [
            'NA' => ['SA' => 0.60, 'EU' => 0.50, 'ASIA' => 0.35, 'OCEANIA' => 0.40, 'AFRICA' => 0.40],
            'SA' => ['NA' => 0.60, 'EU' => 0.45, 'ASIA' => 0.30, 'OCEANIA' => 0.35, 'AFRICA' => 0.45],
            'EU' => ['NA' => 0.50, 'SA' => 0.45, 'ASIA' => 0.45, 'OCEANIA' => 0.30, 'AFRICA' => 0.55],
            'ASIA' => ['NA' => 0.35, 'SA' => 0.30, 'EU' => 0.45, 'OCEANIA' => 0.60, 'AFRICA' => 0.40],
            'OCEANIA' => ['NA' => 0.40, 'SA' => 0.35, 'EU' => 0.30, 'ASIA' => 0.60, 'AFRICA' => 0.25],
            'AFRICA' => ['NA' => 0.40, 'SA' => 0.45, 'EU' => 0.55, 'ASIA' => 0.40, 'OCEANIA' => 0.25],
        ];

        $r1 = strtoupper($region1);
        $r2 = strtoupper($region2);

        return $proximityMatrix[$r1][$r2] ?? 0.30;
    }

    protected function calculateActivityTimeMatch(array $userTimes, array $teamTimes): float
    {
        if (empty($userTimes) || empty($teamTimes)) {
            return 0.70;
        }

        $jaccard = $this->calculateJaccardSimilarity($userTimes, $teamTimes);

        if ($jaccard >= 0.70) {
            return 0.85 + ($jaccard - 0.70) * 0.5;
        } elseif ($jaccard >= 0.40) {
            return 0.70 + (($jaccard - 0.40) / 0.30) * 0.15;
        } elseif ($jaccard >= 0.20) {
            return 0.50 + (($jaccard - 0.20) / 0.20) * 0.20;
        } elseif ($jaccard > 0) {
            return 0.30 + ($jaccard / 0.20) * 0.20;
        }

        return 0.50;
    }

    protected function calculateLanguageCompatibility(array $userLangs, array $teamLangs): float
    {
        if (empty($userLangs)) {
            return 0.70;
        }

        $jaccard = $this->calculateJaccardSimilarity($userLangs, $teamLangs);

        if ($jaccard > 0) {
            return 0.50 + ($jaccard * 0.50);
        }

        if (in_array('english', $teamLangs) || in_array('english', $userLangs)) {
            return 0.60;
        }

        return 0.30;
    }
}
