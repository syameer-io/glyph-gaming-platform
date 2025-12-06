<?php

use App\Models\Server;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Server-wide channel for lobby notifications and server events
Broadcast::channel('server.{serverId}', function ($user, $serverId) {
    $server = Server::find($serverId);

    if (!$server) {
        return false;
    }

    // User must be a member of the server
    return $server->members->contains($user->id);
});

// Specific channel within a server (for chat messages)
Broadcast::channel('server.{serverId}.channel.{channelId}', function ($user, $serverId, $channelId) {
    $server = Server::find($serverId);
    $channel = Channel::find($channelId);

    if (!$server || !$channel || $channel->server_id != $serverId) {
        return false;
    }

    return $server->members->contains($user->id);
});

// ===== DIRECT MESSAGE CHANNELS =====

/**
 * Direct Message channel - User's personal DM notification channel
 * Channel format: dm.user.{userId}
 *
 * Authorization: User can only subscribe to their own channel
 */
Broadcast::channel('dm.user.{userId}', function ($user, $userId) {
    Log::info('[Broadcasting] DM channel auth attempt', [
        'authenticated_user_id' => $user->id ?? 'NULL',
        'requested_user_id' => $userId,
        'match' => (int) $user->id === (int) $userId,
    ]);

    // User can only listen to their own DM channel
    return (int) $user->id === (int) $userId;
});

/**
 * Alternative: Conversation-specific channel
 * Channel format: dm.conversation.{conversationId}
 *
 * Authorization: User must be a participant in the conversation
 */
Broadcast::channel('dm.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    return $conversation->hasParticipant($user->id);
});

/**
 * DM Presence channel - For online status tracking in DM
 * Channel format: presence.dm
 *
 * Authorization: Any authenticated user can join
 * Returns user data for presence tracking
 */
Broadcast::channel('presence.dm', function ($user) {
    if (!$user) {
        return false;
    }

    return [
        'id' => $user->id,
        'display_name' => $user->display_name,
        'avatar_url' => $user->profile->avatar_url ?? null,
    ];
});

// ===== USER PERSONAL CHANNELS =====

/**
 * User personal channel - For user-specific notifications
 * Channel format: user.{userId}
 *
 * Used by: DM index, profile, lobby join buttons, matchmaking, phase3 realtime
 * Authorization: User can only subscribe to their own channel
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    Log::info('[Broadcasting] User channel auth attempt', [
        'authenticated_user_id' => $user->id ?? 'NULL',
        'requested_user_id' => $userId,
        'match' => (int) $user->id === (int) $userId,
    ]);

    // User can only listen to their own personal channel
    return (int) $user->id === (int) $userId;
});

// ===== TEAMS CHANNELS =====

/**
 * Global teams channel - For matchmaking and team creation notifications
 * Channel format: teams.global
 *
 * Used by: Live matchmaking, phase3 realtime
 * Authorization: Any authenticated user can subscribe
 */
Broadcast::channel('teams.global', function ($user) {
    Log::info('[Broadcasting] Teams global channel auth attempt', [
        'user_id' => $user->id ?? 'NULL',
    ]);

    // Any authenticated user can listen to global team events
    return $user !== null;
});

// ===== GAMING STATUS CHANNELS =====

/**
 * Server gaming status channel - For real-time gaming status updates within a server
 * Channel format: server.{serverId}.gaming-status
 *
 * Used by: Gaming status manager
 * Authorization: User must be a member of the server
 */
Broadcast::channel('server.{serverId}.gaming-status', function ($user, $serverId) {
    $server = Server::find($serverId);

    if (!$server) {
        Log::warning('[Broadcasting] Gaming status channel auth failed - server not found', [
            'server_id' => $serverId,
            'user_id' => $user->id ?? 'NULL',
        ]);
        return false;
    }

    $isMember = $server->members->contains($user->id);

    Log::info('[Broadcasting] Gaming status channel auth attempt', [
        'server_id' => $serverId,
        'user_id' => $user->id ?? 'NULL',
        'is_member' => $isMember,
    ]);

    return $isMember;
});

/**
 * User gaming status channel - For personal gaming status updates
 * Channel format: user.{userId}.gaming-status
 *
 * Used by: Gaming status manager
 * Authorization: User can only subscribe to their own gaming status channel
 */
Broadcast::channel('user.{userId}.gaming-status', function ($user, $userId) {
    Log::info('[Broadcasting] User gaming status channel auth attempt', [
        'authenticated_user_id' => $user->id ?? 'NULL',
        'requested_user_id' => $userId,
        'match' => (int) $user->id === (int) $userId,
    ]);

    // User can only listen to their own gaming status channel
    return (int) $user->id === (int) $userId;
});

// ===== LOBBY CHANNELS =====

/**
 * User lobby channel - For lobby creation/deletion notifications
 * Channel format: user.{userId}.lobby
 *
 * Used by: Lobbies page feed (Phase 3)
 * Authorization: User can subscribe to their friends' lobby channels
 */
Broadcast::channel('user.{userId}.lobby', function ($user, $userId) {
    // Users can always listen to their own lobby channel
    if ((int) $user->id === (int) $userId) {
        return true;
    }

    // Users can listen to their friends' lobby channels
    $isFriend = $user->friends()
        ->wherePivot('status', 'accepted')
        ->where('users.id', $userId)
        ->exists();

    Log::info('[Broadcasting] User lobby channel auth attempt', [
        'authenticated_user_id' => $user->id ?? 'NULL',
        'target_user_id' => $userId,
        'is_friend' => $isFriend,
    ]);

    return $isFriend;
});