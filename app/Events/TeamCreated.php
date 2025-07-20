<?php

namespace App\Events;

use App\Models\Team;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;

    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->team->server_id),
            new PrivateChannel('teams.global'),
        ];
    }

    public function broadcastAs()
    {
        return 'team.created';
    }

    public function broadcastWith()
    {
        return [
            'team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'description' => $this->team->description,
                'game_appid' => $this->team->game_appid,
                'game_name' => $this->team->game_name,
                'server_id' => $this->team->server_id,
                'creator_id' => $this->team->creator_id,
                'max_size' => $this->team->max_size,
                'current_size' => $this->team->current_size,
                'skill_level' => $this->team->skill_level,
                'status' => $this->team->status,
                'average_skill_score' => $this->team->average_skill_score,
                'created_at' => $this->team->created_at->toISOString(),
                'creator' => [
                    'id' => $this->team->creator->id,
                    'display_name' => $this->team->creator->display_name,
                    'avatar_url' => $this->team->creator->profile->avatar_url ?? null,
                ],
                'server' => [
                    'id' => $this->team->server->id,
                    'name' => $this->team->server->name,
                ],
            ],
        ];
    }
}