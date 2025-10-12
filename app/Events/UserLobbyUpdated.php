<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLobbyUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $displayName;
    public string $lobbyLink;
    public ?array $team;

    /**
     * Create a new event instance.
     *
     * This event is broadcast when a user creates or updates their CS2 lobby link.
     * It notifies friends and team members that a lobby is available to join.
     *
     * @param int $userId The user who created the lobby
     * @param string $displayName The user's display name
     * @param string $lobbyLink The Steam CS2 lobby link
     * @param array|null $team Optional team information if user is in a team
     */
    public function __construct(
        int $userId,
        string $displayName,
        string $lobbyLink,
        ?array $team = null
    ) {
        $this->userId = $userId;
        $this->displayName = $displayName;
        $this->lobbyLink = $lobbyLink;
        $this->team = $team;
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
        return 'user.lobby.updated';
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
            'lobby_link' => $this->lobbyLink,
            'team' => $this->team,
            'message' => "{$this->displayName} created a CS2 lobby!",
            'timestamp' => now()->toISOString(),
        ];
    }
}
