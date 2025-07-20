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

class GoalProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $goal;
    public $previousProgress;

    public function __construct(ServerGoal $goal, float $previousProgress)
    {
        $this->goal = $goal;
        $this->previousProgress = $previousProgress;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->goal->server_id),
        ];
    }

    public function broadcastAs()
    {
        return 'goal.progress.updated';
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
                'previous_progress' => $this->previousProgress,
                'target_value' => $this->goal->target_value,
                'completion_percentage' => $this->goal->getCompletionPercentage(),
                'status' => $this->goal->status,
                'participant_count' => $this->goal->participants()->count(),
                'updated_at' => $this->goal->updated_at->toISOString(),
            ],
        ];
    }
}