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
     * Check if member can manage roles (assign roles to team members)
     */
    public function canManageRoles(): bool
    {
        return $this->isLeader() || $this->isCoLeader();
    }

    /**
     * Get preferred roles from member_data
     * These are the roles the member indicated they can play when joining
     */
    public function getPreferredRolesAttribute(): array
    {
        $memberData = $this->member_data ?? [];
        return $memberData['preferred_roles'] ?? [];
    }

    /**
     * Check if a given role is in the member's preferred roles
     */
    public function isPreferredRole(string $role): bool
    {
        return in_array($role, $this->preferred_roles, true);
    }

    /**
     * Assign a game role to this member
     *
     * @param string $role The game role to assign
     * @param int $assignedBy User ID of the person assigning the role
     */
    public function assignRole(string $role, int $assignedBy): void
    {
        $memberData = $this->member_data ?? [];
        $memberData['role_assigned_at'] = now()->toIso8601String();
        $memberData['role_assigned_by'] = $assignedBy;

        $this->update([
            'game_role' => $role,
            'member_data' => $memberData,
        ]);
    }

    /**
     * Clear the assigned game role
     */
    public function clearRole(): void
    {
        $memberData = $this->member_data ?? [];
        unset($memberData['role_assigned_at']);
        unset($memberData['role_assigned_by']);

        $this->update([
            'game_role' => null,
            'member_data' => $memberData,
        ]);
    }

    /**
     * Get role assignment metadata
     */
    public function getRoleAssignmentInfo(): ?array
    {
        $memberData = $this->member_data ?? [];

        if (!isset($memberData['role_assigned_at'])) {
            return null;
        }

        return [
            'assigned_at' => $memberData['role_assigned_at'],
            'assigned_by' => $memberData['role_assigned_by'] ?? null,
        ];
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
     * Get game role display name using config/game_roles.php
     *
     * @param string|null $gameAppId Optional game ID for game-specific display names
     */
    public function getGameRoleDisplayName(?string $gameAppId = null): string
    {
        if (!$this->game_role) {
            return 'Unassigned';
        }

        // If game ID provided, try to get game-specific display name
        if ($gameAppId) {
            $gameConfig = config("game_roles.games.{$gameAppId}");
            if ($gameConfig && isset($gameConfig['display_names'][$this->game_role])) {
                return $gameConfig['display_names'][$this->game_role];
            }
        }

        // Try to get display name from team's game if we have team loaded
        if ($this->relationLoaded('team') && $this->team) {
            $teamGameId = $this->team->game_appid;
            $gameConfig = config("game_roles.games.{$teamGameId}");
            if ($gameConfig && isset($gameConfig['display_names'][$this->game_role])) {
                return $gameConfig['display_names'][$this->game_role];
            }
        }

        // Fallback to generic display names
        $genericNames = config('game_roles.generic.display_names', []);
        if (isset($genericNames[$this->game_role])) {
            return $genericNames[$this->game_role];
        }

        // Final fallback: humanize the role string
        return ucfirst(str_replace('_', ' ', $this->game_role));
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