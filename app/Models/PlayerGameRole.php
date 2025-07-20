<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerGameRole extends Model
{
    protected $fillable = [
        'user_id',
        'game_appid',
        'game_name',
        'role_name',
        'role_type',
        'skill_level',
        'preference_level',
        'experience_hours',
        'performance_stats',
        'role_settings',
        'is_primary',
        'is_active',
        'last_played',
    ];

    protected $casts = [
        'preference_level' => 'decimal:2',
        'experience_hours' => 'decimal:2',
        'performance_stats' => 'array',
        'role_settings' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'last_played' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get skill level color for UI
     */
    public function getSkillLevelColor(): string
    {
        return match($this->skill_level) {
            'beginner' => 'text-green-500',
            'intermediate' => 'text-yellow-500',
            'advanced' => 'text-orange-500',
            'expert' => 'text-red-500',
            default => 'text-gray-500'
        };
    }

    /**
     * Get preference level badge class
     */
    public function getPreferenceBadgeClass(): string
    {
        return match(true) {
            $this->preference_level >= 90 => 'bg-red-100 text-red-800',
            $this->preference_level >= 70 => 'bg-yellow-100 text-yellow-800',
            $this->preference_level >= 50 => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get role compatibility with another role
     */
    public function getCompatibilityWith(self $otherRole): float
    {
        if ($this->role_name === $otherRole->role_name) {
            return 20; // Same role = low compatibility (need diversity)
        }

        // Check for complementary roles
        $complementaryRoles = $this->getComplementaryRoles();
        
        if (in_array($otherRole->role_name, $complementaryRoles)) {
            return 90; // High compatibility for complementary roles
        }

        // Check skill level compatibility
        $skillDifference = abs($this->getSkillScore() - $otherRole->getSkillScore());
        $skillCompatibility = max(0, 100 - ($skillDifference * 2));

        return $skillCompatibility * 0.7; // Moderate compatibility based on skill
    }

    /**
     * Get complementary roles for this role
     */
    public function getComplementaryRoles(): array
    {
        return match($this->role_name) {
            // FPS games (CS2, Valorant, R6S)
            'entry_fragger' => ['support', 'igl', 'anchor'],
            'support' => ['entry_fragger', 'awper', 'lurker'],
            'awper' => ['support', 'entry_fragger', 'igl'],
            'igl' => ['entry_fragger', 'support', 'anchor'],
            'lurker' => ['support', 'anchor', 'igl'],
            'anchor' => ['entry_fragger', 'igl', 'lurker'],

            // MOBA games (Dota 2, LoL)
            'carry' => ['support', 'initiator', 'jungler'],
            'mid' => ['support', 'carry', 'offlaner'],
            'offlaner' => ['support', 'carry', 'mid'],
            'support' => ['carry', 'mid', 'offlaner'],
            'jungler' => ['carry', 'support', 'mid'],
            'initiator' => ['carry', 'support', 'mid'],

            // MMO/Coop games (Warframe, Destiny)
            'dps' => ['healer', 'tank', 'support'],
            'healer' => ['dps', 'tank', 'crowd_control'],
            'tank' => ['healer', 'dps', 'support'],
            'crowd_control' => ['dps', 'healer', 'tank'],
            'buffer' => ['dps', 'healer', 'tank'],

            // Battle Royale (Apex, PUBG, Fortnite)
            'fragger' => ['support', 'igl', 'scout'],
            'scout' => ['fragger', 'support', 'igl'],
            'sniper' => ['support', 'scout', 'fragger'],

            default => []
        };
    }

    /**
     * Get numeric skill score for calculations
     */
    public function getSkillScore(): float
    {
        return match($this->skill_level) {
            'expert' => 85,
            'advanced' => 65,
            'intermediate' => 45,
            'beginner' => 25,
            default => 50
        };
    }

    /**
     * Update role statistics from gameplay data
     */
    public function updateStats(array $newStats): void
    {
        $currentStats = $this->performance_stats ?? [];
        
        // Merge new stats with existing
        $updatedStats = array_merge($currentStats, $newStats);
        
        // Calculate running averages if applicable
        if (isset($newStats['kd_ratio']) && isset($currentStats['kd_ratio'])) {
            $updatedStats['avg_kd_ratio'] = ($currentStats['kd_ratio'] + $newStats['kd_ratio']) / 2;
        }

        $this->update([
            'performance_stats' => $updatedStats,
            'last_played' => now(),
        ]);
    }

    /**
     * Check if role is suitable for team formation
     */
    public function isSuitableForTeam(): bool
    {
        return $this->is_active && 
               $this->preference_level >= 30 && // At least 30% preference
               ($this->last_played === null || $this->last_played >= now()->subDays(30)); // Played within 30 days
    }

    /**
     * Scopes
     */
    public function scopeByGame($query, string $gameAppId)
    {
        return $query->where('game_appid', $gameAppId);
    }

    public function scopeByRole($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeBySkillLevel($query, string $skillLevel)
    {
        return $query->where('skill_level', $skillLevel);
    }

    public function scopeHighPreference($query, float $minPreference = 70)
    {
        return $query->where('preference_level', '>=', $minPreference);
    }

    public function scopeRecentlyPlayed($query, int $days = 30)
    {
        return $query->where('last_played', '>=', now()->subDays($days));
    }
}
