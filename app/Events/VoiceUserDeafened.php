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
 * Voice User Deafened Event
 *
 * Broadcast event fired when a user toggles their deafen status in a voice channel.
 * Notifies all server members in real-time via WebSocket so the UI can
 * update to show the user's deafened status (headphone with slash icon).
 *
 * This event is broadcast to the private server channel so only server members
 * receive the notification.
 */
class VoiceUserDeafened implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param User $user The user who toggled deafen status
     * @param Channel $channel The voice channel where deafen was toggled
     * @param Server $server The server containing the voice channel
     * @param bool $isDeafened The new deafen status (true = deafened, false = not deafened)
     */
    public function __construct(
        public User $user,
        public Channel $channel,
        public Server $server,
        public bool $isDeafened
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to the private server channel so only server members receive
     * the notification. Follows the existing pattern used by voice events.
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
     * Echo.private('server.123').listen('.voice.user.deafened', callback)
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'voice.user.deafened';
    }

    /**
     * Get the data to broadcast.
     *
     * Returns minimal user and channel data needed for frontend UI updates.
     * Includes the deafen status so the UI can display the correct icon.
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
            ],
            'channel_id' => $this->channel->id,
            'channel_name' => $this->channel->name,
            'server_id' => $this->server->id,
            'is_deafened' => $this->isDeafened,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
