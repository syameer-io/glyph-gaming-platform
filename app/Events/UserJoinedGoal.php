<?php

namespace App\Events;

use App\Models\ServerGoal;
use App\Models\GoalParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedGoal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goal;
    public $participant;

    public function __construct(ServerGoal $goal, GoalParticipant $participant)
    {
        $this->goal = $goal;
        $this->participant = $participant;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->goal->server_id),
            new PrivateChannel('user.' . $this->participant->user_id),
        ];
    }

    public function broadcastAs()
    {
        return 'goal.user.joined';
    }

    public function broadcastWith()
    {
        return [
            'goal' => [
                'id' => $this->goal->id,
                'title' => $this->goal->title,
                'type' => $this->goal->type,
                'participant_count' => $this->goal->participants()->count(),
            ],
            'participant' => [
                'id' => $this->participant->id,
                'user_id' => $this->participant->user_id,
                'contribution' => $this->participant->contribution,
                'contribution_percentage' => $this->participant->contribution_percentage,
                'joined_at' => $this->participant->created_at->toISOString(),
                'user' => [
                    'id' => $this->participant->user->id,
                    'display_name' => $this->participant->user->display_name,
                    'avatar_url' => $this->participant->user->profile->avatar_url ?? null,
                ],
            ],
        ];
    }
}