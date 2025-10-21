<?php

use App\Models\Server;
use App\Models\Channel;
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