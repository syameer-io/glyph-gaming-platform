<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingAnalytics extends Model
{
    protected $fillable = [
        'matchmaking_request_id',
        'team_id',
        'compatibility_score',
        'score_breakdown',
        'was_accepted',
        'configuration_used',
        'match_shown_at',
        'user_action_at',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'was_accepted' => 'boolean',
        'match_shown_at' => 'datetime',
        'user_action_at' => 'datetime',
    ];

    /**
     * Get the matchmaking request associated with this analytic
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(MatchmakingRequest::class, 'matchmaking_request_id');
    }

    /**
     * Get the team associated with this analytic
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get success rate for a configuration
     *
     * @param string $configName Configuration name
     * @return float Percentage of matches that were accepted
     */
    public static function getSuccessRate(string $configName): float
    {
        $total = self::where('configuration_used', $configName)->count();

        if ($total === 0) {
            return 0.0;
        }

        $accepted = self::where('configuration_used', $configName)
            ->where('was_accepted', true)
            ->count();

        return ($accepted / $total) * 100;
    }

    /**
     * Get average compatibility score by criterion
     *
     * @param string $configName Configuration name
     * @return array Average scores per criterion
     */
    public static function getAverageBreakdown(string $configName): array
    {
        $analytics = self::where('configuration_used', $configName)->get();

        if ($analytics->isEmpty()) {
            return [];
        }

        $sums = [];
        $counts = [];

        foreach ($analytics as $analytic) {
            foreach ($analytic->score_breakdown as $criterion => $score) {
                $sums[$criterion] = ($sums[$criterion] ?? 0) + $score;
                $counts[$criterion] = ($counts[$criterion] ?? 0) + 1;
            }
        }

        $averages = [];
        foreach ($sums as $criterion => $sum) {
            $averages[$criterion] = round($sum / $counts[$criterion], 1);
        }

        return $averages;
    }

    /**
     * Get total matches for a configuration
     *
     * @param string $configName Configuration name
     * @return int Total number of matches
     */
    public static function getTotalMatches(string $configName): int
    {
        return self::where('configuration_used', $configName)->count();
    }

    /**
     * Get average response time (time between match shown and user action)
     *
     * @param string $configName Configuration name
     * @return float Average response time in seconds
     */
    public static function getAverageResponseTime(string $configName): float
    {
        $analytics = self::where('configuration_used', $configName)
            ->whereNotNull('match_shown_at')
            ->whereNotNull('user_action_at')
            ->get();

        if ($analytics->isEmpty()) {
            return 0.0;
        }

        $totalSeconds = 0;
        foreach ($analytics as $analytic) {
            $totalSeconds += $analytic->match_shown_at->diffInSeconds($analytic->user_action_at);
        }

        return $totalSeconds / $analytics->count();
    }
}
