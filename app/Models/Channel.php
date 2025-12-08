<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Channel Model
 *
 * Represents a text or voice channel within a server.
 *
 * @package Glyph
 *
 * @property int $id
 * @property int $server_id
 * @property string $name
 * @property string $type
 * @property int $position
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Server $server
 * @property-read \Illuminate\Database\Eloquent\Collection|Message[] $messages
 * @property-read \Illuminate\Database\Eloquent\Collection|ChannelPermissionOverride[] $permissionOverrides
 */
class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'name',
        'type',
        'position',
    ];

    /**
     * Get the server that owns the channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the messages in the channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the permission overrides for this channel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissionOverrides()
    {
        return $this->hasMany(ChannelPermissionOverride::class);
    }

    /**
     * Get the permission override value for a specific role and permission.
     *
     * Returns:
     * - 'allow' if explicitly allowed for this channel
     * - 'deny' if explicitly denied for this channel
     * - null if no override exists (should inherit from role's default)
     *
     * @param Role $role The role to check
     * @param string $permission The permission key
     * @return string|null The override value ('allow', 'deny') or null for inherit
     */
    public function getPermissionForRole(Role $role, string $permission): ?string
    {
        $override = $this->permissionOverrides()
            ->where('role_id', $role->id)
            ->where('permission', $permission)
            ->first();

        if (!$override || $override->isInherit()) {
            return null;
        }

        return $override->value;
    }

    /**
     * Get all permission overrides for a specific role on this channel.
     *
     * @param Role $role The role to get overrides for
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverridesForRole(Role $role)
    {
        return $this->permissionOverrides()
            ->where('role_id', $role->id)
            ->get();
    }

    /**
     * Set a permission override for a role on this channel.
     *
     * @param Role $role The role to set override for
     * @param string $permission The permission key
     * @param string $value One of: 'allow', 'deny', 'inherit'
     * @return ChannelPermissionOverride
     */
    public function setPermissionOverride(Role $role, string $permission, string $value): ChannelPermissionOverride
    {
        return ChannelPermissionOverride::setOverride(
            $this->id,
            $role->id,
            $permission,
            $value
        );
    }

    /**
     * Remove a permission override for a role on this channel.
     *
     * @param Role $role The role to remove override for
     * @param string $permission The permission key
     * @return bool
     */
    public function removePermissionOverride(Role $role, string $permission): bool
    {
        return ChannelPermissionOverride::removeOverride(
            $this->id,
            $role->id,
            $permission
        );
    }
}