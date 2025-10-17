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
 * Voice User Muted Event
 *
 * Broadcast event fired when a user toggles their mute status in a voice channel.
 * Notifies all server members in real-time via WebSocket so the UI can
 * update to show the user's current mute status (muted/unmuted icon).
 *
 * This event is broadcast to the private server channel so only server members
 * receive the notification.
 */
class VoiceUserMuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param User $user The user who toggled mute status
     * @param Channel $channel The voice channel where mute was toggled
     * @param Server $server The server containing the voice channel
     * @param bool $isMuted The new mute status (true = muted, false = unmuted)
     */
    public function __construct(
        public User $user,
        public Channel $channel,
        public Server $server,
        public bool $isMuted
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
     * Echo.private('server.123').listen('.voice.user.muted', callback)
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'voice.user.muted';
    }

    /**
     * Get the data to broadcast.
     *
     * Returns minimal user and channel data needed for frontend UI updates.
     * Includes the current mute status so the UI can display the correct icon
     * (microphone with slash for muted, regular microphone for unmuted).
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
            'is_muted' => $this->isMuted,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
