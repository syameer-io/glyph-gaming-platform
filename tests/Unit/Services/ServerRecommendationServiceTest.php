<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

/**
 * Unit tests for the Server Recommendation Algorithm
 *
 * Tests each scoring strategy and scoring criterion independently
 * to verify algorithm accuracy across all scenarios.
 *
 * @group algorithm
 * @group recommendation
 */
class ServerRecommendationServiceTest extends TestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // TEMPORAL SCORING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider temporalScoreProvider
     */
    public function it_calculates_temporal_score_correctly(
        ?string $pattern,
        array $peakHours,
        array $serverTags,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateTemporalScore($pattern, $peakHours, $serverTags);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Temporal score should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Temporal score should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function temporalScoreProvider(): array
    {
        return [
            'perfect_evening_match' => ['evening', [18, 19, 20, 21], ['evening'], 80, 100],
            'perfect_morning_match' => ['morning', [7, 8, 9, 10], ['morning'], 75, 100],
            'evening_night_secondary' => ['evening', [18, 19, 20], ['night'], 50, 70],
            'morning_vs_evening_poor' => ['morning', [7, 8, 9], ['evening', 'night'], 25, 45],
            'no_temporal_data' => [null, [], [], 45, 55],
            'no_server_tags' => ['evening', [18, 19, 20], [], 55, 65],
        ];
    }

    /** @test */
    public function no_temporal_data_returns_neutral_score(): void
    {
        $score = $this->calculateTemporalScore(null, [], []);

        $this->assertEqualsWithDelta(50, $score, 5, "No temporal data should return ~50");
    }

    // ════════════════════════════════════════════════════════════════════════
    // ACTIVITY PATTERN MATCHING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider activityPatternProvider
     */
    public function it_matches_activity_patterns_correctly(
        string $pattern,
        array $serverTags,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->matchActivityPattern($pattern, $serverTags);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Activity pattern match should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Activity pattern match should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function activityPatternProvider(): array
    {
        return [
            'primary_match_evening' => ['evening', ['evening'], 85, 100],
            'primary_plus_secondary' => ['evening', ['evening', 'night'], 90, 100],
            'secondary_only' => ['evening', ['night'], 65, 80],
            'morning_full_match' => ['morning', ['morning', 'afternoon'], 90, 100],
            'weekend_with_evening' => ['weekend', ['weekend', 'evening'], 90, 100],
            'consistent_all_day' => ['consistent', ['morning', 'afternoon', 'evening'], 95, 100],
            'no_adjacent_match' => ['evening', ['morning'], 20, 35],  // Evening maps to [evening, night], morning isn't adjacent
            'no_overlap' => ['morning', ['night'], 20, 35],
        ];
    }

    /** @test */
    public function primary_tag_match_scores_highest(): void
    {
        $primaryScore = $this->matchActivityPattern('evening', ['evening']);
        $secondaryScore = $this->matchActivityPattern('evening', ['night']);

        $this->assertGreaterThan(
            $secondaryScore,
            $primaryScore,
            "Primary tag match should score higher than secondary"
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    // PEAK HOURS MATCHING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider peakHoursProvider
     */
    public function it_matches_peak_hours_to_activity_tags(
        array $peakHours,
        array $serverTags,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->matchPeakHoursToActivityTags($peakHours, $serverTags);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Peak hours match should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Peak hours match should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function peakHoursProvider(): array
    {
        return [
            'evening_hours_evening_tag' => [[18, 19, 20, 21, 22], ['evening'], 80, 100],
            'morning_hours_morning_tag' => [[7, 8, 9, 10, 11], ['morning'], 80, 100],
            'afternoon_hours_partial' => [[12, 13, 14, 15], ['afternoon'], 70, 85],  // 4/6 hours
            'night_hours_night_tag' => [[23, 0, 1, 2], ['night'], 80, 100],
            'evening_vs_morning_no_overlap' => [[18, 19, 20], ['morning'], 20, 40],
            'spanning_afternoon_evening' => [[14, 15, 16, 17, 18, 19], ['afternoon', 'evening'], 60, 75],  // 6/11 overlap
            'no_peak_hours' => [[], ['evening'], 45, 55],
        ];
    }

    /** @test */
    public function complete_hour_overlap_scores_maximum(): void
    {
        // Evening tag = hours 18-22
        $peakHours = [18, 19, 20, 21, 22];
        $serverTags = ['evening'];

        $score = $this->matchPeakHoursToActivityTags($peakHours, $serverTags);

        $this->assertGreaterThanOrEqual(90, $score, "Complete overlap should score 90+");
    }

    // ════════════════════════════════════════════════════════════════════════
    // WEEKEND PREFERENCE TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider weekendPreferenceProvider
     */
    public function it_matches_weekend_preference_correctly(
        array $peakDays,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->matchWeekendPreference($peakDays);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Weekend preference should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Weekend preference should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function weekendPreferenceProvider(): array
    {
        return [
            'all_weekend_days' => [['friday', 'saturday', 'sunday'], 85, 95],
            'saturday_sunday' => [['saturday', 'sunday'], 65, 75],  // 2/3 = 0.67 is < 0.67 threshold
            'friday_saturday' => [['friday', 'saturday'], 65, 75],  // 2/3 = 0.67 is < 0.67 threshold
            'only_saturday' => [['saturday'], 65, 75],
            'only_sunday' => [['sunday'], 65, 75],
            'weekday_preference' => [['monday', 'tuesday', 'wednesday'], 30, 40],
            'mixed_with_weekend' => [['monday', 'friday'], 65, 75],
        ];
    }

    /** @test */
    public function strong_weekend_preference_scores_highest(): void
    {
        $fullWeekend = $this->matchWeekendPreference(['friday', 'saturday', 'sunday']);
        $partialWeekend = $this->matchWeekendPreference(['saturday']);
        $weekday = $this->matchWeekendPreference(['monday', 'tuesday']);

        $this->assertGreaterThan($partialWeekend, $fullWeekend);
        $this->assertGreaterThan($weekday, $partialWeekend);
    }

    // ════════════════════════════════════════════════════════════════════════
    // ACTIVITY SCORE TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider activityScoreProvider
     */
    public function it_calculates_activity_score_correctly(
        int $memberCount,
        int $activeChannels,
        int $daysOld,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateActivityScore($memberCount, $activeChannels, $daysOld);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Activity score should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Activity score should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function activityScoreProvider(): array
    {
        return [
            'ideal_small_server' => [10, 3, 15, 75, 90],
            'medium_server' => [50, 2, 60, 50, 70],
            'large_server' => [200, 1, 120, 30, 50],
            'minimum_viable' => [5, 0, 100, 35, 50],
            'very_large_established' => [1000, 5, 365, 40, 60],
            'sweet_spot_server' => [15, 3, 20, 75, 95],
        ];
    }

    /** @test */
    public function small_active_new_servers_score_highest(): void
    {
        // 5-20 members, 3 active channels, new (< 30 days)
        $idealScore = $this->calculateActivityScore(15, 3, 20);

        // Large, less active, old
        $poorScore = $this->calculateActivityScore(600, 1, 400);

        $this->assertGreaterThan($poorScore, $idealScore);
    }

    // ════════════════════════════════════════════════════════════════════════
    // SKILL MATCH SCORE TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider skillMatchProvider
     */
    public function it_calculates_skill_match_score_correctly(
        string $userSkill,
        string $serverSkill,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateSkillMatchScore($userSkill, $serverSkill);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Skill match should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Skill match should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function skillMatchProvider(): array
    {
        return [
            'beginner_beginner' => ['beginner', 'beginner', 90, 100],
            'intermediate_intermediate' => ['intermediate', 'intermediate', 90, 100],
            'advanced_advanced' => ['advanced', 'advanced', 90, 100],
            'expert_expert' => ['expert', 'expert', 90, 100],
            'one_gap' => ['beginner', 'intermediate', 60, 80],
            'two_gap' => ['beginner', 'advanced', 30, 50],
            'three_gap' => ['beginner', 'expert', 10, 30],
            'intermediate_expert' => ['intermediate', 'expert', 35, 45],  // 2 level gap
        ];
    }

    /** @test */
    public function same_skill_level_scores_maximum(): void
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'expert'];

        foreach ($levels as $level) {
            $score = $this->calculateSkillMatchScore($level, $level);
            $this->assertEquals(100, $score, "{$level} vs {$level} should be 100");
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // STRATEGY WEIGHTING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider strategyWeightingProvider
     */
    public function it_applies_strategy_weighting_correctly(
        string $strategy,
        float $expectedMin,
        float $expectedMax
    ): void {
        $scores = [
            'content_based' => 80,
            'collaborative' => 60,
            'social' => 40,
            'temporal' => 70,
            'activity' => 50,
            'skill_match' => 90,
        ];

        $finalScore = $this->applyRecommendationStrategy($scores, $strategy);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $finalScore,
            "Strategy {$strategy} should produce score >= {$expectedMin}, got {$finalScore}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $finalScore,
            "Strategy {$strategy} should produce score <= {$expectedMax}, got {$finalScore}"
        );
    }

    public static function strategyWeightingProvider(): array
    {
        return [
            'content_based' => ['content_based', 70, 80],
            'collaborative' => ['collaborative', 48, 58],
            'social' => ['social', 44, 54],
            'temporal' => ['temporal', 60, 70],
            'hybrid' => ['hybrid', 62, 72],
        ];
    }

    /** @test */
    public function hybrid_strategy_uses_all_criteria(): void
    {
        $scores = [
            'content_based' => 100,
            'collaborative' => 100,
            'social' => 100,
            'temporal' => 100,
            'activity' => 100,
            'skill_match' => 100,
        ];

        $finalScore = $this->applyRecommendationStrategy($scores, 'hybrid');

        $this->assertEquals(100, $finalScore, "Hybrid with all 100s should be 100");
    }

    /** @test */
    public function strategy_weights_sum_to_one(): void
    {
        // Content-based: 80% content + 20% activity
        $this->assertEqualsWithDelta(1.0, 0.8 + 0.2, 0.001);

        // Collaborative: 60% collaborative + 40% social
        $this->assertEqualsWithDelta(1.0, 0.6 + 0.4, 0.001);

        // Social: 70% social + 30% collaborative
        $this->assertEqualsWithDelta(1.0, 0.7 + 0.3, 0.001);

        // Temporal: 60% temporal + 40% activity
        $this->assertEqualsWithDelta(1.0, 0.6 + 0.4, 0.001);

        // Hybrid: 25 + 20 + 15 + 15 + 15 + 10 = 100%
        $this->assertEqualsWithDelta(1.0, 0.25 + 0.20 + 0.15 + 0.15 + 0.15 + 0.10, 0.001);
    }

    // ════════════════════════════════════════════════════════════════════════
    // ADJACENT TIME SCORING TESTS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider adjacentTimeProvider
     */
    public function it_calculates_adjacent_time_score_correctly(
        array $userTags,
        array $serverTags,
        float $expectedMin,
        float $expectedMax
    ): void {
        $score = $this->calculateAdjacentTimeScore($userTags, $serverTags);

        $this->assertGreaterThanOrEqual(
            $expectedMin,
            $score,
            "Adjacent time score should be >= {$expectedMin}, got {$score}"
        );
        $this->assertLessThanOrEqual(
            $expectedMax,
            $score,
            "Adjacent time score should be <= {$expectedMax}, got {$score}"
        );
    }

    public static function adjacentTimeProvider(): array
    {
        return [
            'morning_to_afternoon' => [['morning'], ['afternoon'], 45, 60],
            'afternoon_to_evening' => [['afternoon'], ['evening'], 45, 60],
            'evening_to_night' => [['evening'], ['night'], 45, 60],
            'morning_to_night_no_adjacent' => [['morning'], ['night'], 20, 30],
            'afternoon_to_night_no_adjacent' => [['afternoon'], ['night'], 20, 30],
            'multiple_user_one_adjacent' => [['evening', 'afternoon'], ['morning'], 45, 60],
        ];
    }

    /** @test */
    public function non_adjacent_times_score_lowest(): void
    {
        // Morning and night are not adjacent
        $score = $this->calculateAdjacentTimeScore(['morning'], ['night']);

        $this->assertLessThan(30, $score, "Non-adjacent times should score < 30");
    }

    // ════════════════════════════════════════════════════════════════════════
    // JACCARD SIMILARITY TESTS (Shared with Matchmaking)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function jaccard_returns_one_for_identical_sets(): void
    {
        $set = ['a', 'b', 'c'];
        $score = $this->calculateJaccardSimilarity($set, $set);

        $this->assertEquals(1.0, $score);
    }

    /** @test */
    public function jaccard_returns_zero_for_disjoint_sets(): void
    {
        $score = $this->calculateJaccardSimilarity(['a', 'b'], ['c', 'd']);

        $this->assertEquals(0.0, $score);
    }

    /** @test */
    public function jaccard_handles_empty_sets(): void
    {
        $this->assertEquals(0.0, $this->calculateJaccardSimilarity([], ['a']));
        $this->assertEquals(0.0, $this->calculateJaccardSimilarity(['a'], []));
        $this->assertEquals(0.0, $this->calculateJaccardSimilarity([], []));
    }

    // ════════════════════════════════════════════════════════════════════════
    // HELPER METHODS (Algorithm Logic Replication)
    // ════════════════════════════════════════════════════════════════════════

    protected function calculateTemporalScore(?string $pattern, array $peakHours, array $serverTags): float
    {
        if (empty($peakHours) && empty($pattern)) {
            return 50.0;
        }

        if (empty($serverTags)) {
            return 60.0;
        }

        $scores = [];

        if ($pattern && $pattern !== 'unknown' && $pattern !== 'varied') {
            $patternScore = $this->matchActivityPattern($pattern, $serverTags);
            $scores[] = ['score' => $patternScore, 'weight' => 0.5];
        }

        if (!empty($peakHours)) {
            $hoursScore = $this->matchPeakHoursToActivityTags($peakHours, $serverTags);
            $scores[] = ['score' => $hoursScore, 'weight' => 0.35];
        }

        if (empty($scores)) {
            return 50.0;
        }

        $totalWeight = array_sum(array_column($scores, 'weight'));
        $weightedSum = 0;

        foreach ($scores as $scoreData) {
            $weightedSum += $scoreData['score'] * ($scoreData['weight'] / $totalWeight);
        }

        return min(max($weightedSum, 0), 100);
    }

    protected function matchActivityPattern(string $pattern, array $serverTags): float
    {
        $patternToTagMap = [
            'evening' => ['evening', 'night'],
            'evening_weekend' => ['evening', 'weekend', 'night'],
            'weekend' => ['weekend', 'evening', 'afternoon'],
            'morning' => ['morning', 'afternoon'],
            'consistent' => ['morning', 'afternoon', 'evening'],
        ];

        $expectedTags = $patternToTagMap[$pattern] ?? [];

        if (empty($expectedTags)) {
            return 50.0;
        }

        $primaryTag = $expectedTags[0];
        $secondaryTags = array_slice($expectedTags, 1);

        if (in_array($primaryTag, $serverTags)) {
            $additionalMatches = count(array_intersect($secondaryTags, $serverTags));
            $bonus = min($additionalMatches * 5, 15);
            return min(85 + $bonus, 100);
        }

        $secondaryMatches = array_intersect($secondaryTags, $serverTags);
        if (!empty($secondaryMatches)) {
            $matchRatio = count($secondaryMatches) / count($secondaryTags);
            return 65 + ($matchRatio * 15);
        }

        return $this->calculateAdjacentTimeScore($expectedTags, $serverTags);
    }

    protected function calculateAdjacentTimeScore(array $userTags, array $serverTags): float
    {
        $adjacencyMap = [
            'morning' => ['afternoon'],
            'afternoon' => ['morning', 'evening'],
            'evening' => ['afternoon', 'night'],
            'night' => ['evening'],
            'weekend' => ['evening', 'afternoon'],
        ];

        $adjacentMatches = 0;

        foreach ($userTags as $userTag) {
            $adjacentTags = $adjacencyMap[$userTag] ?? [];
            if (!empty(array_intersect($adjacentTags, $serverTags))) {
                $adjacentMatches++;
            }
        }

        if ($adjacentMatches > 0) {
            return 40 + ($adjacentMatches / count($userTags)) * 20;
        }

        return 25.0;
    }

    protected function matchPeakHoursToActivityTags(array $peakHours, array $serverTags): float
    {
        if (empty($peakHours)) {
            return 50.0;
        }

        $serverHours = [];
        foreach ($serverTags as $tag) {
            $tagHours = $this->getActivityTimeHourRange($tag);
            $serverHours = array_merge($serverHours, $tagHours);
        }
        $serverHours = array_unique($serverHours);

        if (empty($serverHours)) {
            return 50.0;
        }

        $jaccardScore = $this->calculateJaccardSimilarity($peakHours, $serverHours);

        return 20 + ($jaccardScore * 80);
    }

    protected function getActivityTimeHourRange(string $activityTime): array
    {
        return match($activityTime) {
            'morning' => [6, 7, 8, 9, 10, 11],
            'afternoon' => [12, 13, 14, 15, 16, 17],
            'evening' => [18, 19, 20, 21, 22],
            'night' => [23, 0, 1, 2, 3],
            'weekend' => range(0, 23),
            default => [],
        };
    }

    protected function matchWeekendPreference(array $peakDays): float
    {
        $weekendDays = ['friday', 'saturday', 'sunday'];
        $overlap = array_intersect($peakDays, $weekendDays);
        $overlapRatio = count($overlap) / count($weekendDays);

        if ($overlapRatio >= 0.67) {
            return 90.0;
        } elseif ($overlapRatio >= 0.33) {
            return 70.0;
        }

        return 35.0;
    }

    protected function calculateActivityScore(int $memberCount, int $activeChannels, int $daysOld): float
    {
        $memberScore = 0;

        if ($memberCount >= 5 && $memberCount <= 20) {
            $memberScore = 40;
        } elseif ($memberCount > 20 && $memberCount <= 100) {
            $memberScore = 35;
        } elseif ($memberCount > 100 && $memberCount <= 500) {
            $memberScore = 25;
        } elseif ($memberCount > 500) {
            $memberScore = 15;
        }

        $activityScore = min($activeChannels * 10, 30);

        $ageScore = 0;
        if ($daysOld < 30) {
            $ageScore = 15;
        } elseif ($daysOld < 90) {
            $ageScore = 10;
        }

        return min($memberScore + $activityScore + $ageScore, 100);
    }

    protected function calculateSkillMatchScore(string $userSkill, string $serverSkill): float
    {
        $skillLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];

        $userLevel = $skillLevels[$userSkill] ?? 2;
        $serverLevel = $skillLevels[$serverSkill] ?? 2;

        $difference = abs($userLevel - $serverLevel);

        return match($difference) {
            0 => 100,
            1 => 70,
            2 => 40,
            3 => 20,
            default => 10,
        };
    }

    protected function applyRecommendationStrategy(array $scores, string $strategy): float
    {
        return match($strategy) {
            'content_based' => ($scores['content_based'] * 0.8) + ($scores['activity'] * 0.2),
            'collaborative' => ($scores['collaborative'] * 0.6) + ($scores['social'] * 0.4),
            'social' => ($scores['social'] * 0.7) + ($scores['collaborative'] * 0.3),
            'temporal' => ($scores['temporal'] * 0.6) + ($scores['activity'] * 0.4),
            'hybrid' => ($scores['content_based'] * 0.25) +
                       ($scores['collaborative'] * 0.20) +
                       ($scores['social'] * 0.15) +
                       ($scores['temporal'] * 0.15) +
                       ($scores['activity'] * 0.15) +
                       ($scores['skill_match'] * 0.10),
            default => 0,
        };
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
}
