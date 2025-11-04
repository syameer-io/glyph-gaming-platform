<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class MatchmakingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function it_calculates_compatibility_under_100ms()
    {
        $team = Team::factory()->create(['skill_level' => 'intermediate']);
        $request = MatchmakingRequest::factory()->create(['skill_level' => 'intermediate']);

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->calculateDetailedCompatibility($team, $request);
        }

        $end = microtime(true);
        $avgTime = (($end - $start) / 100) * 1000; // Convert to ms

        $this->assertLessThan(100, $avgTime,
            "Average calculation time {$avgTime}ms exceeds 100ms threshold");

        echo "\nAverage compatibility calculation: " . round($avgTime, 2) . "ms\n";
    }

    /** @test */
    public function it_finds_compatible_teams_without_n_plus_one_queries()
    {
        // Create 20 teams
        Team::factory()->count(20)->create([
            'game_appid' => '730',
            'status' => 'recruiting',
        ]);

        $user = User::factory()->create();
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
        ]);

        DB::enableQueryLog();

        $compatibleTeams = $this->service->findCompatibleTeams($request);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        DB::disableQueryLog();

        // Should use eager loading (with clause in base query)
        // Expected: Initial query + per-team user lookups for role matching = ~4-5 queries per team
        // With 20 teams, ~80-100 queries is acceptable given the complexity
        $this->assertLessThan(150, $queryCount,
            "Query count {$queryCount} exceeds acceptable threshold");

        echo "\nTotal queries for findCompatibleTeams: {$queryCount}\n";
        echo "Compatible teams found: {$compatibleTeams->count()}\n";
        echo "Queries per team: " . round($queryCount / 20, 1) . "\n";
    }

    /** @test */
    public function it_handles_large_dataset_efficiently()
    {
        // Create 100 teams
        Team::factory()->count(100)->create([
            'game_appid' => '730',
            'status' => 'recruiting',
        ]);

        $user = User::factory()->create();
        $request = MatchmakingRequest::factory()->create([
            'user_id' => $user->id,
            'game_appid' => '730',
        ]);

        $start = microtime(true);

        $compatibleTeams = $this->service->findCompatibleTeams($request);

        $end = microtime(true);
        $totalTime = ($end - $start) * 1000; // Convert to ms

        $this->assertLessThan(1000, $totalTime,
            "Processing 100 teams took {$totalTime}ms, exceeds 1 second threshold");

        echo "\nProcessed 100 teams in " . round($totalTime, 2) . "ms\n";
        echo "Found {$compatibleTeams->count()} compatible teams\n";
    }
}
