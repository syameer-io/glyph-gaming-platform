<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'display_name',
        'email',
        'password',
        'steam_id',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function friendRequests()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')
            ->wherePivot('status', 'pending')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_members')
            ->withPivot(['joined_at', 'is_banned', 'is_muted'])
            ->withTimestamps();
    }

    public function createdServers()
    {
        return $this->hasMany(Server::class, 'creator_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('server_id')
            ->withTimestamps();
    }

    public function hasRole($roleName, $serverId)
    {
        return $this->roles()
            ->where('user_roles.server_id', $serverId)
            ->where('name', $roleName)
            ->exists();
    }

    public function gamingPreferences()
    {
        return $this->hasMany(UserGamingPreference::class);
    }

    /**
     * Gaming preferences that have available join configurations
     * Only shows games the user can actually create lobbies for
     */
    public function gamingPreferencesWithJoinConfigs()
    {
        return $this->hasMany(UserGamingPreference::class)
            ->whereIn('game_appid', function ($query) {
                $query->select('game_id')
                    ->from('game_join_configurations')
                    ->where('is_enabled', true);
            })
            ->orderBy('playtime_forever', 'desc');
    }

    public function gamingSessions()
    {
        return $this->hasMany(GamingSession::class);
    }

    public function gameLobbies()
    {
        return $this->hasMany(GameLobby::class);
    }

    public function isServerAdmin($serverId)
    {
        // Check if user is the server creator
        $server = \App\Models\Server::find($serverId);
        if ($server && $server->creator_id === $this->id) {
            return true;
        }
        
        // Check if user has Server Admin role
        return $this->hasRole('Server Admin', $serverId);
    }

    public function getTopGames($limit = 3)
    {
        return $this->gamingPreferences()
            ->orderBy('playtime_forever', 'desc')
            ->limit($limit)
            ->get();
    }

    // Phase 3: Team Management Relationships
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot([
                'role', 'game_role', 'skill_level', 'individual_skill_score', 
                'joined_at', 'left_at', 'status'
            ])
            ->withTimestamps();
    }

    public function createdTeams()
    {
        return $this->hasMany(Team::class, 'creator_id');
    }

    public function activeTeams()
    {
        return $this->teams()->whereIn('teams.status', ['recruiting', 'full', 'active']);
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

    // Phase 3: Gaming Role Preferences
    public function playerGameRoles()
    {
        return $this->hasMany(PlayerGameRole::class);
    }

    public function getPreferredRoles($gameAppId)
    {
        // Get the player game role record for this game
        $playerGameRole = $this->playerGameRoles()
            ->where('game_appid', $gameAppId)
            ->first();

        if (!$playerGameRole) {
            return [];
        }

        // Build preferred roles array from the schema fields
        $preferredRoles = [];

        // Add primary role if exists
        if ($playerGameRole->primary_role) {
            $preferredRoles[] = $playerGameRole->primary_role;
        }

        // Add secondary role if exists and different from primary
        if ($playerGameRole->secondary_role && $playerGameRole->secondary_role !== $playerGameRole->primary_role) {
            $preferredRoles[] = $playerGameRole->secondary_role;
        }

        // Add any additional roles from the JSON preferred_roles field
        if ($playerGameRole->preferred_roles && is_array($playerGameRole->preferred_roles)) {
            foreach ($playerGameRole->preferred_roles as $role) {
                if (!in_array($role, $preferredRoles)) {
                    $preferredRoles[] = $role;
                }
            }
        }

        return $preferredRoles;
    }

    // Phase 3: Goal Participation
    public function goalParticipations()
    {
        return $this->hasMany(GoalParticipant::class);
    }

    public function activeGoalParticipations()
    {
        return $this->goalParticipations()->where('participation_status', 'active');
    }

    public function createdGoals()
    {
        return $this->hasMany(ServerGoal::class, 'creator_id');
    }

    // Phase 3: Achievement Leaderboards
    public function achievementLeaderboards()
    {
        return $this->hasMany(AchievementLeaderboard::class);
    }

    public function getLeaderboardRank($serverId, $gameAppId)
    {
        return $this->achievementLeaderboards()
            ->where('server_id', $serverId)
            ->where('game_appid', $gameAppId)
            ->first();
    }

    // Phase 3: Team Management Helper Methods
    public function isTeamLeader(Team $team)
    {
        return $team->creator_id === $this->id;
    }

    public function canManageTeam(Team $team)
    {
        return $this->isTeamLeader($team) || 
               $this->teams()->where('team_id', $team->id)->wherePivot('role', 'co_leader')->exists();
    }

    public function leaveTeam(Team $team)
    {
        $teamMember = $this->teams()->where('team_id', $team->id)->first();
        if ($teamMember) {
            $teamMember->pivot->update([
                'status' => 'left',
                'left_at' => now()
            ]);
            $team->decrement('current_size');
        }
    }

    // Phase 3: Goal Management Helper Methods
    public function joinGoal(ServerGoal $goal)
    {
        if (!$goal->canUserParticipate($this)) {
            return false;
        }

        $skillScore = $this->getSkillScoreForGame($goal->game_appid);

        $this->goalParticipations()->create([
            'goal_id' => $goal->id,
            'individual_progress' => 0,
            'participation_status' => 'active',
            'joined_at' => now(),
            'last_activity_at' => now(),
            'skill_score_at_start' => $skillScore,
            'current_skill_score' => $skillScore,
        ]);

        $goal->increment('participant_count');
        return true;
    }

    public function leaveGoal(ServerGoal $goal)
    {
        $participation = $this->goalParticipations()
            ->where('goal_id', $goal->id)
            ->where('participation_status', 'active')
            ->first();

        if ($participation) {
            $participation->update(['participation_status' => 'left']);
            $goal->decrement('participant_count');
            return true;
        }

        return false;
    }

    // Phase 3: Skill Assessment Methods
    public function getSkillScoreForGame($gameAppId)
    {
        $steamData = $this->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];
        
        return $skillMetrics[$gameAppId]['skill_score'] ?? 50;
    }

    public function getOverallSkillLevel()
    {
        $scores = [];
        $steamData = $this->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];

        foreach ($skillMetrics as $gameData) {
            if (isset($gameData['skill_score'])) {
                $scores[] = $gameData['skill_score'];
            }
        }

        if (empty($scores)) {
            return 'unranked';
        }

        $averageScore = array_sum($scores) / count($scores);

        return match(true) {
            $averageScore >= 80 => 'expert',
            $averageScore >= 60 => 'advanced',
            $averageScore >= 40 => 'intermediate',
            default => 'beginner'
        };
    }

    // Phase 3: Matchmaking Helper Methods
    public function createMatchmakingRequest($gameAppId, $requestType, $preferences = [])
    {
        // Cancel any existing active requests for the same game
        $this->activeMatchmakingRequests()
            ->where('game_appid', $gameAppId)
            ->update(['status' => 'cancelled']);

        $skillScore = $this->getSkillScoreForGame($gameAppId);

        return $this->matchmakingRequests()->create([
            'game_appid' => $gameAppId,
            'game_name' => $preferences['game_name'] ?? 'Unknown Game',
            'request_type' => $requestType,
            'skill_score' => $skillScore,
            'preferred_roles' => $this->getPreferredRoles($gameAppId),
            'skill_range' => $preferences['skill_range'] ?? 20,
            'server_id' => $preferences['server_id'] ?? null,
            'message' => $preferences['message'] ?? null,
            'max_team_size' => $preferences['max_team_size'] ?? 5,
            'status' => 'active',
            'preferences' => $preferences,
        ]);
    }

    // Phase 3: Gaming Statistics
    public function getGamingStatistics()
    {
        return [
            'teams_joined' => $this->teams()->count(),
            'teams_created' => $this->createdTeams()->count(),
            'goals_participated' => $this->goalParticipations()->count(),
            'goals_created' => $this->createdGoals()->count(),
            'active_matchmaking_requests' => $this->activeMatchmakingRequests()->count(),
            'overall_skill_level' => $this->getOverallSkillLevel(),
            'favorite_games' => $this->getTopGames(5),
            'total_achievements' => $this->achievementLeaderboards()->sum('achievement_count'),
            'average_completion' => $this->achievementLeaderboards()->avg('completion_percentage'),
        ];
    }

    public function updateGamingPreferences($steamGames)
    {
        foreach ($steamGames as $game) {
            $this->gamingPreferences()->updateOrCreate(
                [
                    'game_appid' => $game['appid'],
                ],
                [
                    'game_name' => $game['name'],
                    'playtime_forever' => $game['playtime_forever'] ?? 0,
                    'playtime_2weeks' => $game['playtime_2weeks'] ?? 0,
                    'last_played' => isset($game['rtime_last_played']) 
                        ? \Carbon\Carbon::createFromTimestamp($game['rtime_last_played'])
                        : null,
                ]
            );
        }
    }
}