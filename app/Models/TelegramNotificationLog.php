<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramNotificationLog extends Model
{
    use HasFactory;

    // Notification type constants
    public const TYPE_GOAL_COMPLETED = 'goal_completed';
    public const TYPE_GOAL_PROGRESS = 'goal_progress';
    public const TYPE_NEW_GOAL = 'new_goal';
    public const TYPE_USER_JOINED = 'user_joined';
    public const TYPE_MILESTONE_REACHED = 'milestone_reached';
    public const TYPE_TEAM_CREATED = 'team_created';
    public const TYPE_TEAM_MEMBER_JOINED = 'team_member_joined';
    public const TYPE_TEAM_MEMBER_LEFT = 'team_member_left';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'notification_type',
        'recipient_chat_id',
        'message_preview',
        'delivery_status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get all available notification types.
     */
    public static function getNotificationTypes(): array
    {
        return [
            self::TYPE_GOAL_COMPLETED,
            self::TYPE_GOAL_PROGRESS,
            self::TYPE_NEW_GOAL,
            self::TYPE_USER_JOINED,
            self::TYPE_MILESTONE_REACHED,
            self::TYPE_TEAM_CREATED,
            self::TYPE_TEAM_MEMBER_JOINED,
            self::TYPE_TEAM_MEMBER_LEFT,
        ];
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_FAILED,
            self::STATUS_SKIPPED,
        ];
    }

    /**
     * Get the server that owns this notification log.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Scope a query to only include logs of a specific type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope a query to only include logs from the last N days.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include successful (sent) logs.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('delivery_status', self::STATUS_SENT);
    }

    /**
     * Scope a query to only include failed logs.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('delivery_status', self::STATUS_FAILED);
    }

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_GOAL_COMPLETED => 'Goal Completed',
            self::TYPE_GOAL_PROGRESS => 'Goal Progress',
            self::TYPE_NEW_GOAL => 'New Goal',
            self::TYPE_USER_JOINED => 'User Joined Goal',
            self::TYPE_MILESTONE_REACHED => 'Milestone Reached',
            self::TYPE_TEAM_CREATED => 'Team Created',
            self::TYPE_TEAM_MEMBER_JOINED => 'Member Joined Team',
            self::TYPE_TEAM_MEMBER_LEFT => 'Member Left Team',
        ];

        return $labels[$this->notification_type] ?? ucfirst(str_replace('_', ' ', $this->notification_type));
    }

    /**
     * Get the emoji for the notification type.
     */
    public function getTypeEmojiAttribute(): string
    {
        $emojis = [
            self::TYPE_GOAL_COMPLETED => '&#x1F3C6;', // Trophy
            self::TYPE_GOAL_PROGRESS => '&#x1F4C8;', // Chart increasing
            self::TYPE_NEW_GOAL => '&#x1F3AF;', // Target
            self::TYPE_USER_JOINED => '&#x1F3AE;', // Game controller
            self::TYPE_MILESTONE_REACHED => '&#x1F396;', // Medal
            self::TYPE_TEAM_CREATED => '&#x1F465;', // Busts in silhouette
            self::TYPE_TEAM_MEMBER_JOINED => '&#x1F389;', // Party popper
            self::TYPE_TEAM_MEMBER_LEFT => '&#x1F44B;', // Wave
        ];

        return $emojis[$this->notification_type] ?? '&#x1F4E8;'; // Envelope
    }

    /**
     * Get the color class for the status.
     */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_PENDING => '#f59e0b', // Amber/Warning
            self::STATUS_SENT => '#10b981', // Green/Success
            self::STATUS_FAILED => '#ef4444', // Red/Error
            self::STATUS_SKIPPED => '#71717a', // Gray/Muted
        ];

        return $colors[$this->delivery_status] ?? '#71717a';
    }

    /**
     * Log a notification.
     *
     * @param int $serverId
     * @param string $type
     * @param string $chatId
     * @param string $message
     * @param bool $success
     * @param string|null $errorMessage
     * @param array|null $metadata
     * @return static
     */
    public static function logNotification(
        int $serverId,
        string $type,
        string $chatId,
        string $message,
        bool $success,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): self {
        // Truncate message preview to 200 characters
        $messagePreview = mb_strlen($message) > 200
            ? mb_substr($message, 0, 197) . '...'
            : $message;

        // Strip HTML tags for preview
        $messagePreview = strip_tags($messagePreview);

        return self::create([
            'server_id' => $serverId,
            'notification_type' => $type,
            'recipient_chat_id' => $chatId,
            'message_preview' => $messagePreview,
            'delivery_status' => $success ? self::STATUS_SENT : self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'sent_at' => $success ? now() : null,
        ]);
    }

    /**
     * Get server notification statistics.
     *
     * @param int $serverId
     * @param int $days
     * @return array
     */
    public static function getServerStats(int $serverId, int $days = 30): array
    {
        $query = self::where('server_id', $serverId)
            ->where('created_at', '>=', now()->subDays($days));

        $total = $query->count();
        $sent = (clone $query)->where('delivery_status', self::STATUS_SENT)->count();
        $failed = (clone $query)->where('delivery_status', self::STATUS_FAILED)->count();
        $skipped = (clone $query)->where('delivery_status', self::STATUS_SKIPPED)->count();

        // Get counts by type
        $byType = self::where('server_id', $serverId)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('notification_type, COUNT(*) as count')
            ->groupBy('notification_type')
            ->pluck('count', 'notification_type')
            ->toArray();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'delivery_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
            'by_type' => $byType,
        ];
    }
}
