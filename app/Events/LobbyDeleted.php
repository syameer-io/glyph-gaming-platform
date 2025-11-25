<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LobbyDeleted event broadcasts when a user deletes their game lobby
 *
 * This notifies the frontend to remove the lobby indicator from the UI
 */
class LobbyDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $lobbyId;
    public int $userId;

    /**
     * Create a new event instance.
     *
     * @param int $lobbyId The ID of the deleted lobby
     * @param int $userId The ID of the user who owned the lobby
     */
    public function __construct(int $lobbyId, int $userId)
    {
        $this->lobbyId = $lobbyId;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}.lobby"),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'lobby.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'lobby_id' => $this->lobbyId,
            'user_id' => $this->userId,
        ];
    }
}
