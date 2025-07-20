<?php

namespace App\Events;

use App\Models\MatchmakingRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchmakingRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;

    public function __construct(MatchmakingRequest $request)
    {
        $this->request = $request;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->user_id),
            new PrivateChannel('matchmaking.global'),
        ];
    }

    public function broadcastAs()
    {
        return 'matchmaking.request.created';
    }

    public function broadcastWith()
    {
        return [
            'request' => [
                'id' => $this->request->id,
                'game_appid' => $this->request->game_appid,
                'game_name' => $this->request->game_name,
                'skill_level' => $this->request->skill_level,
                'region_preference' => $this->request->region_preference,
                'status' => $this->request->status,
                'created_at' => $this->request->created_at->toISOString(),
                'user' => [
                    'id' => $this->request->user->id,
                    'display_name' => $this->request->user->display_name,
                ],
            ],
        ];
    }
}