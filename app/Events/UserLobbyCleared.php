<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLobbyCleared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $displayName;

    /**
     * Create a new event instance.
     *
     * This event is broadcast when a user clears/closes their CS2 lobby link.
     * It notifies friends and team members that the lobby is no longer available.
     *
     * @param int $userId The user who cleared the lobby
     * @param string $displayName The user's display name
     */
    public function __construct(int $userId, string $displayName)
    {
        $this->userId = $userId;
        $this->displayName = $displayName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to:
     * - Private user channel for the user's profile/dashboard
     * - Team members can listen to this channel if implementing team notifications
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'user.lobby.cleared';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'display_name' => $this->displayName,
            'message' => "{$this->displayName} left their lobby",
            'timestamp' => now()->toISOString(),
        ];
    }
}
