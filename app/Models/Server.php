<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'invite_code',
        'icon_url',
        'creator_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($server) {
            if (empty($server->invite_code)) {
                $server->invite_code = static::generateUniqueInviteCode();
            }
        });
    }

    public static function generateUniqueInviteCode()
    {
        do {
            $code = Str::random(8);
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'server_members')
            ->withPivot(['joined_at', 'is_banned', 'is_muted'])
            ->withTimestamps();
    }

    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function tags()
    {
        return $this->hasMany(ServerTag::class);
    }

    public function getDefaultChannel()
    {
        return $this->channels()->where('name', 'general')->first();
    }

    public function getTagsByType($type)
    {
        return $this->tags()->where('tag_type', $type)->get();
    }

    public function hasTag($type, $value)
    {
        return $this->tags()->where('tag_type', $type)->where('tag_value', $value)->exists();
    }

    public function addTag($type, $value, $weight = 1)
    {
        return $this->tags()->updateOrCreate(
            ['tag_type' => $type, 'tag_value' => $value],
            ['weight' => $weight]
        );
    }

    public function removeTag($type, $value)
    {
        return $this->tags()->where('tag_type', $type)->where('tag_value', $value)->delete();
    }

    // Phase 3: Team Management Relationships
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function recruitingTeams()
    {
        return $this->teams()->where('teams.status', 'recruiting');
    }

    public function activeTeams()
    {
        return $this->teams()->whereIn('teams.status', ['recruiting', 'full', 'active']);
    }

    // Phase 3: Goal Management Relationships
    public function goals()
    {
        return $this->hasMany(ServerGoal::class);
    }

    public function activeGoals()
    {
        return $this->goals()->where('status', 'active');
    }

    public function completedGoals()
    {
        return $this->goals()->where('status', 'completed');
    }

    // Phase 3: Achievement Leaderboard Relationships
    public function achievementLeaderboards()
    {
        return $this->hasMany(AchievementLeaderboard::class);
    }

    public function getLeaderboardForGame($gameAppId)
    {
        return $this->achievementLeaderboards()
            ->where('game_appid', $gameAppId)
            ->orderBy('rank_position')
            ->get();
    }

    // Phase 3: Matchmaking Relationships
    public function matchmakingRequests()
    {
        return $this->hasMany(MatchmakingRequest::class);
    }

    public function activeMatchmakingRequests()
    {
        return $this->matchmakingRequests()->where('status', 'active');
    }

    // Phase 3: Team Management Helper Methods
    public function createTeam(User $creator, array $teamData)
    {
        return $this->teams()->create(array_merge($teamData, [
            'creator_id' => $creator->id,
            'current_size' => 1,
            'status' => 'recruiting',
        ]));
    }

    public function getTeamsForGame($gameAppId)
    {
        return $this->teams()
            ->where('game_appid', $gameAppId)
            ->with(['creator', 'activeMembers.user'])
            ->get();
    }

    public function getTeamStatistics()
    {
        $teams = $this->teams();
        
        return [
            'total_teams' => $teams->count(),
            'recruiting_teams' => $teams->where('status', 'recruiting')->count(),
            'active_teams' => $teams->whereIn('status', ['recruiting', 'full', 'active'])->count(),
            'total_team_members' => $teams->sum('current_size'),
            'average_team_size' => $teams->avg('current_size'),
            'teams_by_game' => $teams->selectRaw('game_name, COUNT(*) as count')
                                   ->groupBy('game_name')
                                   ->pluck('count', 'game_name'),
        ];
    }

    // Phase 3: Goal Management Helper Methods
    public function createGoal(User $creator, array $goalData)
    {
        return $this->goals()->create(array_merge($goalData, [
            'creator_id' => $creator->id,
            'current_progress' => 0,
            'completion_percentage' => 0,
            'participant_count' => 0,
        ]));
    }

    public function getGoalsForGame($gameAppId)
    {
        return $this->goals()
            ->where('game_appid', $gameAppId)
            ->with(['creator', 'activeParticipants.user', 'milestones'])
            ->get();
    }

    public function getGoalStatistics()
    {
        $goals = $this->goals();
        
        return [
            'total_goals' => $goals->count(),
            'active_goals' => $goals->where('status', 'active')->count(),
            'completed_goals' => $goals->where('status', 'completed')->count(),
            'total_participants' => $goals->sum('participant_count'),
            'completion_rate' => $goals->count() > 0 
                ? ($goals->where('status', 'completed')->count() / $goals->count()) * 100 
                : 0,
            'average_participation' => $goals->avg('participant_count'),
            'goals_by_type' => $goals->selectRaw('goal_type, COUNT(*) as count')
                                   ->groupBy('goal_type')
                                   ->pluck('count', 'goal_type'),
            'goals_by_difficulty' => $goals->selectRaw('difficulty, COUNT(*) as count')
                                          ->groupBy('difficulty')
                                          ->pluck('count', 'difficulty'),
        ];
    }

    // Phase 3: Achievement & Competition Methods
    public function updateMemberLeaderboards($gameAppId = null)
    {
        $members = $this->members()->with('profile')->get();
        
        foreach ($members as $member) {
            if ($gameAppId) {
                AchievementLeaderboard::updateUserPosition($member, $gameAppId, $this);
            } else {
                // Update for all games the user plays
                $steamData = $member->profile->steam_data ?? [];
                $games = $steamData['games'] ?? [];
                
                foreach ($games as $game) {
                    if (($game['playtime_forever'] ?? 0) > 300) { // 5+ hours
                        AchievementLeaderboard::updateUserPosition($member, $game['appid'], $this);
                    }
                }
            }
        }
    }

    public function getTopAchievers($limit = 10)
    {
        return AchievementLeaderboard::getServerLeaders($this, $limit);
    }

    public function getGamingActivity()
    {
        $memberPreferences = $this->members()
            ->with('gamingPreferences')
            ->get()
            ->flatMap->gamingPreferences;

        return $memberPreferences->groupBy('game_appid')
            ->map(function ($preferences) {
                return [
                    'game_name' => $preferences->first()->game_name,
                    'player_count' => $preferences->count(),
                    'total_playtime' => $preferences->sum('playtime_forever'),
                    'avg_playtime' => $preferences->avg('playtime_forever'),
                    'recent_activity' => $preferences->where('playtime_2weeks', '>', 0)->count(),
                ];
            })
            ->sortByDesc('player_count')
            ->take(10);
    }

    // Phase 3: Matchmaking Helper Methods
    public function getMatchmakingActivity()
    {
        return [
            'active_requests' => $this->activeMatchmakingRequests()->count(),
            'recent_teams_formed' => $this->teams()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'popular_games' => $this->matchmakingRequests()
                ->selectRaw('game_name, COUNT(*) as request_count')
                ->groupBy('game_name')
                ->orderByDesc('request_count')
                ->limit(5)
                ->get(),
        ];
    }

    public function findTeamForUser(User $user, $gameAppId)
    {
        return $this->teams()
            ->where('game_appid', $gameAppId)
            ->where('status', 'recruiting')
            ->whereDoesntHave('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('current_size', '<', 'max_size')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    // Phase 3: Advanced Analytics
    public function getServerInsights()
    {
        return [
            'member_activity' => [
                'total_members' => $this->members()->count(),
                'active_members' => $this->members()
                    ->whereHas('gamingSessions', function ($query) {
                        $query->where('started_at', '>=', now()->subDays(7));
                    })
                    ->count(),
                'new_members' => $this->members()
                    ->wherePivot('joined_at', '>=', now()->subDays(30))
                    ->count(),
            ],
            'gaming_stats' => $this->getGamingActivity(),
            'team_stats' => $this->getTeamStatistics(),
            'goal_stats' => $this->getGoalStatistics(),
            'matchmaking_stats' => $this->getMatchmakingActivity(),
            'achievement_stats' => [
                'total_achievements' => $this->achievementLeaderboards()->sum('achievement_count'),
                'avg_completion' => $this->achievementLeaderboards()->avg('completion_percentage'),
                'top_achievers' => $this->getTopAchievers(5),
            ],
        ];
    }

    // Phase 3: Community Health Metrics
    public function getCommunityHealth()
    {
        $memberCount = $this->members()->count();
        $activeTeams = $this->activeTeams()->count();
        $activeGoals = $this->activeGoals()->count();
        $recentActivity = $this->members()
            ->whereHas('gamingSessions', function ($query) {
                $query->where('started_at', '>=', now()->subDays(3));
            })
            ->count();

        $healthScore = 0;
        $healthScore += min(50, $memberCount * 2); // Up to 50 points for members
        $healthScore += min(25, $activeTeams * 5); // Up to 25 points for teams
        $healthScore += min(15, $activeGoals * 3); // Up to 15 points for goals
        $healthScore += min(10, ($recentActivity / max($memberCount, 1)) * 10); // Up to 10 points for activity

        return [
            'health_score' => round($healthScore, 1),
            'member_count' => $memberCount,
            'active_teams' => $activeTeams,
            'active_goals' => $activeGoals,
            'recent_activity_percentage' => $memberCount > 0 
                ? round(($recentActivity / $memberCount) * 100, 1) 
                : 0,
            'recommendations' => $this->getHealthRecommendations($healthScore),
        ];
    }

    private function getHealthRecommendations($healthScore)
    {
        $recommendations = [];

        if ($healthScore < 30) {
            $recommendations[] = 'Create engaging server goals to boost participation';
            $recommendations[] = 'Form teams for popular games to increase collaboration';
        }

        if ($this->activeGoals()->count() < 2) {
            $recommendations[] = 'Add community challenges to keep members engaged';
        }

        if ($this->activeTeams()->count() < 3) {
            $recommendations[] = 'Encourage team formation for better gaming experiences';
        }

        return $recommendations;
    }

    public function getCompatibilityScore(User $user): float
    {
        $userPreferences = $user->gamingPreferences;
        if ($userPreferences->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0;
        $maxScore = 0;

        foreach ($userPreferences as $preference) {
            $gameTag = ServerTag::getGameTags()[$preference->game_appid] ?? null;
            if ($gameTag && $this->hasTag('game', $gameTag)) {
                $score = $preference->getPreferenceWeight() * $preference->getPlaytimeHours();
                $totalScore += $score;
            }
            $maxScore += $preference->getPreferenceWeight() * $preference->getPlaytimeHours();
        }

        return $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0.0;
    }
}