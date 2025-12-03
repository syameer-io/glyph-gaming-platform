<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerGoal extends Model
{
    protected $fillable = [
        'server_id',
        'creator_id',
        'title',
        'description',
        'game_appid',
        'game_name',
        'goal_type',
        'target_criteria',
        'target_value',
        'current_progress',
        'completion_percentage',
        'difficulty',
        'visibility',
        'status',
        'rewards',
        'goal_settings',
        'start_date',
        'deadline',
        'completed_at',
        'participant_count',
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'rewards' => 'array',
        'goal_settings' => 'array',
        'completion_percentage' => 'decimal:2',
        'start_date' => 'datetime',
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GoalParticipant::class, 'goal_id');
    }

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(GoalParticipant::class, 'goal_id')
                   ->where('participation_status', 'active');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class, 'goal_id')->orderBy('order');
    }

    /**
     * Check if goal is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               (!$this->deadline || $this->deadline > now()) &&
               (!$this->start_date || $this->start_date <= now());
    }

    /**
     * Check if goal is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || 
               $this->completion_percentage >= 100;
    }

    /**
     * Check if goal is expired
     */
    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline <= now() && !$this->isCompleted();
    }

    /**
     * Check if user can participate
     */
    public function canUserParticipate(User $user): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if user is already participating
        if ($this->participants()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Check visibility permissions
        if ($this->visibility === 'private') {
            return $user->id === $this->creator_id;
        }

        // Check if user is a server member
        return $user->servers()->where('server_id', $this->server_id)->exists();
    }

    /**
     * Add participant to goal
     */
    public function addParticipant(User $user): bool
    {
        if (!$this->canUserParticipate($user)) {
            return false;
        }

        $this->participants()->create([
            'user_id' => $user->id,
            'individual_progress' => 0,
            'contribution_percentage' => 0,
            'participation_status' => 'active',
            'joined_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->increment('participant_count');
        return true;
    }

    /**
     * Update goal progress
     */
    public function updateProgress(int $newProgress = null): void
    {
        $previousProgress = $this->current_progress;
        $previousStatus = $this->status;

        if ($newProgress !== null) {
            $this->current_progress = $newProgress;
        } else {
            // Calculate progress from participants
            $this->current_progress = $this->participants()
                ->where('participation_status', 'active')
                ->sum('individual_progress');
        }

        // Update completion percentage
        $this->completion_percentage = $this->target_value > 0 
            ? min(100, ($this->current_progress / $this->target_value) * 100)
            : 0;

        // Check if goal is completed
        if ($this->completion_percentage >= 100 && $this->status !== 'completed') {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        $this->save();

        // Broadcast progress update event if progress changed
        if ($this->current_progress !== $previousProgress) {
            event(new \App\Events\GoalProgressUpdated($this, $previousProgress));
        }

        // Broadcast completion event if just completed
        if ($this->status === 'completed' && $previousStatus !== 'completed') {
            $topContributors = $this->getTopContributors(3);
            event(new \App\Events\GoalCompleted($this, $topContributors));
        }

        // Check and update milestones
        $this->updateMilestones();
    }

    /**
     * Update milestone achievements
     */
    public function updateMilestones(): void
    {
        $milestones = $this->milestones()->where('is_achieved', false)->get();

        foreach ($milestones as $milestone) {
            if ($this->current_progress >= $milestone->progress_required ||
                $this->completion_percentage >= $milestone->percentage_required) {
                
                $milestone->update([
                    'is_achieved' => true,
                    'achieved_at' => now(),
                    'achieved_by_count' => $this->participant_count,
                ]);

                // Broadcast milestone achievement if enabled
                if ($milestone->broadcast_achievement) {
                    event(new \App\Events\GoalMilestoneReached($this, $milestone));
                }
            }
        }
    }

    /**
     * Get progress towards next milestone
     */
    public function getNextMilestone(): ?GoalMilestone
    {
        return $this->milestones()
                   ->where('is_achieved', false)
                   ->orderBy('progress_required')
                   ->first();
    }

    /**
     * Get achieved milestones
     */
    public function getAchievedMilestones(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->milestones()->where('is_achieved', true)->get();
    }

    /**
     * Get top contributors for this goal
     *
     * @param int $limit Number of contributors to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopContributors(int $limit = 3): \Illuminate\Database\Eloquent\Collection
    {
        return $this->participants()
            ->where('participation_status', 'active')
            ->orderBy('individual_progress', 'desc')
            ->with('user')
            ->take($limit)
            ->get();
    }

    /**
     * Calculate days remaining
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->deadline) {
            return null;
        }

        $daysRemaining = (int) now()->diffInDays($this->deadline, false);
        return max(0, $daysRemaining);
    }

    /**
     * Get difficulty color for UI
     */
    public function getDifficultyColor(): string
    {
        return match($this->difficulty) {
            'easy' => 'text-green-500',
            'medium' => 'text-yellow-500',
            'hard' => 'text-orange-500',
            'extreme' => 'text-red-500',
            default => 'text-gray-500'
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'draft' => 'text-gray-500',
            'active' => 'text-blue-500',
            'completed' => 'text-green-500',
            'failed' => 'text-red-500',
            'cancelled' => 'text-gray-400',
            default => 'text-gray-500'
        };
    }

    /**
     * Scope for active goals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('deadline')
                          ->orWhere('deadline', '>', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', now());
                    });
    }

    /**
     * Scope for public goals
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope for goals by game
     */
    public function scopeByGame($query, string $gameAppId)
    {
        return $query->where('game_appid', $gameAppId);
    }

    /**
     * Scope for goals by type
     */
    public function scopeByType($query, string $goalType)
    {
        return $query->where('goal_type', $goalType);
    }

    /**
     * Scope for goals by difficulty
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope for completed goals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for goals expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereNotNull('deadline')
                    ->whereBetween('deadline', [now(), now()->addDays($days)]);
    }

    /**
     * Accessor for game name (fallback if not set in database)
     */
    public function getGameNameAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Fallback to map app ID to game name
        if ($this->game_appid) {
            $gameNames = [
                '730' => 'Counter-Strike 2',
                '570' => 'Dota 2',
                '230410' => 'Warframe',
                '1172470' => 'Apex Legends',
                '252490' => 'Rust',
                '578080' => 'PUBG',
                '359550' => 'Rainbow Six Siege',
                '433850' => 'Fall Guys',
            ];
            
            return $gameNames[$this->game_appid] ?? 'Unknown Game';
        }

        return null;
    }
}