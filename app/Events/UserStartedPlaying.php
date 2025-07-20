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

class UserStartedPlaying implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $gameData;

    public function __construct(User $user, array $gameData)
    {
        $this->user = $user;
        $this->gameData = $gameData;
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
        return 'user.started.playing';
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
                'appid' => $this->gameData['appid'],
                'name' => $this->gameData['name'],
                'server_name' => $this->gameData['server_name'] ?? null,
                'map' => $this->gameData['map'] ?? null,
                'game_mode' => $this->gameData['game_mode'] ?? null,
                'timestamp' => $this->gameData['timestamp'],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}