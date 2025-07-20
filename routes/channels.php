<?php

use App\Models\Server;
use App\Models\Channel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('server.{serverId}.channel.{channelId}', function ($user, $serverId, $channelId) {
    $server = Server::find($serverId);
    $channel = Channel::find($channelId);
    
    if (!$server || !$channel || $channel->server_id != $serverId) {
        return false;
    }
    
    return $server->members->contains($user->id);
});