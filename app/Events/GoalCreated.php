<?php

namespace App\Events;

use App\Models\ServerGoal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoalCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goal;

    public function __construct(ServerGoal $goal)
    {
        $this->goal = $goal;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->goal->server_id),
        ];
    }

    public function broadcastAs()
    {
        return 'goal.created';
    }

    public function broadcastWith()
    {
        return [
            'goal' => [
                'id' => $this->goal->id,
                'title' => $this->goal->title,
                'description' => $this->goal->description,
                'game_name' => $this->goal->game_name,
                'goal_type' => $this->goal->goal_type,
                'target_value' => $this->goal->target_value,
                'difficulty' => $this->goal->difficulty,
                'visibility' => $this->goal->visibility,
                'status' => $this->goal->status,
                'deadline' => $this->goal->deadline?->toISOString(),
                'creator' => [
                    'id' => $this->goal->creator->id,
                    'name' => $this->goal->creator->display_name,
                ],
            ],
        ];
    }
}
