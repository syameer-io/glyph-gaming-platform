<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Role Model
 *
 * Represents a role within a server with associated permissions.
 * Roles use a position-based hierarchy where higher position = more authority.
 *
 * @package Glyph
 * @since Phase 1 - Role Permissions System (enhanced)
 *
 * @property int $id
 * @property int $server_id
 * @property string $name
 * @property string|null $color
 * @property int $position
 * @property array|null $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Server $server
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|ChannelPermissionOverride[] $channelOverrides
 */
class Role extends Model
{
    use HasFactory;

    /**
     * Protected role names that cannot be deleted.
     */
    public const PROTECTED_ROLES = ['Server Admin', 'Member'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'name',
        'color',
        'position',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'position' => 'integer',
        'server_id' => 'integer',
    ];

    /**
     * Boot the model.
     * Registers event listeners for cache invalidation.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Invalidate permission caches when role is saved (created or updated)
        static::saved(function (Role $role) {
            $role->invalidateUserCaches();
            Cache::forget("server_{$role->server_id}_permissions");
        });

        // Invalidate permission caches when role is deleted
        static::deleted(function (Role $role) {
            $role->invalidateUserCaches();
            Cache::forget("server_{$role->server_id}_permissions");
        });
    }

    /**
     * Get the server that owns the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the users that have this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('server_id')
            ->withTimestamps();
    }

    /**
     * Get the channel permission overrides for this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channelOverrides()
    {
        return $this->hasMany(ChannelPermissionOverride::class);
    }

    /**
     * Check if this role has a specific permission.
     *
     * The administrator permission grants all permissions.
     *
     * @param string $permission The permission key to check
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        // Administrator bypasses all permission checks
        if (in_array(config('permissions.administrator', 'administrator'), $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Grant a permission to this role.
     *
     * @param string $permission The permission key to grant
     * @return bool True if permission was added, false if already had it
     */
    public function grantPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array($permission, $permissions)) {
            return false;
        }

        $permissions[] = $permission;
        $this->permissions = array_values(array_unique($permissions));
        $this->save();

        return true;
    }

    /**
     * Revoke a permission from this role.
     *
     * @param string $permission The permission key to revoke
     * @return bool True if permission was removed, false if didn't have it
     */
    public function revokePermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (!in_array($permission, $permissions)) {
            return false;
        }

        $this->permissions = array_values(array_diff($permissions, [$permission]));
        $this->save();

        return true;
    }

    /**
     * Set the complete list of permissions for this role.
     * Validates permissions against the configured permission list.
     *
     * @param array $permissions Array of permission keys
     * @return void
     * @throws \InvalidArgumentException If invalid permissions are provided
     */
    public function setPermissions(array $permissions): void
    {
        // Get all valid permissions from config
        $validPermissions = config('permissions.all', []);

        // Validate each provided permission
        $invalidPermissions = array_diff($permissions, $validPermissions);
        if (!empty($invalidPermissions)) {
            throw new \InvalidArgumentException(
                'Invalid permissions: ' . implode(', ', $invalidPermissions)
            );
        }

        $this->permissions = array_values(array_unique($permissions));
        $this->save();
    }

    /**
     * Check if this is a protected role that cannot be deleted.
     *
     * Protected roles are: "Server Admin" and "Member"
     *
     * @return bool
     */
    public function isProtected(): bool
    {
        return in_array($this->name, self::PROTECTED_ROLES);
    }

    /**
     * Check if this role has the administrator permission.
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->hasPermission(config('permissions.administrator', 'administrator'));
    }

    /**
     * Invalidate permission caches for all users who have this role.
     * Called when role permissions change.
     *
     * @return void
     */
    public function invalidateUserCaches(): void
    {
        $serverId = $this->server_id;

        // Get all users who have this role in this server
        $userIds = $this->users()
            ->wherePivot('server_id', $serverId)
            ->pluck('users.id');

        foreach ($userIds as $userId) {
            // Invalidate the user's permission cache for this server
            Cache::forget("user_{$userId}_server_{$serverId}_permissions");
            // Also invalidate channel-specific caches
            Cache::forget("user_{$userId}_server_{$serverId}_channel_permissions");
        }
    }

    /**
     * Get all permission keys this role has.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Check if this role has any of the specified permissions.
     *
     * @param array $permissions Array of permission keys
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Administrator has all permissions
        if ($this->isAdministrator()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this role has all of the specified permissions.
     *
     * @param array $permissions Array of permission keys
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        // Administrator has all permissions
        if ($this->isAdministrator()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the channel permission overrides for a specific channel.
     *
     * @param int $channelId The channel ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChannelOverrides(int $channelId)
    {
        return $this->channelOverrides()
            ->where('channel_id', $channelId)
            ->get();
    }

    /**
     * Get the effective permission for this role on a specific channel.
     * Considers channel overrides.
     *
     * @param string $permission The permission key
     * @param int|null $channelId The channel ID (null for server-wide)
     * @return bool
     */
    public function getEffectivePermission(string $permission, ?int $channelId = null): bool
    {
        // Check channel override first if channelId is provided
        if ($channelId !== null) {
            $override = $this->channelOverrides()
                ->where('channel_id', $channelId)
                ->where('permission', $permission)
                ->first();

            if ($override) {
                if ($override->isDeny()) {
                    return false;
                }
                if ($override->isAllow()) {
                    return true;
                }
                // isInherit falls through to role default
            }
        }

        // Fall back to role's default permission
        return $this->hasPermission($permission);
    }
}