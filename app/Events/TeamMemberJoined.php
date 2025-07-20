<?php

namespace App\Events;

use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $teamMember;

    public function __construct(Team $team, TeamMember $teamMember)
    {
        $this->team = $team;
        $this->teamMember = $teamMember;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->team->server_id),
            new PrivateChannel('team.' . $this->team->id),
            new PrivateChannel('user.' . $this->teamMember->user_id),
        ];
    }

    public function broadcastAs()
    {
        return 'team.member.joined';
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
            ],
            'member' => [
                'id' => $this->teamMember->id,
                'user_id' => $this->teamMember->user_id,
                'role' => $this->teamMember->role,
                'skill_score' => $this->teamMember->skill_score,
                'status' => $this->teamMember->status,
                'joined_at' => $this->teamMember->created_at->toISOString(),
                'user' => [
                    'id' => $this->teamMember->user->id,
                    'display_name' => $this->teamMember->user->display_name,
                    'avatar_url' => $this->teamMember->user->profile->avatar_url ?? null,
                ],
            ],
        ];
    }
}