<?php

namespace App\Events;

use App\Models\MatchmakingRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchmakingRequestCancelled implements ShouldBroadcast
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
        return 'matchmaking.request.cancelled';
    }

    public function broadcastWith()
    {
        return [
            'request' => [
                'id' => $this->request->id,
                'game_appid' => $this->request->game_appid,
                'game_name' => $this->request->game_name,
                'user_id' => $this->request->user_id,
                'status' => $this->request->status,
            ],
        ];
    }
}
