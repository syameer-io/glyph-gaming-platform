<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Server;
use App\Services\PermissionService;

class ServerPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * General server management (settings, name, description).
     */
    public function manage(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_server', $server);
    }

    /**
     * Channel management (create, edit, delete).
     */
    public function manageChannels(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_channels', $server);
    }

    /**
     * Role management (create, edit, assign roles).
     */
    public function manageRoles(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_roles', $server);
    }

    /**
     * Member management (mute, manage nicknames).
     */
    public function manageMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_members', $server);
    }

    /**
     * Kick members from the server.
     */
    public function kickMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'kick_members', $server);
    }

    /**
     * Ban members from the server.
     */
    public function banMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'ban_members', $server);
    }

    /**
     * Mute members in the server.
     */
    public function muteMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'mute_members', $server);
    }

    /**
     * Admin access (for legacy compatibility - uses manage_server).
     */
    public function admin(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_server', $server);
    }

    /**
     * View the server (must be a member and have view_channels).
     */
    public function view(User $user, Server $server): bool
    {
        $isMember = $server->members()->where('user_id', $user->id)->exists();

        if (!$isMember) {
            return false;
        }

        return $this->permissionService->check($user, 'view_channels', $server);
    }

    /**
     * Send messages in channels.
     */
    public function sendMessages(User $user, Server $server): bool
    {
        $membership = $server->members()->where('user_id', $user->id)->first();

        // Check if banned
        if (!$membership || $membership->pivot->is_banned) {
            return false;
        }

        // Check if muted
        if ($membership->pivot->is_muted) {
            return false;
        }

        return $this->permissionService->check($user, 'send_messages', $server);
    }

    /**
     * Manage messages (delete/pin others' messages).
     */
    public function manageMessages(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'manage_messages', $server);
    }

    /**
     * Connect to voice channels.
     */
    public function connectVoice(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'connect', $server);
    }

    /**
     * Speak in voice channels.
     */
    public function speakVoice(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'speak', $server);
    }

    /**
     * Mute members in voice channels.
     */
    public function muteVoiceMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'mute_voice_members', $server);
    }

    /**
     * Move members between voice channels.
     */
    public function moveMembers(User $user, Server $server): bool
    {
        return $this->permissionService->check($user, 'move_members', $server);
    }
}
