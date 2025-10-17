<?php

namespace App\Events;

use App\Models\User;
use App\Models\Channel;
use App\Models\Server;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Voice User Left Event
 *
 * Broadcast event fired when a user leaves a voice channel.
 * Notifies all server members in real-time via WebSocket so the UI can
 * update to remove the user from the voice channel participant list.
 *
 * This event is broadcast to the private server channel so only server members
 * receive the notification.
 */
class VoiceUserLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param User $user The user who left the voice channel
     * @param Channel $channel The voice channel that was left
     * @param Server $server The server containing the voice channel
     */
    public function __construct(
        public User $user,
        public Channel $channel,
        public Server $server
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to the private server channel so only server members receive
     * the notification. Follows the existing pattern used by MessagePosted events.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->server->id),
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * Frontend will listen for this event name via Laravel Echo:
     * Echo.private('server.123').listen('.voice.user.left', callback)
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'voice.user.left';
    }

    /**
     * Get the data to broadcast.
     *
     * Returns minimal user and channel data needed for frontend UI updates.
     * Includes avatar URL from user profile relationship.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'display_name' => $this->user->display_name,
                'avatar_url' => $this->user->profile->avatar_url ?? null,
            ],
            'channel_id' => $this->channel->id,
            'channel_name' => $this->channel->name,
            'server_id' => $this->server->id,
            'left_at' => now()->toIso8601String(),
        ];
    }
}
