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
 * Voice User Speaking Event
 *
 * Broadcast event fired when a user's speaking status changes in a voice channel.
 * Notifies all server members in real-time via WebSocket so the UI can
 * update to show the speaking indicator (green ring around avatar).
 *
 * This event is broadcast to the private server channel so only server members
 * receive the notification. Debounced on client-side to max 10 updates/second.
 */
class VoiceUserSpeaking implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param User $user The user whose speaking status changed
     * @param Channel $channel The voice channel where speaking status changed
     * @param Server $server The server containing the voice channel
     * @param bool $isSpeaking Whether the user is currently speaking
     */
    public function __construct(
        public User $user,
        public Channel $channel,
        public Server $server,
        public bool $isSpeaking
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
     * Echo.private('server.123').listen('.voice.user.speaking', callback)
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'voice.user.speaking';
    }

    /**
     * Get the data to broadcast.
     *
     * Returns minimal user and channel data needed for frontend UI updates.
     * Includes the speaking status so the UI can display the green speaking ring.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'channel_id' => $this->channel->id,
            'server_id' => $this->server->id,
            'is_speaking' => $this->isSpeaking,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
