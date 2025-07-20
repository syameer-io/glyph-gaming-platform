<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalMilestone extends Model
{
    protected $fillable = [
        'goal_id',
        'milestone_name',
        'description',
        'progress_required',
        'percentage_required',
        'reward_description',
        'milestone_data',
        'milestone_type',
        'is_achieved',
        'achieved_at',
        'achieved_by_count',
        'achieved_by_users',
        'broadcast_achievement',
        'order',
    ];

    protected $casts = [
        'percentage_required' => 'decimal:2',
        'milestone_data' => 'array',
        'achieved_by_users' => 'array',
        'is_achieved' => 'boolean',
        'broadcast_achievement' => 'boolean',
        'achieved_at' => 'datetime',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(ServerGoal::class, 'goal_id');
    }

    public function checkAchievement(int $currentProgress, float $currentPercentage): bool
    {
        if ($this->is_achieved) {
            return false;
        }

        $achieved = $currentProgress >= $this->progress_required || 
                   $currentPercentage >= $this->percentage_required;

        if ($achieved) {
            $this->update([
                'is_achieved' => true,
                'achieved_at' => now(),
            ]);
        }

        return $achieved;
    }

    public function scopeAchieved($query)
    {
        return $query->where('is_achieved', true);
    }

    public function scopeUnachieved($query)
    {
        return $query->where('is_achieved', false);
    }
}