<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Voice Session Model
 *
 * Tracks user voice chat sessions in voice channels using Agora.io WebRTC.
 * Records session duration, mute status, and channel participation.
 */
class VoiceSession extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'channel_id',
        'server_id',
        'agora_channel_name',
        'joined_at',
        'left_at',
        'is_muted',
        'session_duration',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_muted' => 'boolean',
        'session_duration' => 'integer',
    ];

    /**
     * Get the user who owns the voice session.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the channel where the voice session is happening.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the server where the voice session is happening.
     *
     * @return BelongsTo
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get formatted session duration as "Xh Ym" or "Xm Ys".
     *
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->session_duration) {
            return '0s';
        }

        $hours = floor($this->session_duration / 3600);
        $minutes = floor(($this->session_duration % 3600) / 60);
        $seconds = $this->session_duration % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    /**
     * Check if the session is currently active (user has not left).
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->left_at);
    }

    /**
     * Scope a query to only include active sessions (not left yet).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    /**
     * Scope a query to only include sessions for a specific channel.
     *
     * @param Builder $query
     * @param int $channelId
     * @return Builder
     */
    public function scopeForChannel(Builder $query, int $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope a query to only include sessions for a specific user.
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Calculate and set session duration when user leaves.
     *
     * @return void
     */
    public function calculateDuration(): void
    {
        if ($this->left_at && $this->joined_at) {
            $this->session_duration = $this->joined_at->diffInSeconds($this->left_at);
        }
    }

    /**
     * Mark the session as ended and calculate duration.
     *
     * @return bool
     */
    public function endSession(): bool
    {
        $this->left_at = now();
        $this->calculateDuration();

        return $this->save();
    }

    /**
     * Toggle mute status for the session.
     *
     * @return bool
     */
    public function toggleMute(): bool
    {
        $this->is_muted = !$this->is_muted;

        return $this->save();
    }

    /**
     * Get active session for a specific user and channel.
     *
     * @param int $userId
     * @param int $channelId
     * @return VoiceSession|null
     */
    public static function getActiveUserSession(int $userId, int $channelId): ?self
    {
        return static::active()
            ->forUser($userId)
            ->forChannel($channelId)
            ->latest('joined_at')
            ->first();
    }

    /**
     * Get all active sessions for a channel with user data.
     *
     * @param int $channelId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getChannelActiveSessions(int $channelId)
    {
        return static::active()
            ->forChannel($channelId)
            ->with('user')
            ->orderBy('joined_at', 'asc')
            ->get();
    }

    /**
     * Get user's total voice chat statistics.
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    public static function getUserStats(int $userId, int $days = 30): array
    {
        $sessions = static::forUser($userId)
            ->whereNotNull('left_at')
            ->where('joined_at', '>=', now()->subDays($days))
            ->get();

        $totalDuration = $sessions->sum('session_duration');
        $totalSessions = $sessions->count();
        $totalHours = round($totalDuration / 3600, 1);
        $averageMinutes = $totalSessions > 0 ? round($totalDuration / $totalSessions / 60) : 0;

        return [
            'total_sessions' => $totalSessions,
            'total_hours' => $totalHours,
            'total_minutes' => round($totalDuration / 60),
            'average_session_minutes' => $averageMinutes,
            'channels_joined' => $sessions->pluck('channel_id')->unique()->count(),
            'servers_joined' => $sessions->pluck('server_id')->unique()->count(),
        ];
    }
}
