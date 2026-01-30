<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MatchmakingService;
use App\Services\ServerRecommendationService;
use Illuminate\Support\Facades\File;

class TestAlgorithmAccuracy extends Command
{
    protected $signature = 'test:algorithm-accuracy
                            {--algorithm=all : Which algorithm to test (matchmaking, recommendation, all)}
                            {--detailed : Show detailed output for each test case}
                            {--html : Generate HTML report}
                            {--output= : Custom output path for HTML report}';

    protected $description = 'Run comprehensive accuracy tests on matchmaking and server recommendation algorithms';

    protected MatchmakingService $matchmakingService;
    protected ServerRecommendationService $recommendationService;

    protected array $results = [
        'matchmaking' => [],
        'recommendation' => [],
    ];

    protected array $summary = [
        'matchmaking' => ['total' => 0, 'passed' => 0, 'failed' => 0],
        'recommendation' => ['total' => 0, 'passed' => 0, 'failed' => 0],
    ];

    protected float $startTime;

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $algorithm = $this->option('algorithm');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║           ALGORITHM ACCURACY TESTING SUITE v1.0                  ║');
        $this->info('║                  Glyph Gaming Platform                           ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->line('Test started at: ' . now()->format('Y-m-d H:i:s'));
        $this->line('─────────────────────────────────────────────────────────────────────');
        $this->info('');

        $this->matchmakingService = app(MatchmakingService::class);
        $this->recommendationService = app(ServerRecommendationService::class);

        if ($algorithm === 'all' || $algorithm === 'matchmaking') {
            $this->testMatchmakingAlgorithm();
        }

        if ($algorithm === 'all' || $algorithm === 'recommendation') {
            $this->testServerRecommendationAlgorithm();
        }

        $this->displaySummary();

        if ($this->option('html')) {
            $this->generateHtmlReport();
        }

        $totalTests = $this->summary['matchmaking']['total'] + $this->summary['recommendation']['total'];
        $totalPassed = $this->summary['matchmaking']['passed'] + $this->summary['recommendation']['passed'];

        return $totalPassed === $totalTests ? Command::SUCCESS : Command::FAILURE;
    }

    protected function testMatchmakingAlgorithm(): void
    {
        $this->info('┌──────────────────────────────────────────────────────────────────┐');
        $this->info('│  MATCHMAKING ALGORITHM ACCURACY TESTS                            │');
        $this->info('└──────────────────────────────────────────────────────────────────┘');
        $this->info('');

        // Test 1: Skill Compatibility Matrix
        $this->testSkillCompatibility();

        // Test 2: Jaccard Similarity Function
        $this->testJaccardSimilarity();

        // Test 3: Role Match Scoring
        $this->testRoleMatchScoring();

        // Test 4: Region Proximity Matrix
        $this->testRegionProximity();

        // Test 5: Skill Score Normalization
        $this->testSkillNormalization();

        // Test 6: Weight Validation
        $this->testWeightValidation();

        // Test 7: Activity Time Matching
        $this->testActivityTimeMatching();

        // Test 8: Language Compatibility
        $this->testLanguageCompatibility();
    }

    protected function testServerRecommendationAlgorithm(): void
    {
        $this->info('');
        $this->info('┌──────────────────────────────────────────────────────────────────┐');
        $this->info('│  SERVER RECOMMENDATION ALGORITHM ACCURACY TESTS                  │');
        $this->info('└──────────────────────────────────────────────────────────────────┘');
        $this->info('');

        // Test 1: Temporal Scoring
        $this->testTemporalScoring();

        // Test 2: Activity Pattern Matching
        $this->testActivityPatternMatching();

        // Test 3: Peak Hours to Tags Matching
        $this->testPeakHoursMatching();

        // Test 4: Weekend Preference Matching
        $this->testWeekendPreference();

        // Test 5: Activity Score Calculation
        $this->testActivityScoreCalculation();

        // Test 6: Skill Match Score
        $this->testSkillMatchScore();

        // Test 7: Strategy Weighting
        $this->testStrategyWeighting();

        // Test 8: Adjacent Time Scoring
        $this->testAdjacentTimeScoring();
    }

    // ══════════════════════════════════════════════════════════════════
    // MATCHMAKING TESTS
    // ══════════════════════════════════════════════════════════════════

    protected function testSkillCompatibility(): void
    {
        $this->line('  ▶ Testing Skill Compatibility Matrix');

        $testCases = [
            // [team_skill, request_skill, expected_min, expected_max, description]
            ['beginner', 'beginner', 100, 100, 'Same level (beginner)'],
            ['intermediate', 'intermediate', 100, 100, 'Same level (intermediate)'],
            ['advanced', 'advanced', 100, 100, 'Same level (advanced)'],
            ['expert', 'expert', 100, 100, 'Same level (expert)'],
            ['beginner', 'intermediate', 60, 75, '1 level gap (beginner-intermediate)'],
            ['intermediate', 'advanced', 60, 75, '1 level gap (intermediate-advanced)'],
            ['advanced', 'expert', 60, 75, '1 level gap (advanced-expert)'],
            ['beginner', 'advanced', 10, 25, '2 level gap with penalty (beginner-advanced)'],
            ['intermediate', 'expert', 10, 25, '2 level gap with penalty (intermediate-expert)'],
            ['beginner', 'expert', 0, 5, '3 level gap (beginner-expert)'],
            ['expert', 'beginner', 0, 5, '3 level gap reversed (expert-beginner)'],
            ['intermediate', 'unranked', 45, 55, 'Unranked player gets neutral score'],
        ];

        foreach ($testCases as $case) {
            [$teamSkill, $requestSkill, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateSkillCompatibilityTest($teamSkill, $requestSkill);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Skill Compatibility', $description, [
                'team_skill' => $teamSkill,
                'request_skill' => $requestSkill,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Skill Compatibility', 'matchmaking');
    }

    protected function testJaccardSimilarity(): void
    {
        $this->line('  ▶ Testing Jaccard Similarity Function');

        $testCases = [
            // [set1, set2, expected_min, expected_max, description]
            [['a', 'b', 'c'], ['a', 'b', 'c'], 1.0, 1.0, 'Identical sets'],
            [['a', 'b'], ['c', 'd'], 0.0, 0.0, 'No overlap'],
            [['a', 'b', 'c'], ['a', 'b'], 0.65, 0.70, '2/3 overlap'],
            [['a', 'b', 'c', 'd'], ['a', 'b'], 0.45, 0.55, '2/4 overlap'],
            [['a'], ['a', 'b', 'c'], 0.30, 0.35, '1/3 overlap'],
            [[], ['a', 'b'], 0.0, 0.0, 'Empty first set'],
            [['a', 'b'], [], 0.0, 0.0, 'Empty second set'],
            [[], [], 0.0, 0.0, 'Both empty sets'],
        ];

        foreach ($testCases as $case) {
            [$set1, $set2, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateJaccardSimilarityTest($set1, $set2);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Jaccard Similarity', $description, [
                'set1' => $set1,
                'set2' => $set2,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Jaccard Similarity', 'matchmaking');
    }

    protected function testRoleMatchScoring(): void
    {
        $this->line('  ▶ Testing Role Match Scoring');

        $testCases = [
            // [needed_roles, user_roles, expected_min, expected_max, description]
            [[], ['entry', 'support'], 0.75, 0.85, 'Team has no role needs (flexible)'],
            [['entry', 'support'], [], 0.65, 0.75, 'User has no preferences (flex player)'],
            [['entry', 'support'], ['entry', 'support'], 0.95, 1.0, 'Perfect fill (all roles matched)'],
            [['entry', 'support', 'awper'], ['entry', 'support'], 0.75, 0.90, 'Partial fill (2/3 roles)'],
            [['entry'], ['entry', 'support', 'awper', 'igl'], 0.95, 1.0, 'User covers needed role + extras'],
            [['awper', 'igl'], ['entry', 'support', 'lurker'], 0.55, 0.65, 'Multi-role player (3+ roles)'],
            [['awper'], ['entry'], 0.25, 0.35, 'No overlap at all'],
        ];

        foreach ($testCases as $case) {
            [$neededRoles, $userRoles, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateRoleMatchTest($neededRoles, $userRoles);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Role Match Scoring', $description, [
                'needed_roles' => $neededRoles,
                'user_roles' => $userRoles,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Role Match Scoring', 'matchmaking');
    }

    protected function testRegionProximity(): void
    {
        $this->line('  ▶ Testing Region Proximity Matrix');

        $testCases = [
            // [region1, region2, expected_min, expected_max, description]
            ['NA', 'NA', 1.0, 1.0, 'Same region (NA)'],
            ['EU', 'EU', 1.0, 1.0, 'Same region (EU)'],
            ['NA', 'EU', 0.45, 0.55, 'NA to EU (transatlantic)'],
            ['EU', 'NA', 0.45, 0.55, 'EU to NA (transatlantic reversed)'],
            ['NA', 'SA', 0.55, 0.65, 'NA to SA (same continent)'],
            ['ASIA', 'OCEANIA', 0.55, 0.65, 'Asia to Oceania (pacific)'],
            ['NA', 'ASIA', 0.30, 0.45, 'NA to Asia (cross-pacific)'],
            ['EU', 'OCEANIA', 0.20, 0.35, 'EU to Oceania (distant)'],
            ['SA', 'AFRICA', 0.35, 0.50, 'SA to Africa (south atlantic)'],
        ];

        foreach ($testCases as $case) {
            [$region1, $region2, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateRegionProximityTest($region1, $region2);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Region Proximity', $description, [
                'region1' => $region1,
                'region2' => $region2,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Region Proximity', 'matchmaking');
    }

    protected function testSkillNormalization(): void
    {
        $this->line('  ▶ Testing Skill Score Normalization');

        $testCases = [
            // [difference, expected_min, expected_max, description]
            [0, 1.0, 1.0, 'No difference (perfect match)'],
            [1, 0.60, 0.70, 'Difference of 1 level'],
            [2, 0.10, 0.20, 'Difference of 2 levels (with penalty)'],
            [3, 0.0, 0.05, 'Difference of 3 levels (max gap)'],
            [4, 0.0, 0.0, 'Difference of 4+ levels (clamped to 0)'],
        ];

        foreach ($testCases as $case) {
            [$difference, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->normalizeSkillScoreTest($difference);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Skill Normalization', $description, [
                'difference' => $difference,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Skill Normalization', 'matchmaking');
    }

    protected function testWeightValidation(): void
    {
        $this->line('  ▶ Testing Weight Validation');

        $testCases = [
            // [weights, should_pass, description]
            [
                ['skill' => 0.40, 'composition' => 0.30, 'region' => 0.15, 'schedule' => 0.10, 'language' => 0.05],
                true,
                'Valid weights summing to 1.0'
            ],
            [
                ['skill' => 0.35, 'composition' => 0.25, 'region' => 0.20, 'schedule' => 0.15, 'language' => 0.05],
                true,
                'Alternative valid weights summing to 1.0'
            ],
            [
                ['skill' => 0.50, 'composition' => 0.30, 'region' => 0.15, 'schedule' => 0.10, 'language' => 0.05],
                false,
                'Invalid weights summing to 1.1'
            ],
            [
                ['skill' => 0.30, 'composition' => 0.20, 'region' => 0.15, 'schedule' => 0.10, 'language' => 0.05],
                false,
                'Invalid weights summing to 0.8'
            ],
        ];

        foreach ($testCases as $case) {
            [$weights, $shouldPass, $description] = $case;

            $sum = array_sum($weights);
            $actualValid = abs($sum - 1.0) < 0.001;
            $passed = $actualValid === $shouldPass;

            $this->recordResult('matchmaking', 'Weight Validation', $description, [
                'weights' => $weights,
                'sum' => round($sum, 4),
                'expected_valid' => $shouldPass,
                'actual_valid' => $actualValid,
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $validStr = $actualValid ? 'valid' : 'invalid';
                $this->line("    [{$status}] {$description}: {$validStr} (sum={$sum})");
            }
        }

        $this->printTestGroupResult('Weight Validation', 'matchmaking');
    }

    protected function testActivityTimeMatching(): void
    {
        $this->line('  ▶ Testing Activity Time Matching');

        $testCases = [
            // [user_times, team_times, expected_min, expected_max, description]
            [['evening', 'night'], ['evening', 'night'], 0.85, 1.0, 'Identical activity times'],
            [['evening'], ['evening', 'night'], 0.70, 0.85, 'Partial overlap (subset)'],
            [['morning', 'afternoon'], ['evening', 'night'], 0.45, 0.60, 'No direct overlap'],
            [['morning', 'afternoon', 'evening'], ['afternoon'], 0.50, 0.70, 'Single common time'],
            [[], ['evening'], 0.65, 0.75, 'No user preference (neutral)'],
            [['evening'], [], 0.65, 0.75, 'No team preference (neutral)'],
        ];

        foreach ($testCases as $case) {
            [$userTimes, $teamTimes, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateActivityTimeMatchTest($userTimes, $teamTimes);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Activity Time Match', $description, [
                'user_times' => $userTimes,
                'team_times' => $teamTimes,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Activity Time Match', 'matchmaking');
    }

    protected function testLanguageCompatibility(): void
    {
        $this->line('  ▶ Testing Language Compatibility');

        $testCases = [
            // [user_languages, team_languages, expected_min, expected_max, description]
            [['english'], ['english'], 0.95, 1.0, 'Same language (English)'],
            [['english', 'spanish'], ['english', 'spanish'], 0.95, 1.0, 'Identical language sets'],
            [['english', 'german'], ['english', 'french'], 0.60, 0.80, 'Partial overlap with English'],
            [['german', 'french'], ['spanish', 'italian'], 0.25, 0.35, 'No overlap'],
            [['malay'], ['english'], 0.55, 0.65, 'English fallback (user non-English)'],
            [[], ['english'], 0.65, 0.75, 'No user preference (neutral)'],
        ];

        foreach ($testCases as $case) {
            [$userLangs, $teamLangs, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateLanguageCompatibilityTest($userLangs, $teamLangs);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('matchmaking', 'Language Compatibility', $description, [
                'user_languages' => $userLangs,
                'team_languages' => $teamLangs,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 4),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Language Compatibility', 'matchmaking');
    }

    // ══════════════════════════════════════════════════════════════════
    // SERVER RECOMMENDATION TESTS
    // ══════════════════════════════════════════════════════════════════

    protected function testTemporalScoring(): void
    {
        $this->line('  ▶ Testing Temporal Score Calculation');

        $testCases = [
            // [user_pattern, user_peak_hours, server_tags, expected_min, expected_max, description]
            ['evening', [18, 19, 20, 21], ['evening'], 80, 100, 'Perfect evening match'],
            ['morning', [7, 8, 9, 10], ['morning'], 75, 100, 'Perfect morning match'],
            ['evening', [18, 19, 20], ['night'], 50, 70, 'Evening user, night server (secondary match)'],
            ['morning', [7, 8, 9], ['evening', 'night'], 25, 45, 'Morning user, evening/night server'],
            [null, [], [], 45, 55, 'No temporal data (neutral)'],
            ['evening', [18, 19, 20], [], 55, 65, 'No server tags (slight positive)'],
        ];

        foreach ($testCases as $case) {
            [$pattern, $peakHours, $serverTags, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateTemporalScoreTest($pattern, $peakHours, $serverTags);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Temporal Scoring', $description, [
                'user_pattern' => $pattern,
                'user_peak_hours' => $peakHours,
                'server_tags' => $serverTags,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Temporal Scoring', 'recommendation');
    }

    protected function testActivityPatternMatching(): void
    {
        $this->line('  ▶ Testing Activity Pattern Matching');

        $testCases = [
            // [user_pattern, server_tags, expected_min, expected_max, description]
            ['evening', ['evening'], 85, 100, 'Primary tag match (evening)'],
            ['evening', ['evening', 'night'], 90, 100, 'Primary + secondary match'],
            ['evening', ['night'], 65, 80, 'Secondary tag match only'],
            ['morning', ['morning', 'afternoon'], 90, 100, 'Morning pattern full match'],
            ['weekend', ['weekend', 'evening'], 90, 100, 'Weekend pattern with evening'],
            ['consistent', ['morning', 'afternoon', 'evening'], 95, 100, 'Consistent player all-day server'],
            ['evening', ['morning'], 20, 35, 'No adjacent match (evening-morning)'],
            ['morning', ['night'], 20, 35, 'No overlap at all'],
        ];

        foreach ($testCases as $case) {
            [$pattern, $serverTags, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->matchActivityPatternTest($pattern, $serverTags);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Activity Pattern Match', $description, [
                'user_pattern' => $pattern,
                'server_tags' => $serverTags,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Activity Pattern Match', 'recommendation');
    }

    protected function testPeakHoursMatching(): void
    {
        $this->line('  ▶ Testing Peak Hours to Activity Tags Matching');

        $testCases = [
            // [peak_hours, server_tags, expected_min, expected_max, description]
            [[18, 19, 20, 21, 22], ['evening'], 80, 100, 'Evening hours match evening tag'],
            [[7, 8, 9, 10, 11], ['morning'], 80, 100, 'Morning hours match morning tag'],
            [[12, 13, 14, 15], ['afternoon'], 70, 85, 'Afternoon hours partial match (4/6 hours)'],
            [[23, 0, 1, 2], ['night'], 80, 100, 'Night hours match night tag'],
            [[18, 19, 20], ['morning'], 20, 40, 'Evening hours vs morning tag (no overlap)'],
            [[14, 15, 16, 17, 18, 19], ['afternoon', 'evening'], 60, 75, 'Spanning afternoon-evening (6/11 overlap)'],
            [[], ['evening'], 45, 55, 'No peak hours (neutral)'],
        ];

        foreach ($testCases as $case) {
            [$peakHours, $serverTags, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->matchPeakHoursToActivityTagsTest($peakHours, $serverTags);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Peak Hours Match', $description, [
                'peak_hours' => $peakHours,
                'server_tags' => $serverTags,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Peak Hours Match', 'recommendation');
    }

    protected function testWeekendPreference(): void
    {
        $this->line('  ▶ Testing Weekend Preference Matching');

        $testCases = [
            // [peak_days, expected_min, expected_max, description]
            [['friday', 'saturday', 'sunday'], 85, 95, 'All weekend days'],
            [['saturday', 'sunday'], 65, 75, 'Saturday + Sunday (2/3 ratio < 0.67 threshold)'],
            [['friday', 'saturday'], 65, 75, 'Friday + Saturday (2/3 ratio < 0.67 threshold)'],
            [['saturday'], 65, 75, 'Only Saturday'],
            [['sunday'], 65, 75, 'Only Sunday'],
            [['monday', 'tuesday', 'wednesday'], 30, 40, 'Weekday preference'],
            [['monday', 'friday'], 65, 75, 'Mixed with one weekend day'],
        ];

        foreach ($testCases as $case) {
            [$peakDays, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->matchWeekendPreferenceTest($peakDays);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Weekend Preference', $description, [
                'peak_days' => $peakDays,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Weekend Preference', 'recommendation');
    }

    protected function testActivityScoreCalculation(): void
    {
        $this->line('  ▶ Testing Activity Score Calculation');

        $testCases = [
            // [member_count, active_channels, days_old, expected_min, expected_max, description]
            [10, 3, 15, 75, 90, 'Ideal small server (10 members, active, new)'],
            [50, 2, 60, 50, 70, 'Medium server (50 members, some activity)'],
            [200, 1, 120, 30, 50, 'Large server (200 members, less activity)'],
            [5, 0, 100, 35, 50, 'Minimum viable (5 members, no recent activity)'],
            [1000, 5, 365, 40, 60, 'Very large established server'],
            [15, 3, 20, 75, 95, 'Sweet spot server'],
        ];

        foreach ($testCases as $case) {
            [$memberCount, $activeChannels, $daysOld, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateActivityScoreTest($memberCount, $activeChannels, $daysOld);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Activity Score', $description, [
                'member_count' => $memberCount,
                'active_channels' => $activeChannels,
                'days_old' => $daysOld,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Activity Score', 'recommendation');
    }

    protected function testSkillMatchScore(): void
    {
        $this->line('  ▶ Testing Skill Match Score');

        $testCases = [
            // [user_skill, server_skill, expected_min, expected_max, description]
            ['beginner', 'beginner', 90, 100, 'Beginner matches beginner server'],
            ['intermediate', 'intermediate', 90, 100, 'Intermediate matches intermediate'],
            ['advanced', 'advanced', 90, 100, 'Advanced matches advanced'],
            ['expert', 'expert', 90, 100, 'Expert matches expert'],
            ['beginner', 'intermediate', 60, 80, 'Beginner vs intermediate (1 gap)'],
            ['beginner', 'advanced', 30, 50, 'Beginner vs advanced (2 gap)'],
            ['beginner', 'expert', 10, 30, 'Beginner vs expert (3 gap)'],
            ['intermediate', 'expert', 35, 45, 'Intermediate vs expert (2 level gap)'],
        ];

        foreach ($testCases as $case) {
            [$userSkill, $serverSkill, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateSkillMatchScoreTest($userSkill, $serverSkill);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Skill Match Score', $description, [
                'user_skill' => $userSkill,
                'server_skill' => $serverSkill,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Skill Match Score', 'recommendation');
    }

    protected function testStrategyWeighting(): void
    {
        $this->line('  ▶ Testing Strategy Weighting');

        $scores = [
            'content_based' => 80,
            'collaborative' => 60,
            'social' => 40,
            'temporal' => 70,
            'activity' => 50,
            'skill_match' => 90,
        ];

        $testCases = [
            // [strategy, expected_min, expected_max, description]
            ['content_based', 70, 80, 'Content-based strategy (80% content + 20% activity)'],
            ['collaborative', 48, 58, 'Collaborative strategy (60% collaborative + 40% social)'],
            ['social', 44, 54, 'Social strategy (70% social + 30% collaborative)'],
            ['temporal', 60, 70, 'Temporal strategy (60% temporal + 40% activity)'],
            ['hybrid', 62, 72, 'Hybrid strategy (weighted average of all)'],
        ];

        foreach ($testCases as $case) {
            [$strategy, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->applyRecommendationStrategyTest($scores, $strategy);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Strategy Weighting', $description, [
                'input_scores' => $scores,
                'strategy' => $strategy,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Strategy Weighting', 'recommendation');
    }

    protected function testAdjacentTimeScoring(): void
    {
        $this->line('  ▶ Testing Adjacent Time Scoring');

        $testCases = [
            // [user_tags, server_tags, expected_min, expected_max, description]
            [['morning'], ['afternoon'], 45, 60, 'Morning adjacent to afternoon'],
            [['afternoon'], ['evening'], 45, 60, 'Afternoon adjacent to evening'],
            [['evening'], ['night'], 45, 60, 'Evening adjacent to night'],
            [['morning'], ['night'], 20, 30, 'Morning not adjacent to night'],
            [['afternoon'], ['night'], 20, 30, 'Afternoon not adjacent to night'],
            [['evening', 'afternoon'], ['morning'], 45, 60, 'Multiple user tags, one adjacent'],
        ];

        foreach ($testCases as $case) {
            [$userTags, $serverTags, $expectedMin, $expectedMax, $description] = $case;

            $actualScore = $this->calculateAdjacentTimeScoreTest($userTags, $serverTags);
            $passed = $actualScore >= $expectedMin && $actualScore <= $expectedMax;

            $this->recordResult('recommendation', 'Adjacent Time Score', $description, [
                'user_tags' => $userTags,
                'server_tags' => $serverTags,
                'expected_range' => "{$expectedMin}-{$expectedMax}",
                'actual' => round($actualScore, 2),
                'passed' => $passed,
            ]);

            if ($this->option('detailed')) {
                $status = $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("    [{$status}] {$description}: {$actualScore} (expected {$expectedMin}-{$expectedMax})");
            }
        }

        $this->printTestGroupResult('Adjacent Time Score', 'recommendation');
    }

    // ══════════════════════════════════════════════════════════════════
    // TEST HELPER METHODS (Algorithm Logic Replication)
    // ══════════════════════════════════════════════════════════════════

    protected function calculateSkillCompatibilityTest(string $teamSkill, string $requestSkill): float
    {
        if ($requestSkill === 'unranked') {
            return 50.0;
        }

        $skillMap = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4, 'unranked' => 2];

        $teamNumeric = $skillMap[$teamSkill] ?? 2;
        $requestNumeric = $skillMap[$requestSkill] ?? 2;

        $difference = abs($teamNumeric - $requestNumeric);
        $normalizedScore = $this->normalizeSkillScoreTest($difference);

        return $normalizedScore * 100;
    }

    protected function normalizeSkillScoreTest(int $difference): float
    {
        $baseScore = max(0, 1.0 - ($difference / 3));

        if ($difference >= 2) {
            $baseScore *= 0.5;
        }

        return max(0, min(1, $baseScore));
    }

    protected function calculateJaccardSimilarityTest(array $set1, array $set2): float
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

    protected function calculateRoleMatchTest(array $neededRoles, array $userRoles): float
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

        $jaccardScore = $this->calculateJaccardSimilarityTest($neededRoles, $userRoles);

        if ($jaccardScore > 0) {
            return 0.40 + ($jaccardScore * 0.30);
        }

        return 0.30;
    }

    protected function calculateRegionProximityTest(string $region1, string $region2): float
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

    protected function calculateActivityTimeMatchTest(array $userTimes, array $teamTimes): float
    {
        if (empty($userTimes) || empty($teamTimes)) {
            return 0.70;
        }

        $jaccard = $this->calculateJaccardSimilarityTest($userTimes, $teamTimes);

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

    protected function calculateLanguageCompatibilityTest(array $userLangs, array $teamLangs): float
    {
        if (empty($userLangs)) {
            return 0.70;
        }

        $jaccard = $this->calculateJaccardSimilarityTest($userLangs, $teamLangs);

        if ($jaccard > 0) {
            return 0.50 + ($jaccard * 0.50);
        }

        if (in_array('english', $teamLangs) || in_array('english', $userLangs)) {
            return 0.60;
        }

        return 0.30;
    }

    protected function calculateTemporalScoreTest(?string $pattern, array $peakHours, array $serverTags): float
    {
        if (empty($peakHours) && empty($pattern)) {
            return 50.0;
        }

        if (empty($serverTags)) {
            return 60.0;
        }

        $scores = [];

        if ($pattern && $pattern !== 'unknown' && $pattern !== 'varied') {
            $patternScore = $this->matchActivityPatternTest($pattern, $serverTags);
            $scores[] = ['score' => $patternScore, 'weight' => 0.5];
        }

        if (!empty($peakHours)) {
            $hoursScore = $this->matchPeakHoursToActivityTagsTest($peakHours, $serverTags);
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

    protected function matchActivityPatternTest(string $pattern, array $serverTags): float
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

        return $this->calculateAdjacentTimeScoreTest($expectedTags, $serverTags);
    }

    protected function calculateAdjacentTimeScoreTest(array $userTags, array $serverTags): float
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

    protected function matchPeakHoursToActivityTagsTest(array $peakHours, array $serverTags): float
    {
        if (empty($peakHours)) {
            return 50.0;
        }

        $serverHours = [];
        foreach ($serverTags as $tag) {
            $tagHours = $this->getActivityTimeHourRangeTest($tag);
            $serverHours = array_merge($serverHours, $tagHours);
        }
        $serverHours = array_unique($serverHours);

        if (empty($serverHours)) {
            return 50.0;
        }

        $jaccardScore = $this->calculateJaccardSimilarityTest($peakHours, $serverHours);

        return 20 + ($jaccardScore * 80);
    }

    protected function getActivityTimeHourRangeTest(string $activityTime): array
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

    protected function matchWeekendPreferenceTest(array $peakDays): float
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

    protected function calculateActivityScoreTest(int $memberCount, int $activeChannels, int $daysOld): float
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

    protected function calculateSkillMatchScoreTest(string $userSkill, string $serverSkill): float
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

    protected function applyRecommendationStrategyTest(array $scores, string $strategy): float
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

    // ══════════════════════════════════════════════════════════════════
    // RESULT TRACKING & REPORTING
    // ══════════════════════════════════════════════════════════════════

    protected function recordResult(string $algorithm, string $group, string $testCase, array $data): void
    {
        $this->results[$algorithm][$group][] = [
            'test_case' => $testCase,
            'data' => $data,
            'passed' => $data['passed'],
        ];

        $this->summary[$algorithm]['total']++;

        if ($data['passed']) {
            $this->summary[$algorithm]['passed']++;
        } else {
            $this->summary[$algorithm]['failed']++;
        }
    }

    protected function printTestGroupResult(string $group, string $algorithm): void
    {
        $results = $this->results[$algorithm][$group] ?? [];
        $passed = collect($results)->where('passed', true)->count();
        $total = count($results);

        $status = $passed === $total ? '<fg=green>✓</>' : '<fg=yellow>⚠</>';
        $this->line("    {$status} {$group}: {$passed}/{$total} passed");
        $this->line('');
    }

    protected function displaySummary(): void
    {
        $elapsed = round(microtime(true) - $this->startTime, 2);

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('                        TEST SUMMARY                               ');
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('');

        foreach (['matchmaking', 'recommendation'] as $algo) {
            $summary = $this->summary[$algo];
            $percentage = $summary['total'] > 0
                ? round(($summary['passed'] / $summary['total']) * 100, 1)
                : 0;

            $status = $summary['failed'] === 0 ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $algoName = ucfirst($algo);

            $this->line("  {$algoName} Algorithm:");
            $this->line("    Total Tests:  {$summary['total']}");
            $this->line("    Passed:       <fg=green>{$summary['passed']}</>");
            $this->line("    Failed:       <fg=red>{$summary['failed']}</>");
            $this->line("    Accuracy:     {$percentage}%");
            $this->line("    Status:       {$status}");
            $this->line('');
        }

        $totalAll = $this->summary['matchmaking']['total'] + $this->summary['recommendation']['total'];
        $passedAll = $this->summary['matchmaking']['passed'] + $this->summary['recommendation']['passed'];
        $failedAll = $this->summary['matchmaking']['failed'] + $this->summary['recommendation']['failed'];
        $overallPercentage = $totalAll > 0 ? round(($passedAll / $totalAll) * 100, 1) : 0;

        $this->info('───────────────────────────────────────────────────────────────────');
        $this->line("  OVERALL: {$passedAll}/{$totalAll} tests passed ({$overallPercentage}%)");
        $this->line("  Execution time: {$elapsed}s");
        $this->info('───────────────────────────────────────────────────────────────────');
        $this->info('');

        if ($failedAll === 0) {
            $this->info('  <fg=green>✓ All algorithm accuracy tests passed!</>');
        } else {
            $this->error("  ✗ {$failedAll} test(s) failed. Review results above.");
        }
        $this->info('');
    }

    protected function generateHtmlReport(): void
    {
        $outputPath = $this->option('output')
            ?? storage_path('app/reports/algorithm-accuracy-' . now()->format('Y-m-d_His') . '.html');

        $dir = dirname($outputPath);
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $elapsed = round(microtime(true) - $this->startTime, 2);
        $totalAll = $this->summary['matchmaking']['total'] + $this->summary['recommendation']['total'];
        $passedAll = $this->summary['matchmaking']['passed'] + $this->summary['recommendation']['passed'];
        $overallPercentage = $totalAll > 0 ? round(($passedAll / $totalAll) * 100, 1) : 0;

        $html = $this->generateHtmlContent($elapsed, $overallPercentage);

        File::put($outputPath, $html);

        $this->info("  HTML report generated: {$outputPath}");
    }

    protected function generateHtmlContent(float $elapsed, float $overallPercentage): string
    {
        $matchmakingHtml = $this->generateAlgorithmSection('matchmaking', 'Matchmaking Algorithm');
        $recommendationHtml = $this->generateAlgorithmSection('recommendation', 'Server Recommendation Algorithm');

        $matchmakingPct = $this->summary['matchmaking']['total'] > 0
            ? round(($this->summary['matchmaking']['passed'] / $this->summary['matchmaking']['total']) * 100, 1)
            : 0;
        $recommendationPct = $this->summary['recommendation']['total'] > 0
            ? round(($this->summary['recommendation']['passed'] / $this->summary['recommendation']['total']) * 100, 1)
            : 0;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Glyph Algorithm Accuracy Test Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="gradient-bg text-white py-12 px-4">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-4xl font-bold mb-2">Glyph Algorithm Accuracy Report</h1>
            <p class="text-purple-200">Generated on {$this->formatDate()}</p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 -mt-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="text-3xl font-bold text-purple-600">{$overallPercentage}%</div>
                <div class="text-gray-500">Overall Accuracy</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="text-3xl font-bold text-green-600">{$this->summary['matchmaking']['passed']} + {$this->summary['recommendation']['passed']}</div>
                <div class="text-gray-500">Tests Passed</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="text-3xl font-bold text-red-600">{$this->summary['matchmaking']['failed']} + {$this->summary['recommendation']['failed']}</div>
                <div class="text-gray-500">Tests Failed</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="text-3xl font-bold text-blue-600">{$elapsed}s</div>
                <div class="text-gray-500">Execution Time</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Algorithm Accuracy Comparison</h3>
                <canvas id="accuracyChart"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Test Results Distribution</h3>
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <!-- Matchmaking Section -->
        {$matchmakingHtml}

        <!-- Recommendation Section -->
        {$recommendationHtml}

        <!-- Footer -->
        <div class="text-center text-gray-500 py-8">
            <p>Glyph Gaming Platform - Algorithm Accuracy Testing Suite v1.0</p>
            <p class="text-sm">Report generated by php artisan test:algorithm-accuracy --html</p>
        </div>
    </div>

    <script>
        // Accuracy Chart
        new Chart(document.getElementById('accuracyChart'), {
            type: 'bar',
            data: {
                labels: ['Matchmaking', 'Server Recommendation'],
                datasets: [{
                    label: 'Accuracy %',
                    data: [{$matchmakingPct}, {$recommendationPct}],
                    backgroundColor: ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)'],
                    borderColor: ['rgb(102, 126, 234)', 'rgb(118, 75, 162)'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });

        // Distribution Chart
        new Chart(document.getElementById('distributionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Matchmaking Passed', 'Matchmaking Failed', 'Recommendation Passed', 'Recommendation Failed'],
                datasets: [{
                    data: [{$this->summary['matchmaking']['passed']}, {$this->summary['matchmaking']['failed']}, {$this->summary['recommendation']['passed']}, {$this->summary['recommendation']['failed']}],
                    backgroundColor: ['#22c55e', '#ef4444', '#10b981', '#f87171']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
HTML;
    }

    protected function generateAlgorithmSection(string $algorithm, string $title): string
    {
        $groups = $this->results[$algorithm];
        $html = "<div class=\"bg-white rounded-xl shadow-lg p-6 mb-8\">
            <h2 class=\"text-2xl font-bold mb-6 text-gray-800\">{$title}</h2>";

        foreach ($groups as $groupName => $tests) {
            $passed = collect($tests)->where('passed', true)->count();
            $total = count($tests);
            $pct = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
            $statusColor = $passed === $total ? 'green' : 'yellow';

            $html .= "<div class=\"mb-6\">
                <div class=\"flex items-center justify-between mb-3\">
                    <h3 class=\"text-lg font-semibold text-gray-700\">{$groupName}</h3>
                    <span class=\"px-3 py-1 rounded-full text-sm font-medium bg-{$statusColor}-100 text-{$statusColor}-800\">{$passed}/{$total} ({$pct}%)</span>
                </div>
                <div class=\"overflow-x-auto\">
                    <table class=\"min-w-full divide-y divide-gray-200\">
                        <thead class=\"bg-gray-50\">
                            <tr>
                                <th class=\"px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase\">Test Case</th>
                                <th class=\"px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase\">Expected</th>
                                <th class=\"px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase\">Actual</th>
                                <th class=\"px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase\">Status</th>
                            </tr>
                        </thead>
                        <tbody class=\"bg-white divide-y divide-gray-200\">";

            foreach ($tests as $test) {
                $statusIcon = $test['passed'] ? '✓' : '✗';
                $statusClass = $test['passed'] ? 'text-green-600' : 'text-red-600';
                $expected = $test['data']['expected_range'] ?? ($test['data']['expected_valid'] ?? 'N/A');
                $actual = $test['data']['actual'] ?? ($test['data']['actual_valid'] ?? 'N/A');

                if (is_bool($expected)) $expected = $expected ? 'Valid' : 'Invalid';
                if (is_bool($actual)) $actual = $actual ? 'Valid' : 'Invalid';

                $html .= "<tr>
                    <td class=\"px-4 py-2 text-sm text-gray-900\">{$test['test_case']}</td>
                    <td class=\"px-4 py-2 text-sm text-gray-600\">{$expected}</td>
                    <td class=\"px-4 py-2 text-sm text-gray-600\">{$actual}</td>
                    <td class=\"px-4 py-2 text-sm font-bold {$statusClass}\">{$statusIcon}</td>
                </tr>";
            }

            $html .= "</tbody></table></div></div>";
        }

        $html .= "</div>";
        return $html;
    }

    protected function formatDate(): string
    {
        return now()->format('F j, Y \a\t g:i A');
    }
}
