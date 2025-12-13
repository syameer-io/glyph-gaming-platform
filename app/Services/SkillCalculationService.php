<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SkillCalculationService
 *
 * Calculates user skill levels based on Steam game statistics.
 * Part of the Auto-Skill Calculation feature (Phase 1).
 *
 * Supports two calculation methods:
 * 1. Enhanced: For games with detailed stats (CS2 only currently)
 *    - Uses K/D ratio, accuracy, win rate, playtime, achievements
 * 2. Fallback: For games without detailed stats (Dota 2, Warframe)
 *    - Uses playtime and achievement percentage only
 *
 * @see docs/auto-skill-calculation/01-backend-foundation.md
 */
class SkillCalculationService
{
    protected SteamApiService $steamApiService;

    /**
     * Games with enhanced stats support (GetUserStatsForGame API)
     * Maps game App ID to the calculation method name
     */
    protected array $gamesWithEnhancedStats = [
        730 => 'calculateCS2Skill',  // CS2 only - has reliable stats API
    ];

    /**
     * Supported games for skill calculation
     * Limited to 3 games as per Phase 1 requirements
     */
    protected array $supportedGames = [
        730 => 'CS2',
        570 => 'Dota 2',
        230410 => 'Warframe',
    ];

    public function __construct(SteamApiService $steamApiService)
    {
        $this->steamApiService = $steamApiService;
    }

    /**
     * Main entry point - calculate skill for a user/game combination
     *
     * Now supports ALL Steam games (not just the 3 originally supported).
     * For games with skill_metrics data, uses that data.
     * For games without skill_metrics, falls back to UserGamingPreference playtime.
     *
     * @param User $user The user to calculate skill for
     * @param string $gameAppId The Steam App ID of the game
     * @return array Contains skill_level, skill_score, and breakdown
     */
    public function calculateSkillForGame(User $user, string $gameAppId): array
    {
        // Get user's Steam data from profile
        $steamData = $user->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];
        $gameMetrics = $skillMetrics[$gameAppId] ?? null;

        // No skill_metrics data - try to calculate from gaming preference playtime
        if (!$gameMetrics) {
            Log::info("SkillCalculation: No skill_metrics for user {$user->id}, game {$gameAppId}, using preference data");
            return $this->calculateFromPreference($user, $gameAppId);
        }

        $playtimeHours = $gameMetrics['playtime_hours'] ?? 0;
        $achievementPct = $gameMetrics['achievement_percentage'] ?? 0;

        // Minimum playtime requirement (10 hours)
        if ($playtimeHours < 10) {
            Log::info("SkillCalculation: Insufficient playtime ({$playtimeHours}h) for user {$user->id}, game {$gameAppId}");
            return $this->getUnrankedResult();
        }

        // Check if game has enhanced stats available (CS2 only)
        if (isset($this->gamesWithEnhancedStats[(int)$gameAppId])) {
            $gameStats = $this->steamApiService->getGameStats($user->steam_id, (int)$gameAppId);

            if ($gameStats) {
                $method = $this->gamesWithEnhancedStats[(int)$gameAppId];
                Log::info("SkillCalculation: Using enhanced method '{$method}' for user {$user->id}, game {$gameAppId}");
                return $this->$method($gameStats, $playtimeHours, $achievementPct);
            }
        }

        // Use fallback formula for games without detailed stats
        Log::info("SkillCalculation: Using fallback method for user {$user->id}, game {$gameAppId}");
        return $this->calculateFallbackSkill($playtimeHours, $achievementPct);
    }

    /**
     * Calculate skill from UserGamingPreference when skill_metrics is unavailable
     *
     * This allows skill calculation for ALL Steam games, not just those with
     * explicit skill_metrics data. Uses the fallback formula (playtime + achievements).
     *
     * @param User $user The user to calculate skill for
     * @param string $gameAppId The Steam App ID of the game
     * @return array Contains skill_level, skill_score, and breakdown
     */
    protected function calculateFromPreference(User $user, string $gameAppId): array
    {
        $preference = $user->gamingPreferences()
            ->where('game_appid', $gameAppId)
            ->first();

        // No gaming preference record exists for this game
        if (!$preference) {
            Log::info("SkillCalculation: No preference data for user {$user->id}, game {$gameAppId}");
            return $this->getUnrankedResult();
        }

        // playtime_forever is stored in minutes
        $playtimeHours = $preference->playtime_forever / 60;

        // Minimum playtime requirement (10 hours = 600 minutes)
        if ($preference->playtime_forever < 600) {
            Log::info("SkillCalculation: Insufficient playtime ({$playtimeHours}h) from preference for user {$user->id}, game {$gameAppId}");
            return $this->getUnrankedResult();
        }

        // Achievement data may not be available for all games, default to 0
        $achievementPct = 0;

        Log::info("SkillCalculation: Using preference fallback for user {$user->id}, game {$gameAppId}, playtime={$playtimeHours}h");
        return $this->calculateFallbackSkill($playtimeHours, $achievementPct);
    }

    /**
     * Get skill breakdown for tooltip display
     *
     * @param User $user The user
     * @param string $gameAppId The game App ID
     * @return array Breakdown details for UI display
     */
    public function getSkillBreakdown(User $user, string $gameAppId): array
    {
        $result = $this->calculateSkillForGame($user, $gameAppId);
        return $result['breakdown'] ?? [];
    }

    /**
     * Check if user would be unranked for a game
     *
     * @param User $user The user
     * @param string $gameAppId The game App ID
     * @return bool True if user is unranked for this game
     */
    public function isUnranked(User $user, string $gameAppId): bool
    {
        $result = $this->calculateSkillForGame($user, $gameAppId);
        return $result['skill_level'] === 'unranked';
    }

    /**
     * Get list of supported games
     *
     * @return array Map of App ID => Game Name
     */
    public function getSupportedGames(): array
    {
        return $this->supportedGames;
    }

    /**
     * Check if a game supports enhanced skill calculation
     *
     * @param int $gameAppId The game App ID
     * @return bool True if game has enhanced stats support
     */
    public function hasEnhancedStats(int $gameAppId): bool
    {
        return isset($this->gamesWithEnhancedStats[$gameAppId]);
    }

    /**
     * Return unranked result for users without sufficient data
     *
     * @return array Unranked skill result
     */
    protected function getUnrankedResult(): array
    {
        return [
            'skill_level' => 'unranked',
            'skill_score' => null,
            'breakdown' => [
                'note' => 'No game data available. Play more to get ranked!',
            ],
        ];
    }

    /**
     * CS2 Enhanced Skill Calculation using detailed Steam stats
     *
     * Uses GetUserStatsForGame API data to calculate a weighted skill score.
     *
     * Weights:
     * - K/D Ratio: 30% (primary skill indicator)
     * - Accuracy: 20% (mechanical skill)
     * - Win Rate: 20% (game sense and teamwork)
     * - Playtime: 20% (experience)
     * - Achievements: 10% (exploration/completion)
     *
     * @param array $stats Raw stats from Steam API
     * @param float $playtimeHours Total playtime in hours
     * @param float $achievementPct Achievement completion percentage
     * @return array Calculated skill result with breakdown
     */
    protected function calculateCS2Skill(array $stats, float $playtimeHours, float $achievementPct): array
    {
        // Extract stats with safe defaults to prevent division by zero
        $kills = $stats['total_kills'] ?? 0;
        $deaths = max($stats['total_deaths'] ?? 1, 1);
        $wins = $stats['total_wins'] ?? 0;
        $rounds = max($stats['total_rounds_played'] ?? 1, 1);
        $shotsHit = $stats['total_shots_hit'] ?? 0;
        $shotsFired = max($stats['total_shots_fired'] ?? 1, 1);

        // Calculate raw performance metrics
        $kd = $kills / $deaths;
        $accuracy = ($shotsHit / $shotsFired) * 100;

        // Estimate matches from rounds (average 30 rounds per match)
        $estimatedMatches = $rounds / 30;
        $winRate = $estimatedMatches > 0 ? ($wins / $estimatedMatches) * 100 : 0;
        // Cap win rate at 100% (can exceed due to estimation)
        $winRate = min($winRate, 100);

        // Normalize each metric to 0-100 scale
        $kdScore = $this->normalizeKD($kd);
        $accuracyScore = min($accuracy * 2.5, 100); // 40% accuracy = 100 score
        $winRateScore = min($winRate, 100);
        $playtimeScore = min($playtimeHours / 10, 100); // 1000 hours = 100 score
        $achievementScore = $achievementPct;

        // Weighted combination (weights sum to 1.0)
        $finalScore =
            ($kdScore * 0.30) +
            ($accuracyScore * 0.20) +
            ($winRateScore * 0.20) +
            ($playtimeScore * 0.20) +
            ($achievementScore * 0.10);

        Log::info("SkillCalculation CS2: KD={$kd}, Acc={$accuracy}%, WR={$winRate}%, Score={$finalScore}");

        return [
            'skill_score' => round($finalScore, 1),
            'skill_level' => $this->convertScoreToLevel($finalScore),
            'calculation_method' => 'enhanced',
            'breakdown' => [
                'kd_ratio' => round($kd, 2),
                'kd_score' => round($kdScore, 1),
                'accuracy' => round($accuracy, 1),
                'accuracy_score' => round($accuracyScore, 1),
                'win_rate' => round($winRate, 1),
                'win_rate_score' => round($winRateScore, 1),
                'playtime_hours' => round($playtimeHours, 1),
                'playtime_score' => round($playtimeScore, 1),
                'achievements' => round($achievementPct, 1),
                'achievement_score' => round($achievementScore, 1),
                'weights' => [
                    'kd' => 0.30,
                    'accuracy' => 0.20,
                    'win_rate' => 0.20,
                    'playtime' => 0.20,
                    'achievements' => 0.10,
                ],
            ],
        ];
    }

    /**
     * Fallback skill calculation using playtime + achievements only
     *
     * Used for Warframe and Dota 2 where detailed stats aren't available
     * via the Steam API (GetUserStatsForGame not reliable for these games).
     *
     * Formula:
     * - Playtime: 60% weight (max 60 points at 1000 hours)
     * - Achievements: 40% weight (max 40 points at 100%)
     *
     * @param float $playtimeHours Total playtime in hours
     * @param float $achievementPct Achievement completion percentage
     * @return array Calculated skill result with breakdown
     */
    protected function calculateFallbackSkill(float $playtimeHours, float $achievementPct): array
    {
        // Playtime contribution (max 60 points at 1000 hours)
        // Uses linear scaling: 1 hour = 0.06 points
        $playtimeScore = min($playtimeHours / 1000 * 60, 60);

        // Achievement contribution (max 40 points at 100%)
        // Direct percentage mapping: 1% = 0.4 points
        $achievementScore = $achievementPct * 0.4;

        $finalScore = round($playtimeScore + $achievementScore, 1);

        Log::info("SkillCalculation Fallback: Playtime={$playtimeHours}h, Achievements={$achievementPct}%, Score={$finalScore}");

        return [
            'skill_score' => $finalScore,
            'skill_level' => $this->convertScoreToLevel($finalScore),
            'calculation_method' => 'fallback',
            'breakdown' => [
                'playtime_hours' => round($playtimeHours, 1),
                'playtime_score' => round($playtimeScore, 1),
                'achievements' => round($achievementPct, 1),
                'achievement_score' => round($achievementScore, 1),
                'note' => 'Detailed stats not available for this game. Using playtime and achievements.',
                'weights' => [
                    'playtime' => 0.60,
                    'achievements' => 0.40,
                ],
            ],
        ];
    }

    /**
     * Normalize K/D ratio to 0-100 score using non-linear curve
     *
     * The curve is designed to:
     * - Reward improvement at lower K/D levels more
     * - Flatten out at higher levels (diminishing returns)
     * - Cap at 3.0 K/D = 100 score
     *
     * Thresholds:
     * - K/D < 1.0: Score 0-50 (below average)
     * - K/D 1.0-2.0: Score 50-85 (average to good)
     * - K/D 2.0-3.0: Score 85-100 (very good to excellent)
     * - K/D >= 3.0: Score 100 (cap)
     *
     * @param float $kd The K/D ratio
     * @return float Normalized score 0-100
     */
    protected function normalizeKD(float $kd): float
    {
        if ($kd >= 3.0) {
            return 100;
        }
        if ($kd >= 2.0) {
            // 2.0 to 3.0 maps to 85-100 (15 point range)
            return 85 + (($kd - 2.0) * 15);
        }
        if ($kd >= 1.0) {
            // 1.0 to 2.0 maps to 50-85 (35 point range)
            return 50 + (($kd - 1.0) * 35);
        }
        // 0 to 1.0 maps to 0-50
        return max(0, $kd * 50);
    }

    /**
     * Convert numerical score to skill level string
     *
     * Thresholds align with the MatchmakingService skill categories:
     * - Expert: 80-100 (top tier players)
     * - Advanced: 60-79 (experienced players)
     * - Intermediate: 40-59 (regular players)
     * - Beginner: 0-39 (new players)
     *
     * @param float $score The calculated skill score (0-100)
     * @return string Skill level enum value
     */
    protected function convertScoreToLevel(float $score): string
    {
        return match(true) {
            $score >= 80 => 'expert',
            $score >= 60 => 'advanced',
            $score >= 40 => 'intermediate',
            default => 'beginner',
        };
    }

    /**
     * Batch calculate skills for multiple games
     *
     * Useful for displaying skill levels across all games a user plays.
     *
     * @param User $user The user
     * @return array Map of game App ID => skill result
     */
    public function calculateAllGameSkills(User $user): array
    {
        $results = [];

        foreach ($this->supportedGames as $appId => $gameName) {
            $results[$appId] = [
                'game_name' => $gameName,
                ...$this->calculateSkillForGame($user, (string)$appId),
            ];
        }

        return $results;
    }

    /**
     * Get cached skill calculation result
     *
     * Uses 5-minute cache to avoid recalculating on every request.
     *
     * @param User $user The user
     * @param string $gameAppId The game App ID
     * @return array Cached or fresh skill result
     */
    public function getCachedSkill(User $user, string $gameAppId): array
    {
        $cacheKey = "skill_calc_{$user->id}_{$gameAppId}";

        return Cache::remember($cacheKey, 300, function () use ($user, $gameAppId) {
            return $this->calculateSkillForGame($user, $gameAppId);
        });
    }

    /**
     * Invalidate cached skill calculation
     *
     * Should be called when user's Steam data is refreshed.
     *
     * @param User $user The user
     * @param string|null $gameAppId Specific game or null for all games
     */
    public function invalidateCache(User $user, ?string $gameAppId = null): void
    {
        if ($gameAppId) {
            Cache::forget("skill_calc_{$user->id}_{$gameAppId}");
        } else {
            foreach (array_keys($this->supportedGames) as $appId) {
                Cache::forget("skill_calc_{$user->id}_{$appId}");
            }
        }
    }
}
