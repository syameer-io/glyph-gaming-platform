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

class TeamStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $previousStatus;

    public function __construct(Team $team, string $previousStatus)
    {
        $this->team = $team;
        $this->previousStatus = $previousStatus;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->team->server_id),
            new PrivateChannel('team.' . $this->team->id),
            new PrivateChannel('teams.global'),
        ];
    }

    public function broadcastAs()
    {
        return 'team.status.changed';
    }

    public function broadcastWith()
    {
        return [
            'team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'current_size' => $this->team->current_size,
                'max_size' => $this->team->max_size,
                'status' => $this->team->status,
                'previous_status' => $this->previousStatus,
                'game_name' => $this->team->game_name,
                'skill_level' => $this->team->skill_level,
            ],
        ];
    }
}