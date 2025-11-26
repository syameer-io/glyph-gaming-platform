<?php

use App\Models\Server;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

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