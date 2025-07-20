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

class GoalCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goal;
    public $topContributors;

    public function __construct(ServerGoal $goal, array $topContributors = [])
    {
        $this->goal = $goal;
        $this->topContributors = $topContributors;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->goal->server_id),
        ];
    }

    public function broadcastAs()
    {
        return 'goal.completed';
    }

    public function broadcastWith()
    {
        return [
            'goal' => [
                'id' => $this->goal->id,
                'title' => $this->goal->title,
                'description' => $this->goal->description,
                'type' => $this->goal->type,
                'progress' => $this->goal->progress,
                'target_value' => $this->goal->target_value,
                'completion_percentage' => 100,
                'status' => $this->goal->status,
                'completed_at' => $this->goal->completed_at->toISOString(),
                'reward_data' => $this->goal->reward_data,
                'participant_count' => $this->goal->participants()->count(),
            ],
            'top_contributors' => $this->topContributors,
        ];
    }
}