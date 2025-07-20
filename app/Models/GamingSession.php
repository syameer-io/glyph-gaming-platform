<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamingSession extends Model
{
    protected $fillable = [
        'user_id',
        'game_appid',
        'game_name',
        'started_at',
        'ended_at',
        'duration_minutes',
        'session_data',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'session_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate session duration when ending
     */
    public function calculateDuration(): int
    {
        if (!$this->ended_at || !$this->started_at) {
            return 0;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Mark session as completed
     */
    public function complete(): bool
    {
        $this->ended_at = now();
        $this->duration_minutes = $this->calculateDuration();
        $this->status = 'completed';
        
        return $this->save();
    }

    /**
     * Mark session as abandoned (user quit unexpectedly)
     */
    public function abandon(): bool
    {
        $this->ended_at = now();
        $this->duration_minutes = $this->calculateDuration();
        $this->status = 'abandoned';
        
        return $this->save();
    }

    /**
     * Get active session for user and game
     */
    public static function getActiveSession(int $userId, string $gameAppId): ?self
    {
        return static::where('user_id', $userId)
            ->where('game_appid', $gameAppId)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
    }

    /**
     * Get user's gaming statistics
     */
    public static function getUserStats(int $userId, int $days = 30): array
    {
        $sessions = static::where('user_id', $userId)
            ->where('started_at', '>=', now()->subDays($days))
            ->where('status', '!=', 'active')
            ->get();

        $totalMinutes = $sessions->sum('duration_minutes');
        $totalSessions = $sessions->count();
        $gamesPlayed = $sessions->pluck('game_appid')->unique()->count();

        return [
            'total_sessions' => $totalSessions,
            'total_hours' => round($totalMinutes / 60, 1),
            'average_session_minutes' => $totalSessions > 0 ? round($totalMinutes / $totalSessions) : 0,
            'games_played' => $gamesPlayed,
            'most_played_game' => $sessions->groupBy('game_appid')
                ->map(fn($group) => [
                    'appid' => $group->first()->game_appid,
                    'name' => $group->first()->game_name,
                    'sessions' => $group->count(),
                    'total_minutes' => $group->sum('duration_minutes'),
                ])
                ->sortByDesc('total_minutes')
                ->first(),
        ];
    }
}
