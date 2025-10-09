<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
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
        'team_data',
        'recruitment_deadline',
        'average_skill_score',
    ];

    protected $casts = [
        'team_data' => 'array',
        'recruitment_deadline' => 'datetime',
        'average_skill_score' => 'decimal:2',
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
     * Accessor for recruitment status from team_data
     */
    public function getRecruitmentStatusAttribute()
    {
        return $this->team_data['recruitment_status'] ?? ($this->status === 'recruiting' ? 'open' : 'closed');
    }

    /**
     * Check if team is recruiting
     */
    public function isRecruiting(): bool
    {
        return $this->status === 'recruiting' && 
               $this->current_size < $this->max_size &&
               (!$this->recruitment_deadline || $this->recruitment_deadline > now());
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
     */
    public function addMember(User $user, array $memberData = []): bool
    {
        \Log::info('Team::addMember called', [
            'team_id' => $this->id,
            'user_id' => $user->id,
            'is_full' => $this->isFull(),
            'is_recruiting' => $this->isRecruiting(),
            'current_size' => $this->current_size,
            'max_size' => $this->max_size
        ]);

        // Check if team can accept members
        if ($this->isFull() || !$this->isRecruiting()) {
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
     * Get role distribution for team balance
     */
    public function getRoleDistribution(): array
    {
        $members = $this->activeMembers()->get();
        $distribution = [];

        foreach ($members as $member) {
            $role = $member->game_role ?? 'unassigned';
            $distribution[$role] = ($distribution[$role] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Check if team needs specific roles
     */
    public function getNeededRoles(): array
    {
        $distribution = $this->getRoleDistribution();
        $needed = [];

        // Game-specific role requirements
        $gameRoleRequirements = $this->getGameRoleRequirements();

        foreach ($gameRoleRequirements as $role => $minRequired) {
            $currentCount = $distribution[$role] ?? 0;
            if ($currentCount < $minRequired) {
                $needed[$role] = $minRequired - $currentCount;
            }
        }

        return $needed;
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
}