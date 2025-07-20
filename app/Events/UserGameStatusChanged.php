<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserGameStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $previousGame;
    public $currentGame;
    public $changes;

    public function __construct(User $user, array $previousGame, array $currentGame, array $changes)
    {
        $this->user = $user;
        $this->previousGame = $previousGame;
        $this->currentGame = $currentGame;
        $this->changes = $changes;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to all servers the user is a member of
        foreach ($this->user->servers as $server) {
            $channels[] = new PrivateChannel('server.' . $server->id . '.gaming-status');
        }
        
        // Also broadcast to user's friends (when implemented)
        $channels[] = new PrivateChannel('user.' . $this->user->id . '.gaming-status');
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'user.game.status.changed';
    }

    public function broadcastWith()
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'display_name' => $this->user->display_name,
                'avatar_url' => $this->user->profile->avatar_url,
            ],
            'game' => [
                'appid' => $this->currentGame['appid'],
                'name' => $this->currentGame['name'],
            ],
            'previous_status' => [
                'server_name' => $this->previousGame['server_name'] ?? null,
                'map' => $this->previousGame['map'] ?? null,
                'game_mode' => $this->previousGame['game_mode'] ?? null,
            ],
            'current_status' => [
                'server_name' => $this->currentGame['server_name'] ?? null,
                'map' => $this->currentGame['map'] ?? null,
                'game_mode' => $this->currentGame['game_mode'] ?? null,
                'timestamp' => $this->currentGame['timestamp'],
            ],
            'changes' => $this->changes,
            'timestamp' => now()->toISOString(),
        ];
    }
}