<?php

namespace App\Events;

use App\Models\Team;
use App\Models\MatchmakingRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFound implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $request;
    public $compatibilityScore;

    public function __construct(Team $team, MatchmakingRequest $request, float $compatibilityScore)
    {
        $this->team = $team;
        $this->request = $request;
        $this->compatibilityScore = $compatibilityScore;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->user_id),
        ];
    }

    public function broadcastAs()
    {
        return 'matchmaking.match.found';
    }

    public function broadcastWith()
    {
        return [
            'team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'description' => $this->team->description,
                'game_name' => $this->team->game_name,
                'skill_level' => $this->team->skill_level,
                'current_size' => $this->team->current_size,
                'max_size' => $this->team->max_size,
                'server' => [
                    'id' => $this->team->server->id,
                    'name' => $this->team->server->name,
                ],
            ],
            'compatibility_score' => $this->compatibilityScore,
            'request_id' => $this->request->id,
        ];
    }
}