<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Get the user's status record.
     * Phase 2: Member List Enhancement
     */
    public function userStatus()
    {
        return $this->hasOne(UserStatus::class);
    }

    /**
     * Get the current status for the user.
     * Returns the UserStatus status if set, otherwise falls back to Profile status.
     *
     * Phase 2: Member List Enhancement
     *
     * @return string One of: 'online', 'idle', 'dnd', 'offline'
     */
    public function getCurrentStatus(): string
    {
        // Check UserStatus first
        if ($this->userStatus && $this->userStatus->status) {
            return $this->userStatus->status;
        }

        // Fallback to Profile status (legacy)
        if ($this->profile && $this->profile->status) {
            return $this->profile->status === 'online' ? 'online' : 'offline';
        }

        return 'offline';
    }

    /**
     * Get the status color for UI rendering.
     *
     * Phase 2: Member List Enhancement
     *
     * @return string Hex color code
     */
    public function getStatusColor(): string
    {
        $status = $this->getCurrentStatus();
        return UserStatus::STATUS_COLORS[$status] ?? UserStatus::STATUS_COLORS['offline'];
    }

    /**
     * Get display activity for the user.
     * Returns Steam current game or custom status text.
     *
     * Phase 2: Member List Enhancement
     *
     * @return string|null Activity description or null if none
     */
    public function getDisplayActivity(): ?string
    {
        // Priority 1: Steam "Currently Playing" status
        if ($this->profile && $this->profile->current_game) {
            return 'Playing ' . $this->profile->current_game;
        }

        // Priority 2: Custom status (if not expired)
        if ($this->userStatus && $this->userStatus->hasCustomStatus()) {
            return $this->userStatus->getFullCustomStatus();
        }

        return null;
    }

    /**
     * Check if user is currently playing a game (Steam).
     *
     * Phase 2: Member List Enhancement
     *
     * @return bool
     */
    public function isPlayingGame(): bool
    {
        return $this->profile && !empty($this->profile->current_game);
    }

    /**
     * Get the custom status (emoji + text) if set.
     *
     * Phase 2: Member List Enhancement
     *
     * @return array|null ['emoji' => string|null, 'text' => string|null]
     */
    public function getCustomStatus(): ?array
    {
        if (!$this->userStatus || !$this->userStatus->hasCustomStatus()) {
            return null;
        }

        return [
            'emoji' => $this->userStatus->custom_emoji,
            'text' => $this->userStatus->custom_text,
        ];
    }

    /**
     * Get the user's avatar URL with fallback to UI Avatars.
     *
     * @return string Avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        // Check if profile has an avatar
        if ($this->profile && !empty($this->profile->avatar_url)) {
            return $this->profile->avatar_url;
        }

        // Fallback to UI Avatars service using display name or username
        $name = $this->display_name ?? $this->username ?? 'User';
        $encodedName = urlencode($name);

        return "https://ui-avatars.com/api/?name={$encodedName}&background=5865F2&color=fff&size=128&bold=true";
    }

    /**
     * Set the user's status.
     *
     * Phase 2: Member List Enhancement
     *
     * @param string $status One of: 'online', 'idle', 'dnd', 'offline'
     * @return UserStatus
     */
    public function setStatus(string $status): UserStatus
    {
        return UserStatus::setStatus($this->id, $status);
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

    /**
     * Check if this user is friends with another user.
     * Checks both directions of the friendship relation.
     *
     * @param User $otherUser The user to check friendship with
     * @return bool True if they are accepted friends
     */
    public function isFriendWith(User $otherUser): bool
    {
        // Check if this user sent friend request that was accepted
        $sentFriendship = $this->friends()
            ->where('friend_id', $otherUser->id)
            ->wherePivot('status', 'accepted')
            ->exists();

        if ($sentFriendship) {
            return true;
        }

        // Check if other user sent friend request that was accepted
        return $otherUser->friends()
            ->where('friend_id', $this->id)
            ->wherePivot('status', 'accepted')
            ->exists();
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

    // =========================================================================
    // Phase 1 - Role Permissions System
    // =========================================================================

    /**
     * Get all roles for a user in a specific server, ordered by position descending.
     * Higher position = more authority.
     *
     * @param int $serverId The server ID
     * @return Collection Collection of Role models
     */
    public function getServerRoles(int $serverId): Collection
    {
        return $this->roles()
            ->wherePivot('server_id', $serverId)
            ->orderByDesc('position')
            ->get();
    }

    /**
     * Get the highest role position for this user in a server.
     * Used for hierarchy checks (can only manage users with lower position).
     *
     * @param int $serverId The server ID
     * @return int The highest position, or 0 if no roles
     */
    public function getHighestRolePosition(int $serverId): int
    {
        $highestRole = $this->roles()
            ->wherePivot('server_id', $serverId)
            ->orderByDesc('position')
            ->first();

        return $highestRole ? $highestRole->position : 0;
    }

    /**
     * Check if user has a specific permission in a server.
     * Optionally checks channel-specific overrides.
     *
     * Permission resolution order:
     * 1. Server creator ALWAYS returns true
     * 2. Check cache for computed permissions
     * 3. If cache miss, compute permissions from all roles
     * 4. Apply channel overrides if channelId provided (deny takes precedence)
     *
     * @param string $permission The permission key to check
     * @param int $serverId The server ID
     * @param int|null $channelId Optional channel ID for channel-specific overrides
     * @return bool
     */
    public function hasServerPermission(string $permission, int $serverId, ?int $channelId = null): bool
    {
        // Server creator ALWAYS has all permissions
        $server = Server::find($serverId);
        if ($server && $server->creator_id === $this->id) {
            return true;
        }

        // Get computed permissions (cached)
        $permissions = $this->computeServerPermissions($serverId, $channelId);

        // Check for administrator permission first (grants all)
        $adminPermission = config('permissions.administrator', 'administrator');
        if (in_array($adminPermission, $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Compute aggregated permissions for a user in a server.
     * Combines permissions from all user's roles, with channel overrides.
     *
     * Channel override rules:
     * - 'deny' takes precedence over 'allow'
     * - 'inherit' uses role's default
     *
     * Results are cached for 5 minutes (configurable via config/permissions.php).
     *
     * @param int $serverId The server ID
     * @param int|null $channelId Optional channel ID for channel-specific overrides
     * @return array Array of permission keys the user has
     */
    public function computeServerPermissions(int $serverId, ?int $channelId = null): array
    {
        // Build cache key
        $cacheKey = $channelId
            ? "user_{$this->id}_server_{$serverId}_channel_{$channelId}_permissions"
            : "user_{$this->id}_server_{$serverId}_permissions";

        $cacheTtl = config('permissions.cache_ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($serverId, $channelId) {
            // Get all user's roles in this server
            $roles = $this->getServerRoles($serverId);

            if ($roles->isEmpty()) {
                return [];
            }

            // Aggregate base permissions from all roles
            $basePermissions = [];
            foreach ($roles as $role) {
                $rolePermissions = $role->permissions ?? [];
                $basePermissions = array_merge($basePermissions, $rolePermissions);
            }
            $basePermissions = array_unique($basePermissions);

            // If no channel specified, return base permissions
            if ($channelId === null) {
                return array_values($basePermissions);
            }

            // Apply channel overrides
            // Track allowed and denied permissions from overrides
            $channelAllowed = [];
            $channelDenied = [];

            foreach ($roles as $role) {
                $overrides = $role->getChannelOverrides($channelId);
                foreach ($overrides as $override) {
                    if ($override->isDeny()) {
                        $channelDenied[] = $override->permission;
                    } elseif ($override->isAllow()) {
                        $channelAllowed[] = $override->permission;
                    }
                    // 'inherit' does nothing - uses base permission
                }
            }

            // Deny takes precedence: remove denied permissions
            $effectivePermissions = array_diff($basePermissions, $channelDenied);

            // Add explicitly allowed permissions (that weren't denied)
            foreach ($channelAllowed as $permission) {
                if (!in_array($permission, $channelDenied)) {
                    $effectivePermissions[] = $permission;
                }
            }

            return array_values(array_unique($effectivePermissions));
        });
    }

    /**
     * Check if user can manage another user based on role hierarchy.
     * Users can only manage other users with STRICTLY lower role positions.
     *
     * @param User $targetUser The user to potentially manage
     * @param int $serverId The server ID
     * @return bool
     */
    public function canManageUser(User $targetUser, int $serverId): bool
    {
        // Server creator can manage anyone
        $server = Server::find($serverId);
        if ($server && $server->creator_id === $this->id) {
            return true;
        }

        // Cannot manage yourself
        if ($this->id === $targetUser->id) {
            return false;
        }

        // Cannot manage the server creator
        if ($server && $server->creator_id === $targetUser->id) {
            return false;
        }

        // Get both users' highest role positions
        $myPosition = $this->getHighestRolePosition($serverId);
        $targetPosition = $targetUser->getHighestRolePosition($serverId);

        // Can only manage users with STRICTLY lower position
        return $myPosition > $targetPosition;
    }

    /**
     * Check if user can manage a role based on hierarchy.
     * Users can only manage roles with STRICTLY lower positions than their highest role.
     *
     * @param Role $role The role to potentially manage
     * @param int $serverId The server ID
     * @return bool
     */
    public function canManageRole(Role $role, int $serverId): bool
    {
        // Server creator can manage any role
        $server = Server::find($serverId);
        if ($server && $server->creator_id === $this->id) {
            return true;
        }

        // Get user's highest role position
        $myPosition = $this->getHighestRolePosition($serverId);

        // Can only manage roles with STRICTLY lower position
        return $myPosition > $role->position;
    }

    /**
     * Invalidate permission cache for this user.
     * Called when user's roles change.
     *
     * @param int|null $serverId Optional specific server ID (null clears all)
     * @return void
     */
    public function invalidatePermissionCache(?int $serverId = null): void
    {
        if ($serverId !== null) {
            // Clear cache for specific server
            Cache::forget("user_{$this->id}_server_{$serverId}_permissions");
            Cache::forget("user_{$this->id}_server_{$serverId}_channel_permissions");

            // Clear channel-specific caches for this server
            // We need to get all channels in the server and clear those caches
            $server = Server::with('channels')->find($serverId);
            if ($server) {
                foreach ($server->channels as $channel) {
                    Cache::forget("user_{$this->id}_server_{$serverId}_channel_{$channel->id}_permissions");
                }
            }
        } else {
            // Clear cache for all servers user is a member of
            $serverIds = $this->servers()->pluck('servers.id');
            foreach ($serverIds as $sid) {
                Cache::forget("user_{$this->id}_server_{$sid}_permissions");
                Cache::forget("user_{$this->id}_server_{$sid}_channel_permissions");

                // Clear channel-specific caches
                $server = Server::with('channels')->find($sid);
                if ($server) {
                    foreach ($server->channels as $channel) {
                        Cache::forget("user_{$this->id}_server_{$sid}_channel_{$channel->id}_permissions");
                    }
                }
            }
        }
    }

    /**
     * Check if user has any of the specified permissions in a server.
     *
     * @param array $permissions Array of permission keys
     * @param int $serverId The server ID
     * @param int|null $channelId Optional channel ID
     * @return bool
     */
    public function hasAnyServerPermission(array $permissions, int $serverId, ?int $channelId = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasServerPermission($permission, $serverId, $channelId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the specified permissions in a server.
     *
     * @param array $permissions Array of permission keys
     * @param int $serverId The server ID
     * @param int|null $channelId Optional channel ID
     * @return bool
     */
    public function hasAllServerPermissions(array $permissions, int $serverId, ?int $channelId = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasServerPermission($permission, $serverId, $channelId)) {
                return false;
            }
        }
        return true;
    }

    // =========================================================================
    // End Phase 1 - Role Permissions System
    // =========================================================================

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

    /**
     * Get combined list of games for lobby creation
     * Returns user's owned games first, then all other supported games
     *
     * This allows users to create lobbies for any supported game,
     * while prioritizing games they actually own and play.
     *
     * @return \Illuminate\Support\Collection Collection of game objects with:
     *         - game_id: Steam App ID
     *         - game_name: Display name of the game
     *         - is_owned: Whether user owns this game
     *         - playtime: Hours played (only if owned)
     */
    public function getCombinedLobbyGames(): \Illuminate\Support\Collection
    {
        // Load user's gaming preferences with join configurations
        $userGames = $this->gamingPreferencesWithJoinConfigs()
            ->get()
            ->map(function ($preference) {
                return [
                    'game_id' => $preference->game_appid,
                    'game_name' => $preference->game_name,
                    'is_owned' => true,
                    'playtime' => round($preference->playtime_forever / 60, 1), // Convert minutes to hours
                ];
            });

        // Get all enabled games from join configurations
        $allSupportedGames = \App\Models\GameJoinConfiguration::select('game_id', 'steam_app_id')
            ->where('is_enabled', true)
            ->distinct()
            ->get()
            ->groupBy('game_id'); // Group by game_id to get unique games

        // Get unique game IDs and their names from configurations
        $supportedGamesList = collect();
        foreach ($allSupportedGames as $gameId => $configs) {
            $supportedGamesList->push([
                'game_id' => $gameId,
                'game_name' => $this->getGameNameById($gameId),
                'is_owned' => false,
                'playtime' => null,
            ]);
        }

        // Get user's owned game IDs for filtering
        $ownedGameIds = $userGames->pluck('game_id')->toArray();

        // Filter out games user already owns from the supported list
        $otherSupportedGames = $supportedGamesList->filter(function ($game) use ($ownedGameIds) {
            return !in_array($game['game_id'], $ownedGameIds);
        });

        // Combine: user's games first (sorted by playtime), then other supported games
        return $userGames->concat($otherSupportedGames);
    }

    /**
     * Get game name by Steam App ID
     * Maps common Steam App IDs to their display names
     *
     * @param int $gameId Steam App ID
     * @return string Game display name
     */
    private function getGameNameById(int $gameId): string
    {
        // Map of common Steam App IDs to game names
        $gameNames = [
            730 => 'Counter-Strike 2',
            570 => 'Dota 2',
            230410 => 'Warframe',
            1172470 => 'Apex Legends',
            252490 => 'Rust',
            578080 => 'PUBG: BATTLEGROUNDS',
            359550 => 'Rainbow Six Siege',
            1097150 => 'Fall Guys',
        ];

        return $gameNames[$gameId] ?? "Game #{$gameId}";
    }

    public function gamingSessions()
    {
        return $this->hasMany(GamingSession::class);
    }

    public function gameLobbies()
    {
        return $this->hasMany(GameLobby::class);
    }

    /**
     * Get only active, non-expired lobbies for the user
     */
    public function activeLobbies()
    {
        return $this->hasMany(GameLobby::class)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to eager load active lobbies with the user query
     * Prevents N+1 queries when loading multiple users with their lobbies
     */
    public function scopeWithActiveLobbies($query)
    {
        return $query->with(['activeLobbies' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);
    }

    /**
     * Get active lobby status with caching to reduce database load
     * Cache is invalidated by GameLobbyObserver on lobby changes
     *
     * @return array|null Returns formatted lobby data or null if no active lobby
     */
    public function getActiveLobbyStatus(): ?array
    {
        $cacheKey = "user.{$this->id}.active_lobby_status";

        return \Cache::remember($cacheKey, now()->addMinutes(2), function() {
            $lobby = $this->activeLobbies()
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lobby) {
                return null;
            }

            return [
                'id' => $lobby->id,
                'game_id' => $lobby->game_id,
                'game_name' => $lobby->getGameName(),
                'join_method' => $lobby->join_method,
                'join_link' => $lobby->generateJoinLink(),
                'display_format' => $lobby->getDisplayFormat(),
                'time_remaining' => $lobby->timeRemaining(),
                'time_remaining_minutes' => $lobby->timeRemaining(),
                'is_persistent' => $lobby->expires_at === null,
                'expires_at' => $lobby->expires_at?->toIso8601String(),
                'created_at' => $lobby->created_at->toIso8601String(),
            ];
        });
    }

    /**
     * Check if user has server admin/management privileges.
     *
     * This uses the permission system to check if the user has
     * the 'manage_server' permission through any of their roles,
     * not just the "Server Admin" role name.
     *
     * Returns true if:
     * - User is the server creator
     * - User has 'administrator' permission
     * - User has 'manage_server' permission through any role
     *
     * @param int $serverId
     * @return bool
     */
    public function isServerAdmin($serverId)
    {
        return $this->hasServerPermission('manage_server', $serverId);
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

    // Direct Messaging Relationships and Methods

    /**
     * Get all conversations for this user.
     * Returns a query builder for filtering/eager loading.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function conversations()
    {
        return Conversation::forUser($this->id);
    }

    /**
     * Get all direct messages sent by this user.
     */
    public function sentDirectMessages()
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    /**
     * Get all direct messages received by this user.
     * These are messages in the user's conversations where they are not the sender.
     */
    public function receivedDirectMessages()
    {
        return DirectMessage::whereIn('conversation_id', function ($query) {
            $query->select('id')
                ->from('conversations')
                ->where('user_one_id', $this->id)
                ->orWhere('user_two_id', $this->id);
        })->where('sender_id', '!=', $this->id);
    }

    /**
     * Check if this user can send a direct message to another user.
     * Users can only DM each other if they are accepted friends.
     *
     * @param User $otherUser The user to check DM permission with
     * @return bool
     */
    public function canDirectMessage(User $otherUser): bool
    {
        // Check if there's an accepted friendship in either direction
        $friendshipExists = $this->friends()
            ->where('friend_id', $otherUser->id)
            ->wherePivot('status', 'accepted')
            ->exists();

        if ($friendshipExists) {
            return true;
        }

        // Also check the reverse relationship (friend_id -> user_id)
        return $otherUser->friends()
            ->where('friend_id', $this->id)
            ->wherePivot('status', 'accepted')
            ->exists();
    }

    /**
     * Get or create a conversation with another user if allowed.
     * Returns null if the users are not friends.
     *
     * @param User $otherUser The user to get/create conversation with
     * @return Conversation|null
     */
    public function getConversationWith(User $otherUser): ?Conversation
    {
        if (!$this->canDirectMessage($otherUser)) {
            return null;
        }

        return Conversation::findOrCreateBetween($this->id, $otherUser->id);
    }

    /**
     * Get the total count of unread direct messages for this user.
     *
     * @return int
     */
    public function getUnreadDmCount(): int
    {
        return DirectMessage::whereIn('conversation_id', function ($query) {
            $query->select('id')
                ->from('conversations')
                ->where('user_one_id', $this->id)
                ->orWhere('user_two_id', $this->id);
        })
            ->where('sender_id', '!=', $this->id)
            ->whereNull('read_at')
            ->count();
    }
}