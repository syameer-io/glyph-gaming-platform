<?php

namespace App\Events;

use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $user;
    public $memberData;

    public function __construct(Team $team, User $user, array $memberData)
    {
        $this->team = $team;
        $this->user = $user;
        $this->memberData = $memberData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->team->server_id),
            new PrivateChannel('team.' . $this->team->id),
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    public function broadcastAs()
    {
        return 'team.member.left';
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
            'user' => [
                'id' => $this->user->id,
                'display_name' => $this->user->display_name,
                'avatar_url' => $this->user->profile->avatar_url ?? null,
            ],
            'member_data' => $this->memberData,
        ];
    }
}