<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'game_role',
        'skill_level',
        'individual_skill_score',
        'status',
        'member_data',
        'joined_at',
        'last_active_at',
        'left_at',
    ];

    protected $casts = [
        'member_data' => 'array',
        'individual_skill_score' => 'decimal:2',
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if member is a leader
     */
    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }

    /**
     * Check if member is a co-leader
     */
    public function isCoLeader(): bool
    {
        return $this->role === 'co_leader';
    }

    /**
     * Check if member can manage team
     */
    public function canManageTeam(): bool
    {
        return $this->isLeader();
    }

    /**
     * Update last activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'leader' => 'Team Leader',
            'co-leader' => 'Co-Leader',
            'member' => 'Member',
            'reserve' => 'Reserve',
            default => 'Unknown'
        };
    }

    /**
     * Get game role display name
     */
    public function getGameRoleDisplayName(): string
    {
        if (!$this->game_role) {
            return 'Unassigned';
        }

        return match($this->game_role) {
            'tank' => 'Tank',
            'dps' => 'DPS',
            'support' => 'Support',
            'flex' => 'Flex',
            'igl' => 'In-Game Leader',
            'entry' => 'Entry Fragger',
            'anchor' => 'Anchor',
            'awper' => 'AWPer',
            'rifler' => 'Rifler',
            default => ucfirst($this->game_role)
        };
    }

    /**
     * Get skill level color class for UI
     */
    public function getSkillLevelColor(): string
    {
        return match($this->skill_level) {
            'beginner' => 'text-green-500',
            'intermediate' => 'text-blue-500',
            'advanced' => 'text-purple-500',
            'expert' => 'text-red-500',
            default => 'text-gray-500'
        };
    }

    /**
     * Calculate contribution to team skill score
     */
    public function getSkillContribution(): float
    {
        if (!$this->individual_skill_score) {
            return 0.0;
        }

        // Leaders have slightly higher weight in skill calculation
        $weight = $this->isLeader() ? 1.1 : 1.0;
        
        return $this->individual_skill_score * $weight;
    }

    /**
     * Scope for active members
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for leader and co-leader members
     */
    public function scopeLeaders($query)
    {
        return $query->whereIn('role', ['leader', 'co_leader']);
    }

    /**
     * Scope for members by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for members by game role
     */
    public function scopeByGameRole($query, string $gameRole)
    {
        return $query->where('game_role', $gameRole);
    }

    /**
     * Scope for members by skill level
     */
    public function scopeBySkillLevel($query, string $skillLevel)
    {
        return $query->where('skill_level', $skillLevel);
    }
}