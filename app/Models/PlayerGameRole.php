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
        'preferred_roles',
        'role_ratings',
        'primary_role',
        'secondary_role',
        'experience_level',
        'overall_skill_rating',
        'availability_pattern',
        'playstyle_preferences',
        'communication_preferences',
        'open_to_coaching',
        'open_to_leading',
        'additional_notes',
        'last_updated_from_steam',
    ];

    protected $casts = [
        'preferred_roles' => 'array',
        'role_ratings' => 'array',
        'overall_skill_rating' => 'decimal:2',
        'availability_pattern' => 'array',
        'playstyle_preferences' => 'array',
        'communication_preferences' => 'array',
        'open_to_coaching' => 'boolean',
        'open_to_leading' => 'boolean',
        'last_updated_from_steam' => 'datetime',
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
        return match($this->experience_level) {
            'beginner' => 'text-green-500',
            'intermediate' => 'text-yellow-500',
            'advanced' => 'text-orange-500',
            'expert' => 'text-red-500',
            default => 'text-gray-500'
        };
    }

    /**
     * Get skill rating badge class
     */
    public function getSkillRatingBadgeClass(): string
    {
        $rating = $this->overall_skill_rating ?? 0;

        return match(true) {
            $rating >= 80 => 'bg-red-100 text-red-800',
            $rating >= 60 => 'bg-yellow-100 text-yellow-800',
            $rating >= 40 => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Get role compatibility with another role
     */
    public function getCompatibilityWith(self $otherRole): float
    {
        if ($this->primary_role === $otherRole->primary_role) {
            return 20; // Same role = low compatibility (need diversity)
        }

        // Check for complementary roles
        $complementaryRoles = $this->getComplementaryRoles();

        if (in_array($otherRole->primary_role, $complementaryRoles)) {
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
        $role = $this->primary_role ?? '';

        return match($role) {
            // FPS games (CS2, Valorant, R6S)
            'entry_fragger', 'entry' => ['support', 'igl', 'anchor'],
            'support' => ['entry_fragger', 'awper', 'lurker', 'entry', 'rifler'],
            'awper' => ['support', 'entry_fragger', 'igl', 'entry'],
            'igl' => ['entry_fragger', 'support', 'anchor', 'entry'],
            'lurker' => ['support', 'anchor', 'igl'],
            'anchor' => ['entry_fragger', 'igl', 'lurker', 'entry'],
            'rifler' => ['support', 'awper', 'igl'],

            // MOBA games (Dota 2, LoL)
            'carry' => ['support', 'initiator', 'jungler'],
            'mid' => ['support', 'carry', 'offlaner'],
            'offlaner' => ['support', 'carry', 'mid'],
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

            // Flex role
            'flex' => ['dps', 'support', 'tank', 'carry', 'entry', 'anchor'],

            default => []
        };
    }

    /**
     * Get numeric skill score for calculations
     */
    public function getSkillScore(): float
    {
        // Use overall_skill_rating if available, otherwise derive from experience_level
        if ($this->overall_skill_rating !== null) {
            return (float) $this->overall_skill_rating;
        }

        return match($this->experience_level) {
            'expert' => 85,
            'advanced' => 65,
            'intermediate' => 45,
            'beginner' => 25,
            default => 50
        };
    }

    /**
     * Update role ratings from gameplay data
     */
    public function updateRoleRatings(array $newRatings): void
    {
        $currentRatings = $this->role_ratings ?? [];

        // Merge new ratings with existing
        $updatedRatings = array_merge($currentRatings, $newRatings);

        // Calculate running averages if applicable
        if (isset($newRatings['performance_score']) && isset($currentRatings['performance_score'])) {
            $updatedRatings['avg_performance'] = ($currentRatings['performance_score'] + $newRatings['performance_score']) / 2;
        }

        $this->update([
            'role_ratings' => $updatedRatings,
            'last_updated_from_steam' => now(),
        ]);
    }

    /**
     * Check if role is suitable for team formation
     */
    public function isSuitableForTeam(): bool
    {
        // Check if has primary role and reasonable skill rating
        return $this->primary_role !== null &&
               ($this->overall_skill_rating === null || $this->overall_skill_rating >= 20) && // At least 20% skill rating
               ($this->last_updated_from_steam === null || $this->last_updated_from_steam >= now()->subDays(60)); // Updated within 60 days
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
        return $query->where('primary_role', $roleName);
    }

    public function scopeByExperienceLevel($query, string $experienceLevel)
    {
        return $query->where('experience_level', $experienceLevel);
    }

    public function scopeHighSkillRating($query, float $minRating = 60)
    {
        return $query->where('overall_skill_rating', '>=', $minRating);
    }

    public function scopeRecentlyUpdated($query, int $days = 60)
    {
        return $query->where('last_updated_from_steam', '>=', now()->subDays($days));
    }

    public function scopeOpenToCoaching($query)
    {
        return $query->where('open_to_coaching', true);
    }

    public function scopeOpenToLeading($query)
    {
        return $query->where('open_to_leading', true);
    }
}
