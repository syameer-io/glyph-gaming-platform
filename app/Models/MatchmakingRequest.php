<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingRequest extends Model
{
    protected $fillable = [
        'user_id',
        'game_appid',
        'game_name',
        'request_type',
        'preferred_roles',
        'skill_level',
        'skill_score',
        'availability_hours',
        'server_preferences',
        'additional_requirements',
        'priority',
        'status',
        'description',
        'expires_at',
        'last_activity_at',
    ];

    protected $casts = [
        'preferred_roles' => 'array',
        'availability_hours' => 'array',
        'server_preferences' => 'array',
        'additional_requirements' => 'array',
        'skill_score' => 'decimal:2',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if request is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->expires_at > now();
    }

    /**
     * Check if request is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Mark request as matched
     */
    public function markAsMatched(): void
    {
        $this->update(['status' => 'matched']);
    }

    /**
     * Mark request as expired
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Update activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get compatibility score with another request (0-100)
     */
    public function getCompatibilityScore(self $otherRequest): float
    {
        $score = 0;
        $factors = 0;

        // Game compatibility (essential)
        if ($this->game_appid !== $otherRequest->game_appid) {
            return 0; // No compatibility across different games
        }
        
        // Skill level compatibility
        $skillCompatibility = $this->calculateSkillCompatibility($otherRequest);
        $score += $skillCompatibility * 0.4;
        $factors += 0.4;

        // Role compatibility
        $roleCompatibility = $this->calculateRoleCompatibility($otherRequest);
        $score += $roleCompatibility * 0.3;
        $factors += 0.3;

        // Availability compatibility
        $availabilityCompatibility = $this->calculateAvailabilityCompatibility($otherRequest);
        $score += $availabilityCompatibility * 0.2;
        $factors += 0.2;

        // Server preference compatibility
        $serverCompatibility = $this->calculateServerCompatibility($otherRequest);
        $score += $serverCompatibility * 0.1;
        $factors += 0.1;

        return $factors > 0 ? ($score / $factors) * 100 : 0;
    }

    /**
     * Calculate skill compatibility between requests
     */
    private function calculateSkillCompatibility(self $otherRequest): float
    {
        if (!$this->skill_score || !$otherRequest->skill_score) {
            return 0.5; // Neutral if no skill data
        }

        $skillDifference = abs($this->skill_score - $otherRequest->skill_score);
        
        // Perfect match at 0 difference, decreasing as difference increases
        return max(0, 1 - ($skillDifference / 50));
    }

    /**
     * Calculate role compatibility between requests
     */
    private function calculateRoleCompatibility(self $otherRequest): float
    {
        $myRoles = $this->preferred_roles ?? [];
        $otherRoles = $otherRequest->preferred_roles ?? [];

        if (empty($myRoles) || empty($otherRoles)) {
            return 0.5; // Neutral if no role preferences
        }

        // Check for complementary roles (not overlapping)
        $overlap = array_intersect($myRoles, $otherRoles);
        $overlapRatio = count($overlap) / max(count($myRoles), count($otherRoles));

        // Lower overlap is better for team formation
        return 1 - $overlapRatio;
    }

    /**
     * Calculate availability compatibility
     */
    private function calculateAvailabilityCompatibility(self $otherRequest): float
    {
        $myHours = $this->availability_hours ?? [];
        $otherHours = $otherRequest->availability_hours ?? [];

        if (empty($myHours) || empty($otherHours)) {
            return 0.5; // Neutral if no availability data
        }

        $overlap = array_intersect($myHours, $otherHours);
        $overlapRatio = count($overlap) / max(count($myHours), count($otherHours));

        return $overlapRatio;
    }

    /**
     * Calculate server preference compatibility
     */
    private function calculateServerCompatibility(self $otherRequest): float
    {
        $myServers = $this->server_preferences ?? [];
        $otherServers = $otherRequest->server_preferences ?? [];

        if (empty($myServers) || empty($otherServers)) {
            return 0.5; // Neutral if no server preferences
        }

        $overlap = array_intersect($myServers, $otherServers);
        $overlapRatio = count($overlap) / max(count($myServers), count($otherServers));

        return $overlapRatio;
    }

    /**
     * Scope for active requests
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired requests
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->where('status', '!=', 'expired');
    }

    /**
     * Scope for requests by game
     */
    public function scopeByGame($query, string $gameAppId)
    {
        return $query->where('game_appid', $gameAppId);
    }

    /**
     * Scope for requests by type
     */
    public function scopeByType($query, string $requestType)
    {
        return $query->where('request_type', $requestType);
    }

    /**
     * Scope for requests by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}