<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * UserStatus Model
 *
 * Phase 2: Member List Enhancement
 * Manages Discord-style user status (online, idle, dnd, offline) with custom status support.
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string|null $custom_text
 * @property string|null $custom_emoji
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class UserStatus extends Model
{
    use HasFactory;

    /**
     * Status constants for type safety.
     */
    public const STATUS_ONLINE = 'online';
    public const STATUS_IDLE = 'idle';
    public const STATUS_DND = 'dnd';
    public const STATUS_OFFLINE = 'offline';

    /**
     * Valid status values.
     */
    public const VALID_STATUSES = [
        self::STATUS_ONLINE,
        self::STATUS_IDLE,
        self::STATUS_DND,
        self::STATUS_OFFLINE,
    ];

    /**
     * Status colors for UI rendering.
     */
    public const STATUS_COLORS = [
        self::STATUS_ONLINE => '#43b581',
        self::STATUS_IDLE => '#faa61a',
        self::STATUS_DND => '#f04747',
        self::STATUS_OFFLINE => '#747f8d',
    ];

    /**
     * Status display names.
     */
    public const STATUS_LABELS = [
        self::STATUS_ONLINE => 'Online',
        self::STATUS_IDLE => 'Idle',
        self::STATUS_DND => 'Do Not Disturb',
        self::STATUS_OFFLINE => 'Offline',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'custom_text',
        'custom_emoji',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the user that owns this status.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // Status Helper Methods
    // ==========================================

    /**
     * Check if the custom status has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (is_null($this->expires_at)) {
            return false;
        }

        return Carbon::now()->gt($this->expires_at);
    }

    /**
     * Check if user has a custom status set (not expired).
     *
     * @return bool
     */
    public function hasCustomStatus(): bool
    {
        if (empty($this->custom_text) && empty($this->custom_emoji)) {
            return false;
        }

        return !$this->isExpired();
    }

    /**
     * Get the display text for the status.
     *
     * @return string
     */
    public function getDisplayStatus(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    /**
     * Get the color for the status indicator.
     *
     * @return string
     */
    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? self::STATUS_COLORS[self::STATUS_OFFLINE];
    }

    /**
     * Get the full custom status string (emoji + text).
     *
     * @return string|null
     */
    public function getFullCustomStatus(): ?string
    {
        if (!$this->hasCustomStatus()) {
            return null;
        }

        $parts = [];

        if (!empty($this->custom_emoji)) {
            $parts[] = $this->custom_emoji;
        }

        if (!empty($this->custom_text)) {
            $parts[] = $this->custom_text;
        }

        return implode(' ', $parts);
    }

    // ==========================================
    // Static Helper Methods
    // ==========================================

    /**
     * Set status for a user (create or update).
     *
     * @param int $userId
     * @param string $status
     * @return self
     */
    public static function setStatus(int $userId, string $status): self
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return self::updateOrCreate(
            ['user_id' => $userId],
            ['status' => $status]
        );
    }

    /**
     * Set custom status for a user.
     *
     * @param int $userId
     * @param string|null $text
     * @param string|null $emoji
     * @param Carbon|null $expiresAt
     * @return self
     */
    public static function setCustomStatus(
        int $userId,
        ?string $text = null,
        ?string $emoji = null,
        ?Carbon $expiresAt = null
    ): self {
        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'custom_text' => $text,
                'custom_emoji' => $emoji,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Clear custom status for a user.
     *
     * @param int $userId
     * @return bool
     */
    public static function clearCustomStatus(int $userId): bool
    {
        $status = self::where('user_id', $userId)->first();

        if (!$status) {
            return false;
        }

        $status->update([
            'custom_text' => null,
            'custom_emoji' => null,
            'expires_at' => null,
        ]);

        return true;
    }

    /**
     * Clear all expired custom statuses (for scheduled cleanup).
     *
     * @return int Number of statuses cleared
     */
    public static function clearExpiredStatuses(): int
    {
        return self::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update([
                'custom_text' => null,
                'custom_emoji' => null,
                'expires_at' => null,
            ]);
    }

    /**
     * Get status for a user with fallback to default.
     *
     * @param int $userId
     * @return self
     */
    public static function getOrCreate(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['status' => self::STATUS_OFFLINE]
        );
    }

    /**
     * Set multiple users to offline status.
     * Useful for cleanup when users disconnect.
     *
     * @param array $userIds
     * @return int Number of records updated
     */
    public static function setMultipleOffline(array $userIds): int
    {
        return self::whereIn('user_id', $userIds)
            ->where('status', '!=', self::STATUS_OFFLINE)
            ->update(['status' => self::STATUS_OFFLINE]);
    }

    /**
     * Get online users count.
     *
     * @return int
     */
    public static function getOnlineCount(): int
    {
        return self::where('status', '!=', self::STATUS_OFFLINE)->count();
    }

    /**
     * Scope for active (non-offline) users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_OFFLINE);
    }

    /**
     * Scope for users with custom status.
     */
    public function scopeWithCustomStatus($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('custom_text')
              ->orWhereNotNull('custom_emoji');
        })->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }
}
