<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * ChannelPermissionOverride Model
 *
 * Represents a permission override for a specific role on a specific channel.
 * This allows fine-grained control where a role's server-wide permissions
 * can be modified on a per-channel basis.
 *
 * Override values:
 * - 'allow': Explicitly grants the permission for this channel
 * - 'deny': Explicitly denies the permission for this channel (takes precedence)
 * - 'inherit': Uses the role's default server-wide permission
 *
 * @package Glyph
 * @since Phase 1 - Role Permissions System
 *
 * @property int $id
 * @property int $channel_id
 * @property int $role_id
 * @property string $permission
 * @property string $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Channel $channel
 * @property-read Role $role
 */
class ChannelPermissionOverride extends Model
{
    use HasFactory;

    /**
     * Override value constants
     */
    public const VALUE_ALLOW = 'allow';
    public const VALUE_DENY = 'deny';
    public const VALUE_INHERIT = 'inherit';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'channel_id',
        'role_id',
        'permission',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'channel_id' => 'integer',
        'role_id' => 'integer',
    ];

    /**
     * Valid override values.
     *
     * @var array<int, string>
     */
    public static array $validValues = [
        self::VALUE_ALLOW,
        self::VALUE_DENY,
        self::VALUE_INHERIT,
    ];

    /**
     * Boot the model.
     * Registers event listeners for cache invalidation.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Invalidate caches when override is created
        static::created(function (ChannelPermissionOverride $override) {
            $override->invalidateRelatedCaches();
        });

        // Invalidate caches when override is updated
        static::updated(function (ChannelPermissionOverride $override) {
            $override->invalidateRelatedCaches();
        });

        // Invalidate caches when override is deleted
        static::deleted(function (ChannelPermissionOverride $override) {
            $override->invalidateRelatedCaches();
        });
    }

    /**
     * Get the channel that owns the override.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the role that owns the override.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if this override allows the permission.
     *
     * @return bool
     */
    public function isAllow(): bool
    {
        return $this->value === self::VALUE_ALLOW;
    }

    /**
     * Check if this override denies the permission.
     *
     * @return bool
     */
    public function isDeny(): bool
    {
        return $this->value === self::VALUE_DENY;
    }

    /**
     * Check if this override inherits from role default.
     *
     * @return bool
     */
    public function isInherit(): bool
    {
        return $this->value === self::VALUE_INHERIT;
    }

    /**
     * Invalidate all permission caches related to this override.
     * This includes caches for all users with the affected role.
     */
    public function invalidateRelatedCaches(): void
    {
        // Get the server ID through the channel relationship
        $channel = $this->channel;
        if (!$channel) {
            return;
        }

        $serverId = $channel->server_id;

        // Invalidate cache for all users who have this role in this server
        $role = $this->role;
        if ($role) {
            $role->invalidateUserCaches();
        }

        // Invalidate server-wide permission cache
        Cache::forget("server_{$serverId}_permissions");

        // Invalidate channel-specific permission cache
        Cache::forget("channel_{$this->channel_id}_permissions");
    }

    /**
     * Create or update an override for a channel-role-permission combination.
     *
     * @param int $channelId
     * @param int $roleId
     * @param string $permission
     * @param string $value One of: allow, deny, inherit
     * @return static
     */
    public static function setOverride(int $channelId, int $roleId, string $permission, string $value): static
    {
        return static::updateOrCreate(
            [
                'channel_id' => $channelId,
                'role_id' => $roleId,
                'permission' => $permission,
            ],
            [
                'value' => $value,
            ]
        );
    }

    /**
     * Remove an override (or set it to inherit).
     *
     * @param int $channelId
     * @param int $roleId
     * @param string $permission
     * @return bool
     */
    public static function removeOverride(int $channelId, int $roleId, string $permission): bool
    {
        return static::where('channel_id', $channelId)
            ->where('role_id', $roleId)
            ->where('permission', $permission)
            ->delete() > 0;
    }

    /**
     * Get all overrides for a channel.
     *
     * @param int $channelId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getChannelOverrides(int $channelId)
    {
        return static::where('channel_id', $channelId)
            ->with('role')
            ->get();
    }

    /**
     * Get all overrides for a role.
     *
     * @param int $roleId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRoleOverrides(int $roleId)
    {
        return static::where('role_id', $roleId)
            ->with('channel')
            ->get();
    }
}
