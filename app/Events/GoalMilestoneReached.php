<?php

namespace App\Events;

use App\Models\ServerGoal;
use App\Models\GoalMilestone;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoalMilestoneReached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goal;
    public $milestone;

    public function __construct(ServerGoal $goal, GoalMilestone $milestone)
    {
        $this->goal = $goal;
        $this->milestone = $milestone;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->goal->server_id),
        ];
    }

    public function broadcastAs()
    {
        return 'goal.milestone.reached';
    }

    public function broadcastWith()
    {
        return [
            'goal' => [
                'id' => $this->goal->id,
                'title' => $this->goal->title,
                'progress' => $this->goal->progress,
                'target_value' => $this->goal->target_value,
                'completion_percentage' => $this->goal->getCompletionPercentage(),
            ],
            'milestone' => [
                'id' => $this->milestone->id,
                'name' => $this->milestone->name,
                'description' => $this->milestone->description,
                'threshold' => $this->milestone->threshold,
                'reward_data' => $this->milestone->reward_data,
                'achieved_at' => $this->milestone->achieved_at->toISOString(),
            ],
        ];
    }
}