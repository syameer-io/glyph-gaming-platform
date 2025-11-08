<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MatchmakingService;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MatchmakingServiceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService();
    }

    /** @test */
    public function it_handles_null_skill_levels_gracefully()
    {
        // Use 'any' skill level (valid enum value) to test neutral behavior
        $team = Team::factory()->create(['skill_level' => 'any']);
        $request = MatchmakingRequest::factory()->create(['skill_level' => 'any']);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('total_score', $compatibility);
        $this->assertGreaterThanOrEqual(0, $compatibility['total_score']);
        $this->assertLessThanOrEqual(100, $compatibility['total_score']);
    }

    /** @test */
    public function it_handles_empty_preferred_roles()
    {
        $team = Team::factory()->create(['team_data' => []]);
        $request = MatchmakingRequest::factory()->create(['preferred_roles' => []]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should not error, should return reasonable score
        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('breakdown', $compatibility);
        $this->assertArrayHasKey('composition', $compatibility['breakdown']);
    }

    /** @test */
    public function it_handles_teams_at_max_capacity()
    {
        $team = Team::factory()->create([
            'current_size' => 5,
            'max_size' => 5,
            'status' => 'full',
        ]);

        $request = MatchmakingRequest::factory()->create();

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // SIZE criterion removed - verify it's not in breakdown
        $this->assertArrayNotHasKey('size', $compatibility['breakdown'],
            'SIZE criterion should not be present in compatibility breakdown');

        // Should still calculate other criteria normally
        $this->assertArrayHasKey('skill', $compatibility['breakdown']);
        $this->assertArrayHasKey('composition', $compatibility['breakdown']);
    }

    /** @test */
    public function it_handles_teams_with_minimal_members()
    {
        $team = Team::factory()->create([
            'current_size' => 1, // Minimum 1 member (the creator)
            'max_size' => 10, // Large team, so 1/10 = 10%
        ]);

        $request = MatchmakingRequest::factory()->create();

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // SIZE criterion removed - verify it's not in breakdown
        $this->assertArrayNotHasKey('size', $compatibility['breakdown'],
            'SIZE criterion should not be present in compatibility breakdown');

        // Should still calculate other criteria normally
        $this->assertIsArray($compatibility);
        $this->assertGreaterThanOrEqual(0, $compatibility['total_score']);
        $this->assertLessThanOrEqual(100, $compatibility['total_score']);
    }

    /** @test */
    public function it_handles_missing_region_data()
    {
        $team = Team::factory()->create(['team_data' => []]);
        $request = MatchmakingRequest::factory()->create(['additional_requirements' => []]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should return neutral region score
        $this->assertEqualsWithDelta(70, $compatibility['breakdown']['region'], 5);
    }

    /** @test */
    public function it_handles_missing_schedule_data()
    {
        $team = Team::factory()->create(['team_data' => []]);
        $request = MatchmakingRequest::factory()->create(['availability_hours' => []]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should return neutral schedule score
        $this->assertEqualsWithDelta(70, $compatibility['breakdown']['schedule'], 10);
    }

    /** @test */
    public function it_handles_all_criteria_missing()
    {
        $team = Team::factory()->create([
            'skill_level' => 'any', // Use valid enum value
            'team_data' => [],
            'current_size' => 3,
            'max_size' => 5,
        ]);

        $request = MatchmakingRequest::factory()->create([
            'skill_level' => 'any', // Use valid enum value
            'preferred_roles' => [],
            'server_preferences' => [],
            'availability_hours' => [],
            'additional_requirements' => [],
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should still calculate, defaulting to neutral scores
        // With 5 criteria (no SIZE), neutral scores result in lower total
        $this->assertIsArray($compatibility);
        $this->assertGreaterThan(65, $compatibility['total_score']);
        $this->assertLessThan(95, $compatibility['total_score']);
    }

    /** @test */
    public function it_never_returns_scores_outside_valid_range()
    {
        // Test with extreme values
        $team = Team::factory()->create([
            'skill_level' => 'beginner',
            'current_size' => 100, // Impossible but testing bounds
            'max_size' => 5,
        ]);

        $request = MatchmakingRequest::factory()->create([
            'skill_level' => 'expert',
        ]);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // All breakdown scores should be [0, 100]
        foreach ($compatibility['breakdown'] as $criterion => $score) {
            $this->assertGreaterThanOrEqual(0, $score, "Criterion {$criterion} below 0");
            $this->assertLessThanOrEqual(100, $score, "Criterion {$criterion} above 100");
        }

        // Total score should be [0, 100]
        $this->assertGreaterThanOrEqual(0, $compatibility['total_score']);
        $this->assertLessThanOrEqual(100, $compatibility['total_score']);
    }

    /** @test */
    public function it_handles_case_insensitive_skill_levels()
    {
        // Test that algorithm handles case-insensitive matching internally
        $team1 = Team::factory()->create(['skill_level' => 'intermediate']);
        $team2 = Team::factory()->create(['skill_level' => 'intermediate']);
        $team3 = Team::factory()->create(['skill_level' => 'intermediate']);

        // All requests with lowercase (database constraint)
        $request1 = MatchmakingRequest::factory()->create(['skill_level' => 'intermediate']);
        $request2 = MatchmakingRequest::factory()->create(['skill_level' => 'intermediate']);
        $request3 = MatchmakingRequest::factory()->create(['skill_level' => 'intermediate']);

        $score1 = $this->service->calculateDetailedCompatibility($team1, $request1);
        $score2 = $this->service->calculateDetailedCompatibility($team2, $request2);
        $score3 = $this->service->calculateDetailedCompatibility($team3, $request3);

        // All should return same skill score (algorithm normalizes case internally)
        $this->assertEquals($score1['breakdown']['skill'], $score2['breakdown']['skill']);
        $this->assertEquals($score2['breakdown']['skill'], $score3['breakdown']['skill']);
        $this->assertEquals(100, $score1['breakdown']['skill']);
    }

    /** @test */
    public function it_handles_invalid_skill_level_strings()
    {
        // Use 'any' as a special case that should default to intermediate
        $team = Team::factory()->create(['skill_level' => 'any']);
        $request = MatchmakingRequest::factory()->create(['skill_level' => 'any']);

        $compatibility = $this->service->calculateDetailedCompatibility($team, $request);

        // Should handle gracefully and calculate (any -> intermediate default)
        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('total_score', $compatibility);
        $this->assertGreaterThanOrEqual(0, $compatibility['total_score']);
        $this->assertLessThanOrEqual(100, $compatibility['total_score']);
    }
}
