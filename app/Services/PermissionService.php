<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PermissionService
 *
 * Centralized service for permission-related operations.
 * Provides a clean interface for checking permissions, managing caches,
 * and validating permission configurations.
 *
 * This service follows the established patterns in the Glyph codebase:
 * - Log::info() for START markers on main operations
 * - No HTTP concerns (delegated to controllers)
 * - Return consistent response structures
 *
 * @package Glyph
 * @since Phase 1 - Role Permissions System
 */
class PermissionService
{
    /**
     * Check if a user has a specific permission in a server.
     *
     * This is the primary method for permission checks. It handles:
     * - Server creator bypass (always true)
     * - Administrator permission bypass
     * - Role aggregation
     * - Channel-specific overrides
     * - Caching
     *
     * @param User $user The user to check
     * @param string $permission The permission key
     * @param Server $server The server context
     * @param Channel|null $channel Optional channel for channel-specific overrides
     * @return bool
     */
    public function check(User $user, string $permission, Server $server, ?Channel $channel = null): bool
    {
        Log::info('PermissionService::check START', [
            'user_id' => $user->id,
            'permission' => $permission,
            'server_id' => $server->id,
            'channel_id' => $channel?->id,
        ]);

        return $user->hasServerPermission(
            $permission,
            $server->id,
            $channel?->id
        );
    }

    /**
     * Check if a user has any of the specified permissions.
     *
     * @param User $user The user to check
     * @param array $permissions Array of permission keys
     * @param Server $server The server context
     * @param Channel|null $channel Optional channel for overrides
     * @return bool True if user has at least one permission
     */
    public function checkAny(User $user, array $permissions, Server $server, ?Channel $channel = null): bool
    {
        Log::info('PermissionService::checkAny START', [
            'user_id' => $user->id,
            'permissions' => $permissions,
            'server_id' => $server->id,
            'channel_id' => $channel?->id,
        ]);

        return $user->hasAnyServerPermission(
            $permissions,
            $server->id,
            $channel?->id
        );
    }

    /**
     * Check if a user has all of the specified permissions.
     *
     * @param User $user The user to check
     * @param array $permissions Array of permission keys
     * @param Server $server The server context
     * @param Channel|null $channel Optional channel for overrides
     * @return bool True if user has all permissions
     */
    public function checkAll(User $user, array $permissions, Server $server, ?Channel $channel = null): bool
    {
        Log::info('PermissionService::checkAll START', [
            'user_id' => $user->id,
            'permissions' => $permissions,
            'server_id' => $server->id,
            'channel_id' => $channel?->id,
        ]);

        return $user->hasAllServerPermissions(
            $permissions,
            $server->id,
            $channel?->id
        );
    }

    /**
     * Get all permissions a user has in a server.
     *
     * @param User $user The user
     * @param Server $server The server
     * @param Channel|null $channel Optional channel for overrides
     * @return array Array of permission keys
     */
    public function getUserPermissions(User $user, Server $server, ?Channel $channel = null): array
    {
        Log::info('PermissionService::getUserPermissions START', [
            'user_id' => $user->id,
            'server_id' => $server->id,
            'channel_id' => $channel?->id,
        ]);

        // Server creator has all permissions
        if ($server->creator_id === $user->id) {
            return $this->getAllPermissionKeys();
        }

        return $user->computeServerPermissions($server->id, $channel?->id);
    }

    /**
     * Check if a user can manage another user based on hierarchy.
     *
     * @param User $actor The user performing the action
     * @param User $target The user being acted upon
     * @param Server $server The server context
     * @return bool
     */
    public function canManageUser(User $actor, User $target, Server $server): bool
    {
        Log::info('PermissionService::canManageUser START', [
            'actor_id' => $actor->id,
            'target_id' => $target->id,
            'server_id' => $server->id,
        ]);

        return $actor->canManageUser($target, $server->id);
    }

    /**
     * Check if a user can manage a role based on hierarchy.
     *
     * @param User $user The user
     * @param Role $role The role to manage
     * @return bool
     */
    public function canManageRole(User $user, Role $role): bool
    {
        Log::info('PermissionService::canManageRole START', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'server_id' => $role->server_id,
        ]);

        return $user->canManageRole($role, $role->server_id);
    }

    /**
     * Get the full permission configuration.
     *
     * @return array The permissions config
     */
    public function getPermissionConfig(): array
    {
        return config('permissions', []);
    }

    /**
     * Get all valid permission keys.
     *
     * @return array Array of permission keys
     */
    public function getAllPermissionKeys(): array
    {
        return config('permissions.all', []);
    }

    /**
     * Get permission categories with their permissions.
     *
     * @return array Categories with nested permissions
     */
    public function getPermissionCategories(): array
    {
        return config('permissions.categories', []);
    }

    /**
     * Get dangerous permission keys.
     *
     * @return array Array of dangerous permission keys
     */
    public function getDangerousPermissions(): array
    {
        return config('permissions.dangerous', []);
    }

    /**
     * Check if a permission is marked as dangerous.
     *
     * @param string $permission The permission key
     * @return bool
     */
    public function isDangerous(string $permission): bool
    {
        return in_array($permission, $this->getDangerousPermissions());
    }

    /**
     * Validate an array of permissions against the configured list.
     * Returns only valid permissions, filtering out any invalid ones.
     *
     * @param array $permissions Array of permission keys to validate
     * @return array Array containing 'valid' and 'invalid' permission arrays
     */
    public function validatePermissions(array $permissions): array
    {
        $validPermissions = $this->getAllPermissionKeys();

        $valid = array_intersect($permissions, $validPermissions);
        $invalid = array_diff($permissions, $validPermissions);

        return [
            'valid' => array_values($valid),
            'invalid' => array_values($invalid),
        ];
    }

    /**
     * Get default permissions for a role type.
     *
     * @param string $roleType One of: 'server_admin', 'member'
     * @return array Array of permission keys
     */
    public function getDefaultPermissions(string $roleType): array
    {
        return config("permissions.defaults.{$roleType}", []);
    }

    /**
     * Invalidate all permission caches for a server.
     * Should be called when server-wide permission changes occur.
     *
     * @param Server $server The server
     * @return void
     */
    public function invalidateServerCache(Server $server): void
    {
        Log::info('PermissionService::invalidateServerCache START', [
            'server_id' => $server->id,
        ]);

        // Clear server-level cache
        Cache::forget("server_{$server->id}_permissions");

        // Clear caches for all members
        $memberIds = $server->members()->pluck('users.id');

        foreach ($memberIds as $userId) {
            Cache::forget("user_{$userId}_server_{$server->id}_permissions");
            Cache::forget("user_{$userId}_server_{$server->id}_channel_permissions");

            // Clear channel-specific caches
            foreach ($server->channels as $channel) {
                Cache::forget("user_{$userId}_server_{$server->id}_channel_{$channel->id}_permissions");
            }
        }

        Log::info('PermissionService::invalidateServerCache COMPLETE', [
            'server_id' => $server->id,
            'members_invalidated' => $memberIds->count(),
        ]);
    }

    /**
     * Invalidate permission cache for a specific user in a server.
     *
     * @param User $user The user
     * @param Server $server The server
     * @return void
     */
    public function invalidateUserCache(User $user, Server $server): void
    {
        Log::info('PermissionService::invalidateUserCache START', [
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);

        $user->invalidatePermissionCache($server->id);
    }

    /**
     * Get a formatted list of permissions for display in UI.
     * Groups permissions by category with labels and metadata.
     *
     * @return array Formatted permissions grouped by category
     */
    public function getFormattedPermissions(): array
    {
        $categories = $this->getPermissionCategories();
        $formatted = [];

        foreach ($categories as $categoryKey => $category) {
            $formattedPermissions = [];

            foreach ($category['permissions'] as $permKey => $permission) {
                $formattedPermissions[] = [
                    'key' => $permKey,
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                    'dangerous' => $permission['dangerous'] ?? false,
                    'default' => $permission['default'] ?? false,
                ];
            }

            $formatted[] = [
                'key' => $categoryKey,
                'label' => $category['label'],
                'description' => $category['description'],
                'permissions' => $formattedPermissions,
            ];
        }

        // Add administrator permission at the top
        array_unshift($formatted, [
            'key' => 'special',
            'label' => 'Special',
            'description' => 'Special permissions with elevated privileges',
            'permissions' => [
                [
                    'key' => 'administrator',
                    'label' => 'Administrator',
                    'description' => 'Grants all permissions. Members with this permission can perform any action regardless of other permission settings. This is a dangerous permission.',
                    'dangerous' => true,
                    'default' => false,
                ],
            ],
        ]);

        return $formatted;
    }

    /**
     * Check if a user is the server creator.
     *
     * @param User $user The user
     * @param Server $server The server
     * @return bool
     */
    public function isServerCreator(User $user, Server $server): bool
    {
        return $server->creator_id === $user->id;
    }

    /**
     * Check if a user has the administrator permission in a server.
     *
     * @param User $user The user
     * @param Server $server The server
     * @return bool
     */
    public function isAdministrator(User $user, Server $server): bool
    {
        return $user->hasServerPermission(
            config('permissions.administrator', 'administrator'),
            $server->id
        );
    }

    /**
     * Get the permission label for display.
     *
     * @param string $permission The permission key
     * @return string The label or the key if not found
     */
    public function getPermissionLabel(string $permission): string
    {
        if ($permission === 'administrator') {
            return 'Administrator';
        }

        $categories = $this->getPermissionCategories();

        foreach ($categories as $category) {
            if (isset($category['permissions'][$permission])) {
                return $category['permissions'][$permission]['label'];
            }
        }

        return $permission;
    }

    /**
     * Get the permission description.
     *
     * @param string $permission The permission key
     * @return string The description or empty string if not found
     */
    public function getPermissionDescription(string $permission): string
    {
        if ($permission === 'administrator') {
            return 'Grants all permissions. Members with this permission can perform any action regardless of other permission settings.';
        }

        $categories = $this->getPermissionCategories();

        foreach ($categories as $category) {
            if (isset($category['permissions'][$permission])) {
                return $category['permissions'][$permission]['description'];
            }
        }

        return '';
    }
}
