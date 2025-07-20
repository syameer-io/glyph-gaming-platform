<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Find compatible teammates for a user
     */
    public function findTeammates(User $user, array $criteria = []): Collection
    {
        $gameAppId = $criteria['game_appid'] ?? null;
        $preferredRoles = $criteria['preferred_roles'] ?? [];
        $skillRange = $criteria['skill_range'] ?? 20; // ±20 skill points
        $maxResults = $criteria['max_results'] ?? 10;

        if (!$gameAppId) {
            return collect();
        }

        // Get user's skill score from Steam data
        $userSkillScore = $this->getUserSkillScore($user, $gameAppId);

        // Find active matchmaking requests for the same game
        $compatibleRequests = MatchmakingRequest::active()
            ->byGame($gameAppId)
            ->where('user_id', '!=', $user->id)
            ->where('request_type', 'find_team')
            ->get();

        $matches = collect();

        foreach ($compatibleRequests as $request) {
            $compatibilityScore = $this->calculateUserCompatibility($user, $request->user, $gameAppId);
            
            if ($compatibilityScore >= 60) { // Minimum 60% compatibility
                $matches->push([
                    'user' => $request->user,
                    'request' => $request,
                    'compatibility_score' => $compatibilityScore,
                    'skill_difference' => abs($userSkillScore - ($request->skill_score ?? 50)),
                    'role_compatibility' => $this->calculateRoleCompatibility($preferredRoles, $request->preferred_roles ?? []),
                ]);
            }
        }

        return $matches->sortByDesc('compatibility_score')->take($maxResults)->values();
    }

    /**
     * Find compatible teams for a user to join
     */
    public function findTeams(User $user, array $criteria = []): Collection
    {
        $gameAppId = $criteria['game_appid'] ?? null;
        $serverId = $criteria['server_id'] ?? null;
        $preferredRoles = $criteria['preferred_roles'] ?? [];
        $maxResults = $criteria['max_results'] ?? 10;

        if (!$gameAppId) {
            return collect();
        }

        $query = Team::recruiting()
            ->byGame($gameAppId)
            ->with(['server', 'creator', 'activeMembers.user']);

        if ($serverId) {
            $query->inServer($serverId);
        }

        $teams = $query->get();
        $userSkillScore = $this->getUserSkillScore($user, $gameAppId);
        $matches = collect();

        foreach ($teams as $team) {
            // Check if user is already a member
            if ($team->users()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $compatibility = $this->calculateTeamCompatibility($user, $team, $gameAppId);
            
            if ($compatibility >= 50) { // Minimum 50% compatibility
                $matches->push([
                    'team' => $team,
                    'compatibility_score' => $compatibility,
                    'skill_match' => $this->calculateSkillMatch($userSkillScore, $team->average_skill_score),
                    'role_needs' => $team->getNeededRoles(),
                    'balance_score' => $team->calculateBalanceScore(),
                ]);
            }
        }

        return $matches->sortByDesc('compatibility_score')->take($maxResults)->values();
    }

    /**
     * Create a balanced team from matchmaking requests
     */
    public function createBalancedTeam(array $userIds, string $gameAppId, Server $server, array $teamData = []): ?Team
    {
        if (count($userIds) < 2 || count($userIds) > 10) {
            return null; // Invalid team size
        }

        $users = User::whereIn('id', $userIds)->get();
        
        // Validate all users are available
        foreach ($users as $user) {
            if ($this->isUserInActiveTeam($user, $gameAppId)) {
                return null; // User already in a team for this game
            }
        }

        DB::beginTransaction();
        
        try {
            // Create the team
            $team = Team::create([
                'name' => $teamData['name'] ?? 'Auto-Matched Team',
                'description' => $teamData['description'] ?? 'Team created through intelligent matchmaking',
                'game_appid' => $gameAppId,
                'game_name' => $this->getGameName($gameAppId),
                'server_id' => $server->id,
                'creator_id' => $users->first()->id,
                'max_size' => $teamData['max_size'] ?? 5,
                'current_size' => 0,
                'skill_level' => $this->determineTeamSkillLevel($users, $gameAppId),
                'status' => 'recruiting',
            ]);

            // Add members with optimal role assignments
            $roleAssignments = $this->assignOptimalRoles($users, $gameAppId);
            
            foreach ($users as $user) {
                $userSkillScore = $this->getUserSkillScore($user, $gameAppId);
                $assignedRole = $roleAssignments[$user->id] ?? null;
                
                $team->addMember($user, [
                    'role' => $user->id === $users->first()->id ? 'leader' : 'member',
                    'game_role' => $assignedRole,
                    'skill_level' => $this->getSkillLevel($userSkillScore),
                    'individual_skill_score' => $userSkillScore,
                ]);
            }

            // Mark related matchmaking requests as matched
            MatchmakingRequest::whereIn('user_id', $userIds)
                ->byGame($gameAppId)
                ->active()
                ->update(['status' => 'matched']);

            DB::commit();
            return $team;
            
        } catch (\Exception $e) {
            DB::rollback();
            return null;
        }
    }

    /**
     * Auto-match players based on active requests
     */
    public function autoMatch(string $gameAppId, int $maxTeams = 5): Collection
    {
        $activeRequests = MatchmakingRequest::active()
            ->byGame($gameAppId)
            ->byType('find_teammates')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($activeRequests->count() < 2) {
            return collect();
        }

        $teamsFormed = collect();
        $usedUserIds = [];

        foreach ($activeRequests as $request) {
            if (in_array($request->user_id, $usedUserIds) || $teamsFormed->count() >= $maxTeams) {
                continue;
            }

            $compatibleUsers = $this->findCompatibleUsersForRequest($request, $usedUserIds);
            
            if ($compatibleUsers->count() >= 1) { // At least 2 total users (requester + 1 match)
                $teamUserIds = $compatibleUsers->take(4)->pluck('user.id')->push($request->user_id)->toArray();
                
                // Find a suitable server for the team
                $server = $this->findSuitableServer($request->user, $gameAppId);
                
                if ($server) {
                    $team = $this->createBalancedTeam($teamUserIds, $gameAppId, $server);
                    
                    if ($team) {
                        $teamsFormed->push([
                            'team' => $team,
                            'users_matched' => count($teamUserIds),
                            'average_compatibility' => $compatibleUsers->avg('compatibility_score'),
                        ]);
                        
                        $usedUserIds = array_merge($usedUserIds, $teamUserIds);
                    }
                }
            }
        }

        return $teamsFormed;
    }

    /**
     * Calculate compatibility between two users
     */
    protected function calculateUserCompatibility(User $user1, User $user2, string $gameAppId): float
    {
        $score = 0;
        $factors = 0;

        // Skill compatibility (40% weight)
        $skill1 = $this->getUserSkillScore($user1, $gameAppId);
        $skill2 = $this->getUserSkillScore($user2, $gameAppId);
        $skillDiff = abs($skill1 - $skill2);
        $skillCompatibility = max(0, 100 - ($skillDiff * 2)); // Max difference of 50 points
        $score += $skillCompatibility * 0.4;
        $factors += 0.4;

        // Gaming schedule compatibility (30% weight)
        $scheduleCompatibility = $this->calculateScheduleCompatibility($user1, $user2);
        $score += $scheduleCompatibility * 0.3;
        $factors += 0.3;

        // Server preference compatibility (20% weight)
        $serverCompatibility = $this->calculateServerPreferenceCompatibility($user1, $user2);
        $score += $serverCompatibility * 0.2;
        $factors += 0.2;

        // Gaming style compatibility (10% weight)
        $styleCompatibility = $this->calculateGamingStyleCompatibility($user1, $user2, $gameAppId);
        $score += $styleCompatibility * 0.1;
        $factors += 0.1;

        return $factors > 0 ? $score / $factors : 0;
    }

    /**
     * Calculate compatibility between user and team
     */
    protected function calculateTeamCompatibility(User $user, Team $team, string $gameAppId): float
    {
        $score = 0;
        $factors = 0;

        // Skill compatibility with team average (50% weight)
        $userSkill = $this->getUserSkillScore($user, $gameAppId);
        $teamAverage = $team->average_skill_score ?? 50;
        $skillDiff = abs($userSkill - $teamAverage);
        $skillCompatibility = max(0, 100 - ($skillDiff * 2));
        $score += $skillCompatibility * 0.5;
        $factors += 0.5;

        // Role need compatibility (30% weight)
        $neededRoles = $team->getNeededRoles();
        $userRoles = $this->getUserPreferredRoles($user, $gameAppId);
        $roleMatch = !empty(array_intersect(array_keys($neededRoles), $userRoles)) ? 100 : 50;
        $score += $roleMatch * 0.3;
        $factors += 0.3;

        // Team balance improvement (20% weight)
        $currentBalance = $team->calculateBalanceScore();
        $projectedBalance = $this->calculateProjectedBalance($team, $user, $gameAppId);
        $balanceImprovement = $projectedBalance >= $currentBalance ? 100 : 70;
        $score += $balanceImprovement * 0.2;
        $factors += 0.2;

        return $factors > 0 ? $score / $factors : 0;
    }

    /**
     * Get user's skill score for a specific game
     */
    protected function getUserSkillScore(User $user, string $gameAppId): float
    {
        // Try to get from Steam skill metrics first
        $steamData = $user->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];
        
        if (isset($skillMetrics[$gameAppId])) {
            return $skillMetrics[$gameAppId]['skill_score'] ?? 50;
        }

        // Fallback to gaming preferences
        $preference = $user->gamingPreferences()
            ->where('game_appid', $gameAppId)
            ->first();

        if ($preference) {
            // Convert playtime to skill estimate (rough approximation)
            $playtimeHours = $preference->playtime_forever / 60;
            return min(100, max(20, $playtimeHours / 10 + 30));
        }

        return 50; // Default neutral score
    }

    /**
     * Additional helper methods for matchmaking algorithms
     */
    protected function calculateScheduleCompatibility(User $user1, User $user2): float
    {
        // Implementation for schedule compatibility
        return 75; // Placeholder
    }

    protected function calculateServerPreferenceCompatibility(User $user1, User $user2): float
    {
        // Implementation for server preference compatibility
        return 80; // Placeholder
    }

    protected function calculateGamingStyleCompatibility(User $user1, User $user2, string $gameAppId): float
    {
        // Implementation for gaming style compatibility
        return 70; // Placeholder
    }

    protected function calculateRoleCompatibility(array $roles1, array $roles2): float
    {
        if (empty($roles1) || empty($roles2)) {
            return 50;
        }

        $overlap = array_intersect($roles1, $roles2);
        $overlapRatio = count($overlap) / max(count($roles1), count($roles2));
        
        // Lower overlap is better for team formation
        return (1 - $overlapRatio) * 100;
    }

    protected function isUserInActiveTeam(User $user, string $gameAppId): bool
    {
        return $user->teams()
            ->where('game_appid', $gameAppId)
            ->whereIn('teams.status', ['recruiting', 'full', 'active'])
            ->exists();
    }

    protected function getGameName(string $gameAppId): string
    {
        $gameNames = [
            '730' => 'Counter-Strike 2',
            '570' => 'Dota 2',
            '230410' => 'Warframe',
            '1172470' => 'Apex Legends',
            '252490' => 'Rust',
            '578080' => 'PUBG',
            '359550' => 'Rainbow Six Siege',
            '433850' => 'Fall Guys',
        ];

        return $gameNames[$gameAppId] ?? 'Unknown Game';
    }

    protected function determineTeamSkillLevel(Collection $users, string $gameAppId): string
    {
        $avgSkill = $users->map(fn($user) => $this->getUserSkillScore($user, $gameAppId))->avg();
        
        return match(true) {
            $avgSkill >= 80 => 'expert',
            $avgSkill >= 60 => 'advanced',
            $avgSkill >= 40 => 'intermediate',
            default => 'beginner'
        };
    }

    protected function getSkillLevel(float $skillScore): string
    {
        return match(true) {
            $skillScore >= 80 => 'expert',
            $skillScore >= 60 => 'advanced',
            $skillScore >= 40 => 'intermediate',
            default => 'beginner'
        };
    }

    protected function assignOptimalRoles(Collection $users, string $gameAppId): array
    {
        // Implementation for optimal role assignment
        $assignments = [];
        foreach ($users as $user) {
            $assignments[$user->id] = $this->getUserPreferredRoles($user, $gameAppId)[0] ?? 'flex';
        }
        return $assignments;
    }

    protected function getUserPreferredRoles(User $user, string $gameAppId): array
    {
        // Implementation to get user's preferred roles
        return ['dps', 'support']; // Placeholder
    }

    protected function calculateProjectedBalance(Team $team, User $user, string $gameAppId): float
    {
        // Implementation for projected team balance calculation
        return $team->calculateBalanceScore() + 5; // Slight improvement assumption
    }

    protected function findCompatibleUsersForRequest(MatchmakingRequest $request, array $excludeUserIds): Collection
    {
        // Implementation for finding compatible users
        return collect(); // Placeholder
    }

    protected function findSuitableServer(User $user, string $gameAppId): ?Server
    {
        // Find a server the user is a member of that supports the game
        return $user->servers()->first();
    }

    protected function calculateSkillMatch(float $userSkill, ?float $teamAverage): float
    {
        if (!$teamAverage) return 50;
        
        $diff = abs($userSkill - $teamAverage);
        return max(0, 100 - ($diff * 2));
    }
}