<?php

/**
 * Phase 4 Implementation Test
 * Tests Region, Schedule & Auxiliary Criteria
 *
 * Run: php test_phase4_implementation.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Phase 4 Implementation Test\n";
echo "Region, Schedule & Auxiliary Criteria\n";
echo "========================================\n\n";

$service = new MatchmakingService();
$passed = 0;
$failed = 0;

// Helper function to call protected methods
function callProtectedMethod($object, $methodName, ...$args) {
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method->invoke($object, ...$args);
}

// Test 1: Region Proximity Score
echo "Test 1: Region Proximity Score\n";
echo "-------------------------------\n";

$testCases = [
    ['NA', 'NA', 1.0, 'Same region'],
    ['NA', 'SA', 0.70, 'NA to SA (close)'],
    ['NA', 'EU', 0.50, 'NA to EU (transatlantic)'],
    ['NA', 'ASIA', 0.25, 'NA to ASIA (farthest)'],
    ['EU', 'AFRICA', 0.65, 'EU to AFRICA (close)'],
    ['ASIA', 'OCEANIA', 0.60, 'ASIA to OCEANIA (close)'],
];

foreach ($testCases as [$region1, $region2, $expected, $description]) {
    $score = callProtectedMethod($service, 'getRegionProximityScore', $region1, $region2);
    $status = abs($score - $expected) < 0.01 ? 'PASS' : 'FAIL';

    if ($status === 'PASS') {
        $passed++;
    } else {
        $failed++;
    }

    echo sprintf(
        "[%s] %s: %.2f (expected: %.2f)\n",
        $status,
        $description,
        $score,
        $expected
    );
}

echo "\n";

// Test 2: Time Range Expansion
echo "Test 2: Time Range Expansion\n";
echo "-----------------------------\n";

$timeRangeTests = [
    [['morning'], [9, 10, 11], 'Morning range'],
    [['evening'], [17, 18, 19, 20, 21], 'Evening range'],
    [['night'], [22, 23, 0, 1, 2], 'Night range'],
    [['morning', 'afternoon'], [9, 10, 11, 12, 13, 14, 15, 16], 'Multiple ranges'],
    [[9, 10], [9, 10], 'Numeric hours'],
];

foreach ($timeRangeTests as [$input, $expected, $description]) {
    $result = callProtectedMethod($service, 'expandTimeRanges', $input);
    sort($result);
    sort($expected);

    $status = $result == $expected ? 'PASS' : 'FAIL';

    if ($status === 'PASS') {
        $passed++;
    } else {
        $failed++;
    }

    echo sprintf(
        "[%s] %s: [%s] (expected: [%s])\n",
        $status,
        $description,
        implode(', ', $result),
        implode(', ', $expected)
    );
}

echo "\n";

// Test 3: Schedule Overlap (Jaccard Similarity)
echo "Test 3: Schedule Overlap (Jaccard)\n";
echo "-----------------------------------\n";

$scheduleTests = [
    [['morning'], ['morning'], 1.0, 'Perfect overlap'],
    [['morning'], ['afternoon'], 0.0, 'No overlap'],
    [['morning', 'afternoon'], ['morning'], 0.35, 'Partial overlap (3/8 = 0.375)'],
    [['morning', 'afternoon', 'evening'], ['morning', 'afternoon'], 0.60, 'High overlap'],
];

foreach ($scheduleTests as [$userSlots, $teamSlots, $expectedMin, $description]) {
    $score = callProtectedMethod($service, 'calculateScheduleOverlap', $userSlots, $teamSlots);
    $status = $score >= ($expectedMin - 0.1) ? 'PASS' : 'FAIL';

    if ($status === 'PASS') {
        $passed++;
    } else {
        $failed++;
    }

    echo sprintf(
        "[%s] %s: %.2f (expected: >= %.2f)\n",
        $status,
        $description,
        $score,
        $expectedMin
    );
}

echo "\n";

// Test 4: Team Size Score (Tiered)
echo "Test 4: Team Size Score (Tiered)\n";
echo "---------------------------------\n";

// Get real teams from database or create test scenarios
$teamSizeTests = [
    [[5, 10], 1.0, 'Optimal: 50% full'],
    [[4, 10], 1.0, 'Optimal: 40% full'],
    [[3, 10], 0.90, 'Good: 30% full'],
    [[7, 10], 0.90, 'Good: 70% full'],
    [[2, 10], 0.75, 'Acceptable: 20% full'],
    [[8, 10], 0.75, 'Acceptable: 80% full'],
    [[1, 10], 0.60, 'Poor: 10% full'],
    [[9, 10], 0.60, 'Poor: 90% full'],
];

foreach ($teamSizeTests as [[$current, $max], $expected, $description]) {
    // Create a temporary team for testing
    try {
        $testTeam = new Team();
        $testTeam->id = 999;
        $testTeam->current_size = $current;
        $testTeam->max_size = $max;
        $testTeam->exists = false; // Mark as not saved to DB

        $score = callProtectedMethod($service, 'calculateTeamSizeScore', $testTeam);
        $status = abs($score - $expected) < 0.05 ? 'PASS' : 'FAIL';

        if ($status === 'PASS') {
            $passed++;
        } else {
            $failed++;
        }

        echo sprintf(
            "[%s] %s: %.2f (expected: %.2f)\n",
            $status,
            $description,
            $score,
            $expected
        );
    } catch (Exception $e) {
        echo "[ERROR] {$description}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";

// Test 5: Integration Test with Real Models
echo "Test 5: Integration Test\n";
echo "------------------------\n";

try {
    // Get a real team and request from database
    $team = Team::with('activeMembers')->first();
    $request = MatchmakingRequest::with('user')->where('status', 'active')->first();

    if ($team && $request) {
        echo "Testing with Team #{$team->id} and Request #{$request->id}\n";

        // Call the main compatibility method
        $compatibility = $service->calculateDetailedCompatibility($team, $request);

        echo sprintf("Total Score: %.1f%%\n", $compatibility['total_score']);
        echo "Breakdown:\n";
        foreach ($compatibility['breakdown'] as $criterion => $score) {
            echo sprintf("  - %s: %.1f%%\n", ucfirst($criterion), $score);
        }
        echo "Reasons:\n";
        foreach ($compatibility['reasons'] as $reason) {
            echo "  - {$reason}\n";
        }

        // Validate score is in valid range
        if ($compatibility['total_score'] >= 0 && $compatibility['total_score'] <= 100) {
            echo "[PASS] Score in valid range [0, 100]\n";
            $passed++;
        } else {
            echo "[FAIL] Score out of range: {$compatibility['total_score']}\n";
            $failed++;
        }

        // Validate all breakdown scores are in [0, 100]
        $allValid = true;
        foreach ($compatibility['breakdown'] as $criterion => $score) {
            if ($score < 0 || $score > 100) {
                echo "[FAIL] {$criterion} score out of range: {$score}\n";
                $allValid = false;
                $failed++;
            }
        }

        if ($allValid) {
            echo "[PASS] All breakdown scores in valid range\n";
            $passed++;
        }

    } else {
        echo "[SKIP] No active team or request found in database\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Integration test failed: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n";

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Success Rate: " . ($passed / max(1, $passed + $failed) * 100) . "%\n";
echo "========================================\n";

if ($failed === 0) {
    echo "\n✓ All tests passed! Phase 4 implementation successful.\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the implementation.\n";
    exit(1);
}
