<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'game_appid',
        'game_name',
        'server_id',
        'creator_id',
        'max_size',
        'current_size',
        'skill_level',
        'status',
        'recruitment_status',
        'team_data',
        'recruitment_deadline',
        'average_skill_score',
        'required_roles',
        'activity_times',
        'languages',
    ];

    protected $casts = [
        'team_data' => 'array',
        'recruitment_deadline' => 'datetime',
        'average_skill_score' => 'decimal:2',
        'required_roles' => 'array',
        'activity_times' => 'array',
        'languages' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->where('status', 'active');
    }

    /**
     * Get all join requests for this team
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(TeamJoinRequest::class);
    }

    /**
     * Get pending join requests for this team
     */
    public function pendingJoinRequests(): HasMany
    {
        return $this->hasMany(TeamJoinRequest::class)->where('status', 'pending');
    }

    /**
     * Get all invitations sent for this team
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get pending (active) invitations for this team
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class)->active();
    }

    /**
     * Check if user has a pending invitation to this team
     *
     * @param User $user
     * @return bool
     */
    public function hasPendingInvitationFor(User $user): bool
    {
        return $this->pendingInvitations()
            ->where('invitee_id', $user->id)
            ->exists();
    }

    /**
     * Helper accessor to get User models directly from active members
     */
    public function getMembersUsersAttribute()
    {
        return $this->activeMembers()->with('user')->get()->pluck('user');
    }

    /**
     * Accessor for game name (fallback to attribute or 'Unknown Game')
     */
    public function getGameNameAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Map game AppID to game name
        $gameNames = [
            '730' => 'CS2',
            '570' => 'Dota 2',
            '230410' => 'Warframe',
            '1172470' => 'Apex Legends',
            '252490' => 'Rust',
            '578080' => 'PUBG',
            '359550' => 'Rainbow Six Siege',
            '433850' => 'Fall Guys',
            '548430' => 'Deep Rock Galactic',
            '493520' => 'GTFO',
        ];

        return $gameNames[$this->game_appid] ?? 'Unknown Game';
    }

    /**
     * Accessor for preferred region from team_data
     */
    public function getPreferredRegionAttribute()
    {
        return $this->team_data['preferred_region'] ?? null;
    }

    /**
     * Accessor for activity time from team_data
     */
    public function getActivityTimeAttribute()
    {
        return $this->team_data['activity_time'] ?? null;
    }

    /**
     * Accessor for communication required from team_data
     */
    public function getCommunicationRequiredAttribute()
    {
        return $this->team_data['communication_required'] ?? false;
    }

    /**
     * Check if team is accepting join requests (open recruitment)
     */
    public function isOpenForRecruitment(): bool
    {
        return $this->recruitment_status === 'open';
    }

    /**
     * Check if team is closed for recruitment (invite-only)
     */
    public function isClosedForRecruitment(): bool
    {
        return $this->recruitment_status === 'closed';
    }

    /**
     * Check if team is recruiting (accepting new members)
     * This checks both the team capacity and the recruitment status preference
     */
    public function isRecruiting(): bool
    {
        // Team must have space available
        if ($this->current_size >= $this->max_size) {
            return false;
        }

        // Team status must be 'recruiting'
        if ($this->status !== 'recruiting') {
            return false;
        }

        // Check recruitment deadline if set
        if ($this->recruitment_deadline && $this->recruitment_deadline <= now()) {
            return false;
        }

        // Check recruitment_status column (open vs closed/invite-only)
        // Use database column first, then fallback to team_data for backward compatibility
        $recruitmentStatus = $this->recruitment_status ?? $this->team_data['recruitment_status'] ?? 'open';

        // Only 'open' recruitment allows public joining
        // 'closed' means invite-only (will be enforced in join request logic)
        return $recruitmentStatus === 'open';
    }

    /**
     * Check if team is full
     */
    public function isFull(): bool
    {
        return $this->current_size >= $this->max_size;
    }

    /**
     * Add a member to the team
     *
     * @param User $user The user to add
     * @param array $memberData Additional member data
     * @param bool $bypassRecruitmentCheck Set to true to bypass recruitment status checks (for creators)
     * @return bool
     */
    public function addMember(User $user, array $memberData = [], bool $bypassRecruitmentCheck = false): bool
    {
        \Log::info('Team::addMember called', [
            'team_id' => $this->id,
            'user_id' => $user->id,
            'is_full' => $this->isFull(),
            'is_recruiting' => $this->isRecruiting(),
            'current_size' => $this->current_size,
            'max_size' => $this->max_size,
            'bypass_check' => $bypassRecruitmentCheck,
        ]);

        // Check if team can accept members (unless bypassing for creator)
        if (!$bypassRecruitmentCheck && ($this->isFull() || !$this->isRecruiting())) {
            \Log::error('Team::addMember - Team cannot accept members', [
                'is_full' => $this->isFull(),
                'is_recruiting' => $this->isRecruiting()
            ]);
            return false;
        }

        // Check if user is already a member
        if ($this->members()->where('user_id', $user->id)->exists()) {
            \Log::error('Team::addMember - User already a member', [
                'user_id' => $user->id
            ]);
            return false;
        }

        // Prepare member data with defaults
        $defaultData = [
            'user_id' => $user->id,
            'team_id' => $this->id,
            'role' => 'member',
            'game_role' => null,
            'skill_level' => null,
            'individual_skill_score' => null,
            'status' => 'active',
            'member_data' => null,
            'joined_at' => now(),
            'last_active_at' => now(),
        ];

        $finalData = array_merge($defaultData, $memberData);
        \Log::info('Team::addMember - Creating TeamMember', ['data' => $finalData]);

        try {
            // Create TeamMember record
            $teamMember = TeamMember::create($finalData);

            if (!$teamMember) {
                \Log::error('Team::addMember - TeamMember::create returned null');
                return false;
            }

            \Log::info('Team::addMember - TeamMember created', [
                'team_member_id' => $teamMember->id
            ]);

            // Update team statistics
            $this->increment('current_size');
            $this->updateAverageSkillScore();

            // Update status if team is now full
            if ($this->isFull()) {
                $this->update(['status' => 'full']);
            }

            \Log::info('Team::addMember - Success', [
                'new_current_size' => $this->fresh()->current_size
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Team::addMember - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(User $user): bool
    {
        \Log::info('Team::removeMember called', [
            'team_id' => $this->id,
            'user_id' => $user->id,
            'current_size' => $this->current_size
        ]);

        // Find the team member record
        $teamMember = $this->members()->where('user_id', $user->id)->first();

        if (!$teamMember) {
            \Log::error('Team::removeMember - TeamMember not found', [
                'team_id' => $this->id,
                'user_id' => $user->id
            ]);
            return false;
        }

        \Log::info('Team::removeMember - Deleting TeamMember', [
            'team_member_id' => $teamMember->id
        ]);

        try {
            // Hard delete - completely remove the record
            $removed = $teamMember->delete();

            if ($removed) {
                \Log::info('Team::removeMember - TeamMember deleted successfully');

                // Update team statistics
                $this->decrement('current_size');
                $this->updateAverageSkillScore();

                // Update team status if was full
                if ($this->status === 'full' && $this->current_size < $this->max_size) {
                    $this->update(['status' => 'recruiting']);
                }

                \Log::info('Team::removeMember - Success', [
                    'new_current_size' => $this->fresh()->current_size
                ]);
            } else {
                \Log::error('Team::removeMember - Delete returned false');
            }

            return (bool) $removed;
        } catch (\Exception $e) {
            \Log::error('Team::removeMember - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update average skill score based on active members
     */
    public function updateAverageSkillScore(): void
    {
        $activeMembers = $this->activeMembers()->get();
        
        if ($activeMembers->isEmpty()) {
            $this->update(['average_skill_score' => null]);
            return;
        }

        $totalScore = 0;
        $memberCount = 0;

        foreach ($activeMembers as $member) {
            if ($member->individual_skill_score !== null) {
                $totalScore += $member->individual_skill_score;
                $memberCount++;
            }
        }

        $averageScore = $memberCount > 0 ? $totalScore / $memberCount : null;
        $this->update(['average_skill_score' => $averageScore]);
    }

    /**
     * Calculate team balance score (0-100)
     */
    public function calculateBalanceScore(): float
    {
        $members = $this->activeMembers()->get();
        
        if ($members->count() < 2) {
            return 100.0; // Perfect balance with 0-1 members
        }

        $skillScores = $members->whereNotNull('individual_skill_score')
                              ->pluck('individual_skill_score')
                              ->toArray();

        if (empty($skillScores)) {
            return 50.0; // Neutral balance when no skill data available
        }

        // Calculate standard deviation of skill scores
        $mean = array_sum($skillScores) / count($skillScores);
        $variance = array_sum(array_map(function($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $skillScores)) / count($skillScores);
        
        $standardDeviation = sqrt($variance);
        
        // Convert standard deviation to balance score (lower deviation = better balance)
        // Assuming max std dev of 30 for skill scores 0-100
        $balanceScore = max(0, 100 - ($standardDeviation / 30 * 100));

        return round($balanceScore, 1);
    }

    /**
     * Get ideal game composition based on game_appid
     */
    protected function getIdealGameComposition(): array
    {
        return match($this->game_appid) {
            '730' => ['entry_fragger', 'awper', 'igl', 'lurker', 'support', 'anchor'], // CS2
            '570' => ['carry', 'mid', 'offlaner', 'soft_support', 'hard_support'], // Dota 2
            '230410' => ['dps', 'tank', 'support', 'crowd_control'], // Warframe
            '548430' => ['scout', 'driller', 'engineer', 'gunner'], // Deep Rock Galactic
            '493520' => ['scout', 'cqc', 'sniper', 'support'], // GTFO
            default => [],
        };
    }

    /**
     * Expand time range names to hour arrays
     */
    protected function expandTimeRanges(array $ranges): array
    {
        $hours = [];
        $rangeMap = [
            'early_morning' => [6, 7, 8],
            'morning' => [9, 10, 11],
            'afternoon' => [12, 13, 14, 15, 16],
            'evening' => [17, 18, 19, 20, 21],
            'night' => [22, 23, 0, 1, 2],
            'late_night' => [0, 1, 2, 3, 4, 5],
            'flexible' => range(0, 23),
        ];

        foreach ($ranges as $range) {
            if (is_numeric($range)) {
                $hours[] = (int)$range;
            } elseif (isset($rangeMap[strtolower($range)])) {
                $hours = array_merge($hours, $rangeMap[strtolower($range)]);
            }
        }

        return array_unique($hours);
    }

    /**
     * Calculate Jaccard similarity between two sets
     */
    protected function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        if (empty($set1) && empty($set2)) {
            return 1.0;
        }
        if (empty($set1) || empty($set2)) {
            return 0.0;
        }

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        return count($union) > 0 ? count($intersection) / count($union) : 0.0;
    }

    /**
     * Get required roles that have been filled by at least one member
     *
     * @return array Array of role names that are both required and assigned
     */
    public function getFilledRoles(): array
    {
        $requiredRoles = $this->required_roles ?? $this->getIdealGameComposition();

        if (empty($requiredRoles)) {
            return [];
        }

        $members = $this->activeMembers()->get();
        $assignedRoles = $members->whereNotNull('game_role')
                                 ->pluck('game_role')
                                 ->unique()
                                 ->toArray();

        return array_values(array_intersect($requiredRoles, $assignedRoles));
    }

    /**
     * Get required roles that have NOT been filled by any member
     *
     * @return array Array of role names that are required but not assigned
     */
    public function getUnfilledRoles(): array
    {
        $requiredRoles = $this->required_roles ?? $this->getIdealGameComposition();

        if (empty($requiredRoles)) {
            return [];
        }

        $members = $this->activeMembers()->get();
        $assignedRoles = $members->whereNotNull('game_role')
                                 ->pluck('game_role')
                                 ->unique()
                                 ->toArray();

        return array_values(array_diff($requiredRoles, $assignedRoles));
    }

    /**
     * Get detailed role coverage breakdown for UI display
     *
     * @return array Comprehensive role coverage details
     */
    public function getRoleCoverageDetails(): array
    {
        $requiredRoles = $this->required_roles ?? $this->getIdealGameComposition();
        $members = $this->activeMembers()->with('user')->get();

        $assignedRoles = $members->whereNotNull('game_role')
                                 ->pluck('game_role')
                                 ->unique()
                                 ->toArray();

        $filledRoles = array_values(array_intersect($requiredRoles, $assignedRoles));
        $unfilledRoles = array_values(array_diff($requiredRoles, $assignedRoles));

        // Build assignments list with member info
        $assignments = [];
        foreach ($members->whereNotNull('game_role') as $member) {
            $assignments[] = [
                'role' => $member->game_role,
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'user_name' => $member->user->display_name ?? $member->user->name,
                'is_preferred' => $member->isPreferredRole($member->game_role),
                'is_required' => in_array($member->game_role, $requiredRoles),
            ];
        }

        $coverage = empty($requiredRoles) ? 0 : (count($filledRoles) / count($requiredRoles)) * 100;

        return [
            'coverage_percent' => round($coverage, 1),
            'required_roles' => $requiredRoles,
            'filled_roles' => $filledRoles,
            'unfilled_roles' => $unfilledRoles,
            'filled_count' => count($filledRoles),
            'required_count' => count($requiredRoles),
            'assignments' => $assignments,
        ];
    }

    /**
     * Get roles that the team currently needs
     *
     * Uses new direct required_roles field or falls back to team_data.
     * Returns simple array of role names (not counts) that team is looking for.
     *
     * Phase 4 Enhancement: Uses direct required_roles field for matchmaking.
     *
     * @return array Role names needed (e.g., ['awper', 'support'])
     */
    public function getNeededRoles(): array
    {
        // Use new direct required_roles field first
        if (!empty($this->required_roles) && is_array($this->required_roles)) {
            return $this->required_roles;
        }

        // Fallback to legacy team_data format (desired_roles with counts)
        $desiredRoles = $this->team_data['desired_roles'] ?? null;

        // If team_data is explicitly empty or desired_roles not set, team is flexible (no specific needs)
        if ($desiredRoles === null || (is_array($this->team_data) && empty($this->team_data))) {
            return []; // Team has no specific role requirements
        }

        // If team has custom role preferences in legacy format, use those
        if (!empty($desiredRoles)) {
            // Get current member roles
            $currentRoles = $this->activeMembers()
                ->whereNotNull('game_role')
                ->get()
                ->pluck('game_role')
                ->countBy()
                ->toArray();

            // Calculate gaps between desired and current
            $neededRoles = [];

            foreach ($desiredRoles as $role => $desiredCount) {
                $currentCount = $currentRoles[$role] ?? 0;
                $needed = $desiredCount - $currentCount;

                if ($needed > 0) {
                    $neededRoles[$role] = $needed;
                }
            }

            return array_keys($neededRoles); // Return just role names for consistency
        }

        // If desired_roles is set but empty array, team is flexible
        return [];
    }

    /**
     * Get role requirements for different games
     */
    private function getGameRoleRequirements(): array
    {
        // Basic role requirements by game
        return match($this->game_appid) {
            '730' => [ // CS2
                'igl' => 1,
                'entry' => 1,
                'support' => 1,
                'awper' => 1,
                'anchor' => 1,
            ],
            '1172470' => [ // Apex Legends
                'igl' => 1,
                'entry' => 1,
                'support' => 1,
            ],
            '570' => [ // Dota 2
                'support' => 2,
                'dps' => 2,
                'tank' => 1,
            ],
            default => [
                'dps' => 2,
                'support' => 1,
                'tank' => 1,
            ]
        };
    }

    /**
     * Scope for teams recruiting members
     */
    public function scopeRecruiting($query)
    {
        return $query->where('status', 'recruiting')
                    ->whereColumn('current_size', '<', 'max_size')
                    ->where(function($q) {
                        $q->whereNull('recruitment_deadline')
                          ->orWhere('recruitment_deadline', '>', now());
                    });
    }

    /**
     * Scope for teams by game
     */
    public function scopeByGame($query, string $gameAppId)
    {
        return $query->where('game_appid', $gameAppId);
    }

    /**
     * Scope for teams in server
     */
    public function scopeInServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Check if team is associated with a server
     */
    public function hasServer(): bool
    {
        return $this->server_id !== null;
    }

    /**
     * Check if team is an independent team (not associated with a server)
     */
    public function isIndependent(): bool
    {
        return $this->server_id === null;
    }
}