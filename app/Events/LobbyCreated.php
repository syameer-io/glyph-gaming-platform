<?php

namespace App\Events;

use App\Models\GameLobby;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * LobbyCreated event broadcasts when a user creates a new game lobby
 *
 * This event is broadcast to:
 * - The user's friends (so they see the lobby in their friends list)
 * - Server members (if the user is in any servers)
 * - Team members (if the user is in any teams)
 */
class LobbyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GameLobby $lobby;
    public array $lobbyData;

    /**
     * Create a new event instance.
     *
     * @param GameLobby $lobby The lobby that was created
     */
    public function __construct(GameLobby $lobby)
    {
        $this->lobby = $lobby;

        // Pre-format lobby data to avoid N+1 queries in broadcast
        $this->lobbyData = [
            'id' => $lobby->id,
            'user_id' => $lobby->user_id,
            'game_id' => $lobby->game_id,
            'join_method' => $lobby->join_method,
            'join_link' => $lobby->generateJoinLink(),
            'display_format' => $lobby->getDisplayFormat(),
            'time_remaining' => $lobby->timeRemaining(),
            'is_persistent' => $lobby->expires_at === null,
            'created_at' => $lobby->created_at->toIso8601String(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcast to a private user channel so the frontend can update in real-time
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->lobby->user_id}.lobby"),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'lobby.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'lobby' => $this->lobbyData,
        ];
    }
}
