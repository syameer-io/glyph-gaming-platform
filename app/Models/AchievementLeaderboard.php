<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementLeaderboard extends Model
{
    protected $fillable = [
        'server_id',
        'user_id',
        'game_appid',
        'game_name',
        'achievement_count',
        'total_achievements',
        'completion_percentage',
        'rank_position',
        'rank_change',
        'skill_score',
        'playtime_hours',
        'leaderboard_data',
        'last_updated',
        'season',
    ];

    protected $casts = [
        'completion_percentage' => 'decimal:2',
        'skill_score' => 'decimal:2',
        'playtime_hours' => 'decimal:2',
        'leaderboard_data' => 'array',
        'last_updated' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate comprehensive ranking score
     */
    public function calculateRankingScore(): float
    {
        $achievementWeight = 0.4;
        $skillWeight = 0.3;
        $playtimeWeight = 0.2;
        $activityWeight = 0.1;

        // Achievement score (normalized to 100)
        $achievementScore = $this->total_achievements > 0 
            ? ($this->achievement_count / $this->total_achievements) * 100
            : 0;

        // Skill score (already 0-100)
        $skillScore = $this->skill_score ?? 50;

        // Playtime score (logarithmic scaling, max at 1000 hours = 100 points)
        $playtimeScore = min(100, log($this->playtime_hours + 1) * 21.7); // log(1000+1) * 21.7 ≈ 100

        // Activity score (based on last updated - more recent = higher score)
        $daysSinceUpdate = $this->last_updated ? now()->diffInDays($this->last_updated) : 30;
        $activityScore = max(0, 100 - ($daysSinceUpdate * 3.33)); // 30 days = 0 points

        return ($achievementScore * $achievementWeight) +
               ($skillScore * $skillWeight) +
               ($playtimeScore * $playtimeWeight) +
               ($activityScore * $activityWeight);
    }

    /**
     * Update user's leaderboard position for a game
     */
    public static function updateUserPosition(User $user, string $gameAppId, Server $server): void
    {
        $steamData = $user->profile->steam_data ?? [];
        $achievements = $steamData['achievements'][$gameAppId] ?? [];
        $games = collect($steamData['games'] ?? []);
        $gameData = $games->firstWhere('appid', $gameAppId);

        if (!$gameData) {
            return; // User doesn't have this game
        }

        $entry = self::updateOrCreate([
            'server_id' => $server->id,
            'user_id' => $user->id,
            'game_appid' => $gameAppId,
        ], [
            'game_name' => $gameData['name'] ?? 'Unknown Game',
            'achievement_count' => $achievements['unlocked'] ?? 0,
            'total_achievements' => $achievements['total'] ?? 1,
            'completion_percentage' => $achievements['total'] > 0 
                ? (($achievements['unlocked'] ?? 0) / $achievements['total']) * 100 
                : 0,
            'skill_score' => $steamData['skill_metrics'][$gameAppId]['skill_score'] ?? 50,
            'playtime_hours' => ($gameData['playtime_forever'] ?? 0) / 60,
            'leaderboard_data' => [
                'recent_achievements' => $achievements['recent'] ?? [],
                'rare_achievements' => $achievements['rare'] ?? [],
                'last_played' => $gameData['rtime_last_played'] ?? null,
            ],
            'last_updated' => now(),
            'season' => now()->format('Y-m'),
        ]);

        // Recalculate rankings for this server and game
        self::recalculateRankings($server, $gameAppId);
    }

    /**
     * Recalculate rankings for a server and game
     */
    public static function recalculateRankings(Server $server, string $gameAppId): void
    {
        $entries = self::where('server_id', $server->id)
                      ->where('game_appid', $gameAppId)
                      ->get();

        // Calculate ranking scores and sort
        $rankedEntries = $entries->map(function ($entry) {
            $entry->ranking_score = $entry->calculateRankingScore();
            return $entry;
        })->sortByDesc('ranking_score')->values();

        // Update rank positions
        DB::transaction(function () use ($rankedEntries) {
            foreach ($rankedEntries as $index => $entry) {
                $newRank = $index + 1;
                $rankChange = $entry->rank_position ? ($entry->rank_position - $newRank) : 0;
                
                $entry->update([
                    'rank_position' => $newRank,
                    'rank_change' => $rankChange,
                ]);
            }
        });
    }

    /**
     * Get top players for a server and game
     */
    public static function getTopPlayers(Server $server, string $gameAppId, int $limit = 10): Collection
    {
        return self::where('server_id', $server->id)
                   ->where('game_appid', $gameAppId)
                   ->orderBy('rank_position')
                   ->with('user')
                   ->take($limit)
                   ->get()
                   ->map(function ($entry) {
                       return [
                           'rank' => $entry->rank_position,
                           'user' => $entry->user,
                           'achievement_count' => $entry->achievement_count,
                           'completion_percentage' => $entry->completion_percentage,
                           'skill_score' => $entry->skill_score,
                           'playtime_hours' => $entry->playtime_hours,
                           'rank_change' => $entry->rank_change,
                           'ranking_score' => $entry->calculateRankingScore(),
                       ];
                   });
    }

    /**
     * Get server-wide achievement leaders
     */
    public static function getServerLeaders(Server $server, int $limit = 10): Collection
    {
        return self::where('server_id', $server->id)
                   ->select('user_id', 'users.name', 'users.avatar_url')
                   ->selectRaw('SUM(achievement_count) as total_achievements')
                   ->selectRaw('AVG(completion_percentage) as avg_completion')
                   ->selectRaw('AVG(skill_score) as avg_skill_score')
                   ->selectRaw('SUM(playtime_hours) as total_playtime')
                   ->selectRaw('COUNT(DISTINCT game_appid) as games_played')
                   ->join('users', 'users.id', '=', 'achievement_leaderboards.user_id')
                   ->groupBy('user_id', 'users.name', 'users.avatar_url')
                   ->orderByDesc('total_achievements')
                   ->orderByDesc('avg_completion')
                   ->take($limit)
                   ->get()
                   ->map(function ($entry, $index) {
                       return [
                           'rank' => $index + 1,
                           'user_id' => $entry->user_id,
                           'name' => $entry->name,
                           'avatar_url' => $entry->avatar_url,
                           'total_achievements' => $entry->total_achievements,
                           'avg_completion' => round($entry->avg_completion, 1),
                           'avg_skill_score' => round($entry->avg_skill_score, 1),
                           'total_playtime' => round($entry->total_playtime, 1),
                           'games_played' => $entry->games_played,
                       ];
                   });
    }

    /**
     * Get achievement statistics for a server
     */
    public static function getServerStats(Server $server): array
    {
        $stats = self::where('server_id', $server->id)
                    ->selectRaw('
                        COUNT(DISTINCT user_id) as total_players,
                        COUNT(DISTINCT game_appid) as total_games,
                        SUM(achievement_count) as total_achievements,
                        AVG(completion_percentage) as avg_completion,
                        MAX(completion_percentage) as max_completion,
                        SUM(playtime_hours) as total_playtime
                    ')
                    ->first();

        $topGame = self::where('server_id', $server->id)
                      ->select('game_name')
                      ->selectRaw('COUNT(*) as player_count')
                      ->groupBy('game_appid', 'game_name')
                      ->orderByDesc('player_count')
                      ->first();

        $recentAchievers = self::where('server_id', $server->id)
                              ->where('last_updated', '>=', now()->subDays(7))
                              ->count();

        return [
            'total_players' => $stats->total_players ?? 0,
            'total_games' => $stats->total_games ?? 0,
            'total_achievements' => $stats->total_achievements ?? 0,
            'avg_completion' => round($stats->avg_completion ?? 0, 1),
            'max_completion' => round($stats->max_completion ?? 0, 1),
            'total_playtime' => round($stats->total_playtime ?? 0, 1),
            'top_game' => $topGame->game_name ?? 'None',
            'recent_achievers' => $recentAchievers,
        ];
    }

    /**
     * Get user's ranking in a specific game
     */
    public function getUserGameRank(User $user, string $gameAppId, Server $server): ?array
    {
        $entry = self::where('server_id', $server->id)
                    ->where('user_id', $user->id)
                    ->where('game_appid', $gameAppId)
                    ->first();

        if (!$entry) {
            return null;
        }

        $totalPlayers = self::where('server_id', $server->id)
                           ->where('game_appid', $gameAppId)
                           ->count();

        return [
            'rank' => $entry->rank_position,
            'total_players' => $totalPlayers,
            'percentile' => $totalPlayers > 0 ? round((($totalPlayers - $entry->rank_position + 1) / $totalPlayers) * 100, 1) : 0,
            'achievement_count' => $entry->achievement_count,
            'completion_percentage' => $entry->completion_percentage,
            'skill_score' => $entry->skill_score,
            'rank_change' => $entry->rank_change,
        ];
    }

    /**
     * Get rank badge class for UI
     */
    public function getRankBadgeClass(): string
    {
        return match(true) {
            $this->rank_position <= 3 => 'bg-yellow-100 text-yellow-800', // Gold
            $this->rank_position <= 10 => 'bg-gray-100 text-gray-800',   // Silver
            $this->rank_position <= 25 => 'bg-orange-100 text-orange-800', // Bronze
            default => 'bg-blue-100 text-blue-800' // Regular
        };
    }

    /**
     * Get rank change indicator
     */
    public function getRankChangeIndicator(): string
    {
        return match(true) {
            $this->rank_change > 0 => '↑ +' . $this->rank_change,
            $this->rank_change < 0 => '↓ ' . $this->rank_change,
            default => '→ 0'
        };
    }

    /**
     * Get rank change color
     */
    public function getRankChangeColor(): string
    {
        return match(true) {
            $this->rank_change > 0 => 'text-green-500',
            $this->rank_change < 0 => 'text-red-500',
            default => 'text-gray-500'
        };
    }

    /**
     * Scopes
     */
    public function scopeByGame($query, string $gameAppId)
    {
        return $query->where('game_appid', $gameAppId);
    }

    public function scopeByServer($query, Server $server)
    {
        return $query->where('server_id', $server->id);
    }

    public function scopeTopRanked($query, int $limit = 10)
    {
        return $query->orderBy('rank_position')->take($limit);
    }

    public function scopeRecentlyUpdated($query, int $days = 7)
    {
        return $query->where('last_updated', '>=', now()->subDays($days));
    }

    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeHighCompletion($query, float $minPercentage = 80)
    {
        return $query->where('completion_percentage', '>=', $minPercentage);
    }
}
