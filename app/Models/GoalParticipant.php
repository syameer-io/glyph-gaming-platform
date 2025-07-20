<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalParticipant extends Model
{
    protected $fillable = [
        'goal_id',
        'user_id', 
        'individual_progress',
        'contribution_percentage',
        'progress_data',
        'participation_status',
        'joined_at',
        'last_activity_at',
        'completed_at',
        'achievements_unlocked',
        'skill_score_at_start',
        'current_skill_score',
    ];

    protected $casts = [
        'contribution_percentage' => 'decimal:2',
        'progress_data' => 'array',
        'achievements_unlocked' => 'array',
        'skill_score_at_start' => 'decimal:2',
        'current_skill_score' => 'decimal:2',
        'joined_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(ServerGoal::class, 'goal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->participation_status === 'active';
    }

    public function updateProgress(int $progress): void
    {
        $this->update([
            'individual_progress' => $progress,
            'last_activity_at' => now(),
        ]);

        // Update contribution percentage
        $this->calculateContribution();
        
        // Update goal's overall progress
        $this->goal->updateProgress();
    }

    private function calculateContribution(): void
    {
        $totalProgress = $this->goal->participants()
            ->where('participation_status', 'active')
            ->sum('individual_progress');
            
        if ($totalProgress > 0) {
            $contribution = ($this->individual_progress / $totalProgress) * 100;
            $this->update(['contribution_percentage' => $contribution]);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('participation_status', 'active');
    }
}