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
            if ($team->members()->where('user_id', $user->id)->exists()) {
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
     * Find compatible teams for a matchmaking request
     *
     * @param MatchmakingRequest $request The matchmaking request
     * @return Collection Collection of Team models matching the criteria
     */
    public function findCompatibleTeams(MatchmakingRequest $request): Collection
    {
        \Log::info('MatchmakingService::findCompatibleTeams START', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
            'game_appid' => $request->game_appid,
            'request_type' => $request->request_type,
            'skill_level' => $request->skill_level,
            'skill_score' => $request->skill_score,
            'server_preferences' => $request->server_preferences,
        ]);

        // Start with base query for recruiting teams in the same game
        // Phase 3: Added playerGameRoles eager loading to prevent N+1 in getUserFlexibleRoles()
        $query = Team::recruiting()
            ->byGame($request->game_appid)
            ->with(['server', 'creator', 'activeMembers.user.profile', 'activeMembers.user.playerGameRoles']);

        \Log::info('Built base query for recruiting teams', [
            'game_appid' => $request->game_appid,
        ]);

        // Filter by server preferences if provided
        if (!empty($request->server_preferences) && is_array($request->server_preferences)) {
            $query->whereIn('server_id', $request->server_preferences);
            \Log::info('Filtered by server preferences', [
                'server_ids' => $request->server_preferences,
            ]);
        }

        $teams = $query->get();

        \Log::info('Teams retrieved from database', [
            'total_teams' => $teams->count(),
            'team_ids' => $teams->pluck('id')->toArray(),
        ]);

        // Calculate compatibility for each team and filter
        $compatibleTeams = collect();
        $filteredReasons = [];

        foreach ($teams as $team) {
            \Log::info('Evaluating team', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'status' => $team->status,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'server_id' => $team->server_id,
            ]);

            // Check if user is already a member
            $isMember = $team->members()->where('user_id', $request->user_id)->exists();

            if ($isMember) {
                \Log::info('Team filtered: User already a member', [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                ]);
                $filteredReasons[$team->id] = 'User already a member';
                continue;
            }

            try {
                $compatibility = $this->calculateDetailedCompatibility($team, $request);

                \Log::info('Compatibility calculated', [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'compatibility_score' => $compatibility['total_score'],
                    'breakdown' => $compatibility['breakdown'],
                    'reasons' => $compatibility['reasons'],
                ]);

                // Only include teams with at least 50% compatibility
                if ($compatibility['total_score'] >= 50) {
                    // Store compatibility score as a property for sorting
                    $team->compatibility_score = $compatibility['total_score'];
                    $compatibleTeams->push($team);
                    \Log::info('Team added to compatible list', [
                        'team_id' => $team->id,
                        'compatibility_score' => $compatibility['total_score'],
                    ]);
                } else {
                    \Log::info('Team filtered: Compatibility too low', [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'compatibility_score' => $compatibility['total_score'],
                        'minimum_required' => 50,
                    ]);
                    $filteredReasons[$team->id] = "Low compatibility: {$compatibility['total_score']}%";
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating team compatibility', [
                    'team_id' => $team->id,
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $filteredReasons[$team->id] = 'Error: ' . $e->getMessage();
                continue;
            }
        }

        // Sort by compatibility score (descending) and take top 10
        $sortedTeams = $compatibleTeams->sortByDesc('compatibility_score')->take(10)->values();

        \Log::info('MatchmakingService::findCompatibleTeams COMPLETE', [
            'request_id' => $request->id,
            'total_teams_evaluated' => $teams->count(),
            'compatible_teams_found' => $compatibleTeams->count(),
            'teams_after_limit' => $sortedTeams->count(),
            'filtered_reasons' => $filteredReasons,
            'final_team_ids' => $sortedTeams->pluck('id')->toArray(),
        ]);

        return $sortedTeams;
    }

    /**
     * Calculate detailed compatibility between a team and matchmaking request
     *
     * Uses multi-criteria weighted scoring with proper normalization.
     * All criterion scores are in [0, 1] range before applying weights.
     *
     * Formula: Match Score = Σ(weight_i × normalized_score_i)
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return array Contains 'total_score', 'reasons', and 'breakdown'
     */
    public function calculateDetailedCompatibility(Team $team, MatchmakingRequest $request): array
    {
        // Get and validate weights
        $weights = $this->getMatchmakingWeights();
        $this->validateMatchmakingWeights($weights);

        $normalizedScores = [];
        $reasons = [];

        // 1. Skill Compatibility (returns [0, 100], need to normalize to [0, 1])
        $skillScorePercentage = $this->calculateSkillCompatibilityForTeam($team, $request);
        $normalizedScores['skill'] = $skillScorePercentage / 100; // Convert to [0, 1]

        if ($skillScorePercentage >= 90) {
            $reasons[] = "Perfect skill level match ({$request->skill_level})";
        } elseif ($skillScorePercentage >= 70) {
            $reasons[] = "Good skill level compatibility";
        }

        // 2. Role/Composition Match (already returns [0, 1])
        $normalizedScores['composition'] = $this->calculateRoleMatchForTeam($team, $request);

        // Phase 3 Enhanced Reason Messages
        if ($normalizedScores['composition'] >= 0.95) {
            $neededRoles = $team->getNeededRoles();
            if (!empty($neededRoles)) {
                $userRoles = $this->getUserFlexibleRoles($request->user, $request->game_appid, $request);
                $matchingRoles = array_intersect(array_keys($neededRoles), $userRoles);
                if (!empty($matchingRoles)) {
                    $roleNames = implode(', ', array_map('ucfirst', $matchingRoles));
                    $reasons[] = "Perfect role fit: Can fill {$roleNames}";
                }
            }
        } elseif ($normalizedScores['composition'] >= 0.70) {
            // Check if user has no role preferences (flexible player case)
            $userRoles = $this->getUserFlexibleRoles($request->user, $request->game_appid, $request);
            if (empty($request->preferred_roles ?? [])) {
                $reasons[] = "Flexible player - can adapt to team needs";
            } else {
                $reasons[] = "Good role compatibility";
            }
        } elseif ($normalizedScores['composition'] >= 0.50) {
            $reasons[] = "Flexible player - can adapt to team needs";
        }

        // 3. Region Compatibility (already returns [0, 1])
        $normalizedScores['region'] = $this->calculateRegionCompatibilityForTeam($team, $request);

        if ($normalizedScores['region'] >= 0.80) {
            $teamRegion = $team->team_data['preferred_region'] ?? null;
            if ($teamRegion) {
                $reasons[] = "Same region preference: {$teamRegion}";
            }
        }

        // 4. Schedule/Activity Time Match (already returns [0, 1])
        $normalizedScores['schedule'] = $this->calculateActivityTimeMatch($team, $request);

        if ($normalizedScores['schedule'] >= 0.80) {
            $reasons[] = "Active during your preferred hours";
        }

        // 5. Team Size Score (already returns [0, 1])
        $normalizedScores['size'] = $this->calculateTeamSizeScore($team);

        if ($team->current_size <= 3) {
            $reasons[] = "Small team - easier to integrate";
        } elseif ($team->current_size >= $team->max_size - 1) {
            $reasons[] = "Team almost full - join quickly!";
        }

        // 6. Language Compatibility (already returns [0, 1])
        $normalizedScores['language'] = $this->calculateLanguageCompatibility($team, $request);

        if ($normalizedScores['language'] >= 0.80) {
            $reasons[] = "Shares common language";
        }

        // Calculate weighted sum
        $totalScore = 0.0;
        foreach ($weights as $criterion => $weight) {
            $score = $normalizedScores[$criterion] ?? 0.0;
            $totalScore += $weight * $score;
        }

        // Convert to percentage and create breakdown for display
        $breakdown = [];
        foreach ($normalizedScores as $criterion => $score) {
            $breakdown[$criterion] = round($score * 100, 1); // Convert to percentage for display
        }

        // Add team balance information
        $balanceScore = $team->calculateBalanceScore();
        if ($balanceScore >= 70) {
            $reasons[] = "Well-balanced team composition";
        }

        \Log::debug('Detailed compatibility calculated', [
            'team_id' => $team->id,
            'request_id' => $request->id,
            'normalized_scores' => $normalizedScores,
            'weights' => $weights,
            'total_score' => round($totalScore * 100, 1),
            'breakdown' => $breakdown,
        ]);

        return [
            'total_score' => round($totalScore * 100, 1), // Convert to percentage
            'reasons' => $reasons,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get matchmaking weights for multi-criteria scoring
     *
     * Weights must sum to 1.0 (100%). Based on research from Awesomenauts algorithm,
     * TrueSkill 2, and industry best practices.
     *
     * Weight Distribution Rationale:
     * - Skill (40%): Primary factor for match quality and enjoyment
     * - Composition (25%): Role needs must be met for team success
     * - Region (15%): Affects latency and communication
     * - Schedule (10%): Nice-to-have but not critical (async possible)
     * - Size (5%): Minor factor, teams accept applications at various fill levels
     * - Language (5%): Often correlates with region, English is common
     *
     * @return array Associative array of criterion => weight
     */
    protected function getMatchmakingWeights(): array
    {
        // TODO: Phase 6 - Load from config/database
        return [
            'skill' => 0.40,
            'composition' => 0.25,
            'region' => 0.15,
            'schedule' => 0.10,
            'size' => 0.05,
            'language' => 0.05,
        ];
    }

    /**
     * Validate that matchmaking weights sum to 1.0
     *
     * @param array $weights Associative array of weights
     * @throws \RuntimeException If weights don't sum to 1.0
     * @return void
     */
    protected function validateMatchmakingWeights(array $weights): void
    {
        $sum = array_sum($weights);

        if (abs($sum - 1.0) > 0.001) { // Allow small floating-point error
            throw new \RuntimeException(
                "Matchmaking weights must sum to 1.0, got {$sum}. Weights: " . json_encode($weights)
            );
        }
    }

    /**
     * Calculate skill compatibility between team and request using categorical skill levels
     *
     * This method implements a non-linear penalty system for skill level differences:
     * - Converts categorical skill levels (beginner/intermediate/advanced/expert) to numeric values (1-4)
     * - Uses Manhattan distance (absolute difference) to measure skill gap
     * - Applies 50% penalty multiplier for gaps of 2+ levels
     * - Returns normalized score in [0, 100] range
     *
     * Expected compatibility matrix:
     * - Same level: 100%
     * - 1 level gap: ~67%
     * - 2 level gap: ~17% (with penalty applied)
     * - 3 level gap: 0%
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Compatibility score [0, 100]
     */
    protected function calculateSkillCompatibilityForTeam(Team $team, MatchmakingRequest $request): float
    {
        // Get categorical skill levels
        $teamSkillLevel = $team->skill_level ?? 'intermediate';
        $requestSkillLevel = $request->skill_level ?? 'intermediate';

        // Convert to numeric values for calculation
        $teamSkillNumeric = $this->convertSkillLevelToNumeric($teamSkillLevel);
        $requestSkillNumeric = $this->convertSkillLevelToNumeric($requestSkillLevel);

        // Calculate Manhattan distance (absolute difference)
        $actualDifference = abs($teamSkillNumeric - $requestSkillNumeric);

        // Normalize to [0, 1] score with non-linear penalty for large gaps
        $normalizedScore = $this->normalizeSkillScore($actualDifference);

        // Convert to percentage [0, 100]
        $finalPercentage = $normalizedScore * 100;

        // Debug logging for tracking algorithm behavior
        \Log::debug('MatchmakingService::calculateSkillCompatibilityForTeam', [
            'team_id' => $team->id,
            'team_skill_level' => $teamSkillLevel,
            'team_skill_numeric' => $teamSkillNumeric,
            'request_skill_level' => $requestSkillLevel,
            'request_skill_numeric' => $requestSkillNumeric,
            'actual_difference' => $actualDifference,
            'normalized_score' => $normalizedScore,
            'final_percentage' => $finalPercentage,
        ]);

        return $finalPercentage;
    }

    /**
     * Calculate role match between team needs and request preferences
     *
     * Uses Jaccard similarity for role overlap when both team and user specify roles.
     * Accounts for flexible players and partial role matches.
     * Returns normalized score [0, 1].
     *
     * Scoring Logic (Phase 3 - Jaccard-based):
     * - Perfect match (user fills ALL needed roles): 1.0
     * - Partial fill (user fills some roles): 0.70 + (fillRatio * 0.25) → [0.70, 0.95]
     * - Flexible player (3+ roles): 0.60
     * - Jaccard similarity (partial overlap): 0.40 + (jaccard * 0.30) → [0.40, 0.70]
     * - Team has no needs: 0.80
     * - User has no preferences: 0.70
     * - No overlap at all: 0.30
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized role match score [0.0, 1.0]
     */
    protected function calculateRoleMatchForTeam(Team $team, MatchmakingRequest $request): float
    {
        $neededRoles = $team->getNeededRoles(); // Returns array of role => count
        $requestRoles = $request->preferred_roles ?? [];

        // Case 1: Team has no specific role needs
        if (empty($neededRoles)) {
            // Team is flexible - any player is good
            \Log::debug('Role matching: Team has no role needs', [
                'team_id' => $team->id,
                'case' => 'flexible_team',
                'score' => 0.80,
            ]);
            return 0.80; // High neutral score
        }

        // Case 2: User has no role preferences (total flex player)
        if (empty($requestRoles)) {
            // Flex player can fill any role - good for teams with needs
            \Log::debug('Role matching: User has no role preferences', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'case' => 'flexible_player',
                'score' => 0.70,
            ]);
            return 0.70; // Decent neutral score (lower than team flexibility)
        }

        // Case 3: Both have specified roles - calculate overlap

        // Get roles user can play (includes flexible roles if skilled)
        $userRoles = $this->getUserFlexibleRoles($request->user, $request->game_appid, $request);

        // Extract role names from team needs (getNeededRoles returns role => count)
        $neededRoleNames = array_keys($neededRoles);

        // Check for exact matches first
        $matchingRoles = array_intersect($neededRoleNames, $userRoles);

        if (!empty($matchingRoles)) {
            // User can fill at least one needed role

            // Calculate what percentage of needed roles user can fill
            $fillRatio = count($matchingRoles) / count($neededRoleNames);

            // Perfect fill (can cover all needs)
            if ($fillRatio >= 1.0) {
                \Log::debug('Role matching: Perfect fill', [
                    'team_id' => $team->id,
                    'request_id' => $request->id,
                    'needed_roles' => $neededRoleNames,
                    'user_roles' => $userRoles,
                    'matching_roles' => $matchingRoles,
                    'fill_ratio' => $fillRatio,
                    'case' => 'perfect_fill',
                    'score' => 1.0,
                ]);
                return 1.0;
            }

            // Partial fill - scale between 0.7 and 0.95
            $score = 0.70 + ($fillRatio * 0.25);

            \Log::debug('Role matching: Partial fill', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'needed_roles' => $neededRoleNames,
                'user_roles' => $userRoles,
                'matching_roles' => $matchingRoles,
                'fill_ratio' => $fillRatio,
                'case' => 'partial_fill',
                'score' => $score,
            ]);

            return $score;
        }

        // Case 4: No direct match - use Jaccard similarity for partial overlap potential

        // If user has many roles (flexible), give benefit of doubt
        if (count($userRoles) >= 3) {
            \Log::debug('Role matching: Flexible player with many roles', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'needed_roles' => $neededRoleNames,
                'user_roles' => $userRoles,
                'user_role_count' => count($userRoles),
                'case' => 'multi_role_player',
                'score' => 0.60,
            ]);
            return 0.60; // Flexible player might adapt
        }

        // Calculate Jaccard similarity between all roles
        $jaccardScore = $this->calculateJaccardSimilarity($neededRoleNames, $userRoles);

        if ($jaccardScore > 0) {
            // Some overlap in role space (similar playstyle)
            $score = 0.40 + ($jaccardScore * 0.30); // Scale from 0.4 to 0.7

            \Log::debug('Role matching: Jaccard similarity applied', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'needed_roles' => $neededRoleNames,
                'user_roles' => $userRoles,
                'jaccard_score' => $jaccardScore,
                'case' => 'jaccard_overlap',
                'score' => $score,
            ]);

            return $score;
        }

        // Case 5: No overlap at all
        \Log::debug('Role matching: No overlap', [
            'team_id' => $team->id,
            'request_id' => $request->id,
            'needed_roles' => $neededRoleNames,
            'user_roles' => $userRoles,
            'case' => 'no_overlap',
            'score' => 0.30,
        ]);

        return 0.30; // Poor match - user can't fill needed roles
    }

    /**
     * Calculate region/server compatibility
     *
     * Returns normalized score [0, 1] based on server preference match
     * or region proximity.
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized region compatibility score [0.0, 1.0]
     */
    protected function calculateRegionCompatibilityForTeam(Team $team, MatchmakingRequest $request): float
    {
        $teamRegion = $team->team_data['preferred_region'] ?? null;
        $requestPrefs = $request->server_preferences ?? [];
        $requestReqs = $request->additional_requirements ?? [];

        // Check if request specifies server preferences
        if (!empty($requestPrefs) && is_array($requestPrefs)) {
            // Check if team's server is in the preferred list
            if (in_array($team->server_id, $requestPrefs)) {
                return 1.0; // Perfect server match
            } else {
                return 0.40; // Different server
            }
        }

        // Check for region compatibility in additional requirements
        $requestRegion = $requestReqs['preferred_region'] ?? null;

        if ($teamRegion && $requestRegion) {
            if (strtolower($teamRegion) === strtolower($requestRegion)) {
                return 1.0; // Same region
            } else {
                return 0.30; // Different region
            }
        }

        // No specific preferences - neutral score
        return 0.70;
    }

    /**
     * Calculate team size score (prefer teams 30-70% full)
     *
     * Returns normalized score [0, 1] with optimal range at 30-70% capacity.
     *
     * @param Team $team The team to evaluate
     * @return float Normalized team size score [0.0, 1.0]
     */
    protected function calculateTeamSizeScore(Team $team): float
    {
        $fillPercentage = ($team->current_size / $team->max_size) * 100;

        if ($fillPercentage >= 30 && $fillPercentage <= 70) {
            return 1.0; // Optimal range
        } elseif ($fillPercentage < 30) {
            // Too empty - less established
            return 0.50 + ($fillPercentage * 0.0167); // Scale from 0.50-1.0
        } else {
            // Too full - less room for integration
            return 1.0 - (($fillPercentage - 70) * 0.02); // Scale from 1.0 down
        }
    }

    /**
     * Calculate activity time match between team and request
     *
     * Uses time range overlap to determine schedule compatibility.
     * Returns normalized score [0, 1].
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized activity time score [0.0, 1.0]
     */
    protected function calculateActivityTimeMatch(Team $team, MatchmakingRequest $request): float
    {
        $teamActivityTime = $team->team_data['activity_time'] ?? null;
        $requestAvailability = $request->availability_hours ?? [];

        if (!$teamActivityTime || empty($requestAvailability)) {
            return 0.70; // Neutral score if no data
        }

        // If activity times match exactly
        if (is_array($requestAvailability) && in_array($teamActivityTime, $requestAvailability)) {
            return 1.0;
        }

        // Partial match logic based on time ranges
        $timeRangeMap = [
            'morning' => ['morning', 'afternoon'],
            'afternoon' => ['morning', 'afternoon', 'evening'],
            'evening' => ['afternoon', 'evening', 'night'],
            'night' => ['evening', 'night'],
            'flexible' => ['morning', 'afternoon', 'evening', 'night'],
        ];

        $compatibleTimes = $timeRangeMap[strtolower($teamActivityTime)] ?? [$teamActivityTime];

        foreach ($requestAvailability as $availTime) {
            if (in_array(strtolower($availTime), $compatibleTimes)) {
                return 0.80; // Good match
            }
        }

        return 0.40; // Poor match
    }

    /**
     * Calculate language compatibility between team and request
     *
     * Uses set intersection to check for any common languages.
     * Returns normalized score [0, 1].
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized language compatibility score [0.0, 1.0]
     */
    protected function calculateLanguageCompatibility(Team $team, MatchmakingRequest $request): float
    {
        // Get team languages from team_data or default to English
        $teamLanguages = $team->team_data['languages'] ?? ['en'];

        // Get request languages from additional_requirements or default to English
        $requestReqs = $request->additional_requirements ?? [];
        $requestLanguages = $requestReqs['languages'] ?? ['en'];

        // Check for any overlap
        $overlap = array_intersect($teamLanguages, $requestLanguages);

        if (!empty($overlap)) {
            return 1.0; // Common language found
        }

        return 0.30; // No common language
    }

    /**
     * Calculate schedule compatibility between users based on gaming preferences
     */
    protected function calculateScheduleCompatibility(User $user1, User $user2): float
    {
        // Get recent gaming sessions to determine play patterns
        $user1Sessions = $user1->gamingSessions()
            ->where('session_end', '>', now()->subDays(30))
            ->get();

        $user2Sessions = $user2->gamingSessions()
            ->where('session_end', '>', now()->subDays(30))
            ->get();

        if ($user1Sessions->isEmpty() || $user2Sessions->isEmpty()) {
            return 70; // Neutral score if no session data
        }

        // Extract hour distributions (0-23)
        $user1Hours = $user1Sessions->map(function($session) {
            return $session->session_start->hour;
        })->countBy()->toArray();

        $user2Hours = $user2Sessions->map(function($session) {
            return $session->session_start->hour;
        })->countBy()->toArray();

        // Calculate overlap in active hours
        $commonHours = array_intersect_key($user1Hours, $user2Hours);

        if (empty($commonHours)) {
            return 40; // No overlapping hours
        }

        // Calculate overlap percentage
        $totalHours = array_unique(array_merge(array_keys($user1Hours), array_keys($user2Hours)));
        $overlapRatio = count($commonHours) / count($totalHours);

        return min(100, $overlapRatio * 120); // Scale up to 100%
    }

    /**
     * Calculate server preference compatibility between users
     */
    protected function calculateServerPreferenceCompatibility(User $user1, User $user2): float
    {
        $user1Servers = $user1->servers()->pluck('servers.id')->toArray();
        $user2Servers = $user2->servers()->pluck('servers.id')->toArray();

        if (empty($user1Servers) || empty($user2Servers)) {
            return 60; // Neutral score if no server memberships
        }

        // Calculate common servers
        $commonServers = array_intersect($user1Servers, $user2Servers);

        if (!empty($commonServers)) {
            // Already in same communities - strong compatibility
            return 100;
        }

        // No common servers but both are active in communities
        return 50;
    }

    /**
     * Calculate gaming style compatibility based on playtime patterns
     */
    protected function calculateGamingStyleCompatibility(User $user1, User $user2, string $gameAppId): float
    {
        $user1Pref = $user1->gamingPreferences()
            ->where('game_appid', $gameAppId)
            ->first();

        $user2Pref = $user2->gamingPreferences()
            ->where('game_appid', $gameAppId)
            ->first();

        if (!$user1Pref || !$user2Pref) {
            return 60; // Neutral if no preference data
        }

        // Compare play intensity (hours played)
        $user1Playtime = $user1Pref->playtime_forever ?? 0;
        $user2Playtime = $user2Pref->playtime_forever ?? 0;

        // Categorize play styles
        $getPlayStyle = function($playtime) {
            if ($playtime > 500) return 'hardcore';
            if ($playtime > 200) return 'dedicated';
            if ($playtime > 50) return 'casual';
            return 'beginner';
        };

        $style1 = $getPlayStyle($user1Playtime);
        $style2 = $getPlayStyle($user2Playtime);

        if ($style1 === $style2) {
            return 100; // Perfect match
        }

        // Adjacent styles are compatible
        $compatibilityMatrix = [
            'beginner' => ['beginner' => 100, 'casual' => 80, 'dedicated' => 50, 'hardcore' => 30],
            'casual' => ['beginner' => 80, 'casual' => 100, 'dedicated' => 80, 'hardcore' => 50],
            'dedicated' => ['beginner' => 50, 'casual' => 80, 'dedicated' => 100, 'hardcore' => 80],
            'hardcore' => ['beginner' => 30, 'casual' => 50, 'dedicated' => 80, 'hardcore' => 100],
        ];

        return $compatibilityMatrix[$style1][$style2] ?? 50;
    }

    /**
     * Calculate role compatibility between two users for team formation
     */
    protected function calculateRoleCompatibility(array $roles1, array $roles2): float
    {
        if (empty($roles1) || empty($roles2)) {
            return 50;
        }

        $overlap = array_intersect($roles1, $roles2);
        $overlapRatio = count($overlap) / max(count($roles1), count($roles2));

        // Lower overlap is better for team formation (complementary roles)
        return (1 - $overlapRatio) * 100;
    }

    /**
     * Get user's preferred roles for a specific game
     */
    protected function getUserPreferredRoles(User $user, string $gameAppId): array
    {
        // Use the User model's existing method
        return $user->getPreferredRoles($gameAppId);
    }

    /**
     * Calculate projected team balance after adding a user
     */
    protected function calculateProjectedBalance(Team $team, User $user, string $gameAppId): float
    {
        $currentMembers = $team->activeMembers()->get();
        $userSkillScore = $this->getUserSkillScore($user, $gameAppId);

        // Collect all skill scores including the new user
        $allSkillScores = $currentMembers
            ->whereNotNull('individual_skill_score')
            ->pluck('individual_skill_score')
            ->push($userSkillScore)
            ->toArray();

        if (count($allSkillScores) < 2) {
            return 100; // Perfect balance with 0-1 members
        }

        // Calculate standard deviation with new member
        $mean = array_sum($allSkillScores) / count($allSkillScores);
        $variance = array_sum(array_map(function($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $allSkillScores)) / count($allSkillScores);

        $standardDeviation = sqrt($variance);

        // Convert to balance score (lower std dev = better balance)
        $balanceScore = max(0, 100 - ($standardDeviation / 30 * 100));

        return round($balanceScore, 1);
    }

    /**
     * Find compatible users for a matchmaking request
     */
    protected function findCompatibleUsersForRequest(MatchmakingRequest $request, array $excludeUserIds): Collection
    {
        // Find other active requests for the same game
        $compatibleRequests = MatchmakingRequest::active()
            ->byGame($request->game_appid)
            ->where('user_id', '!=', $request->user_id)
            ->whereNotIn('user_id', $excludeUserIds)
            ->where('request_type', 'find_teammates')
            ->with('user.profile', 'user.gamingPreferences')
            ->get();

        $matches = collect();

        foreach ($compatibleRequests as $otherRequest) {
            try {
                $compatibility = $this->calculateUserCompatibility(
                    $request->user,
                    $otherRequest->user,
                    $request->game_appid
                );

                if ($compatibility >= 60) { // Minimum 60% compatibility
                    $matches->push([
                        'user' => $otherRequest->user,
                        'request' => $otherRequest,
                        'compatibility_score' => $compatibility,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating user compatibility', [
                    'user1_id' => $request->user_id,
                    'user2_id' => $otherRequest->user_id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $matches->sortByDesc('compatibility_score');
    }

    /**
     * Additional helper methods for matchmaking algorithms
     */
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

    /**
     * Calculate Jaccard similarity between two sets
     *
     * Jaccard Index = |A ∩ B| / |A ∪ B|
     * Used for role overlap, language overlap, and schedule compatibility.
     *
     * This is a standard set similarity metric from research literature
     * (Awesomenauts matchmaking paper, TrueSkill 2). It provides gradual
     * scoring for partial overlaps, unlike binary matching.
     *
     * @param array $set1 First set
     * @param array $set2 Second set
     * @return float Jaccard similarity [0.0, 1.0]
     */
    protected function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        // Handle empty sets
        if (empty($set1) && empty($set2)) {
            return 1.0; // Both empty = perfect match
        }

        if (empty($set1) || empty($set2)) {
            return 0.0; // One empty = no overlap
        }

        // Calculate intersection and union
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        $intersectionCount = count($intersection);
        $unionCount = count($union);

        if ($unionCount === 0) {
            return 0.0;
        }

        $jaccardScore = $intersectionCount / $unionCount;

        \Log::debug('Jaccard similarity calculated', [
            'set1' => $set1,
            'set2' => $set2,
            'intersection' => $intersection,
            'union' => $union,
            'intersection_count' => $intersectionCount,
            'union_count' => $unionCount,
            'jaccard_score' => $jaccardScore,
        ]);

        return $jaccardScore;
    }

    /**
     * Get roles user can flexibly play based on preferences and skill level
     *
     * Higher skill players tend to be more flexible. This method returns
     * both preferred roles and roles the user is willing to fill.
     *
     * Expert players (skill >= 70) with limited roles get expanded to
     * common game roles, as they typically have the skill to flex.
     *
     * @param User $user The user
     * @param string $gameAppId Game app ID
     * @param MatchmakingRequest|null $request Optional request with flexible_roles
     * @return array Array of role names user can play
     */
    protected function getUserFlexibleRoles(User $user, string $gameAppId, ?MatchmakingRequest $request = null): array
    {
        // Start with preferred roles - check request first (most specific), then user's gaming preferences
        $preferredRoles = [];

        // Priority 1: Use request's preferred_roles if available (this is what user wants NOW)
        if ($request && !empty($request->preferred_roles)) {
            $preferredRoles = is_array($request->preferred_roles) ? $request->preferred_roles : [];
        } else {
            // Priority 2: Fallback to user's stored gaming preferences
            $preferredRoles = $this->getUserPreferredRoles($user, $gameAppId);
        }

        // Add flexible roles from matchmaking request if provided and column exists
        if ($request && isset($request->flexible_roles) && !empty($request->flexible_roles)) {
            $flexibleRoles = is_array($request->flexible_roles) ? $request->flexible_roles : [];
            $preferredRoles = array_unique(array_merge($preferredRoles, $flexibleRoles));
        }

        // If user has high skill, assume more flexibility
        // Expert players can often fill multiple roles effectively
        $userSkill = $this->getUserSkillScore($user, $gameAppId);

        if ($userSkill >= 70 && count($preferredRoles) <= 1) {
            // Expert with one or no roles can likely flex to others
            // Add common flex roles based on game
            $commonRoles = $this->getCommonRolesForGame($gameAppId);
            $preferredRoles = array_unique(array_merge($preferredRoles, $commonRoles));

            \Log::debug('Expert player flexibility applied', [
                'user_id' => $user->id,
                'user_skill' => $userSkill,
                'original_roles' => $preferredRoles,
                'expanded_roles' => $preferredRoles,
            ]);
        }

        return array_values($preferredRoles); // Re-index array
    }

    /**
     * Get common roles for a game (fallback for flexible matching)
     *
     * These are standard roles that experienced players in each game
     * are typically capable of playing. Used when expanding expert
     * player flexibility.
     *
     * @param string $gameAppId Game app ID
     * @return array Common role names
     */
    protected function getCommonRolesForGame(string $gameAppId): array
    {
        // Common roles by game (can be moved to config in Phase 6)
        $gameRoles = [
            '730' => ['entry', 'support', 'awper', 'igl', 'lurker'], // CS2
            '570' => ['carry', 'mid', 'offlane', 'support', 'hard_support'], // Dota 2
            '230410' => ['dps', 'support', 'tank', 'cc'], // Warframe
            '1172470' => ['assault', 'support', 'recon', 'controller'], // Apex Legends
            '252490' => ['builder', 'raider', 'farmer', 'defender'], // Rust
            '578080' => ['fragger', 'support', 'igl', 'flex'], // PUBG
            '359550' => ['entry', 'support', 'anchor', 'flex'], // Rainbow Six Siege
        ];

        return $gameRoles[$gameAppId] ?? ['flex'];
    }

    protected function assignOptimalRoles(Collection $users, string $gameAppId): array
    {
        $assignments = [];
        $usedRoles = [];

        // First pass: assign preferred roles where possible
        foreach ($users as $user) {
            $preferredRoles = $this->getUserPreferredRoles($user, $gameAppId);

            // Find first available preferred role
            foreach ($preferredRoles as $role) {
                if (!in_array($role, $usedRoles)) {
                    $assignments[$user->id] = $role;
                    $usedRoles[] = $role;
                    break;
                }
            }

            // If no preferred role available, assign 'flex'
            if (!isset($assignments[$user->id])) {
                $assignments[$user->id] = 'flex';
            }
        }

        return $assignments;
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

    /**
     * Convert categorical skill level to numeric value for mathematical operations
     *
     * Mapping:
     * - beginner → 1
     * - intermediate → 2
     * - advanced → 3
     * - expert → 4
     * - null/invalid → 2 (default to intermediate)
     *
     * @param string|null $skillLevel Categorical skill level
     * @return int Numeric skill value [1-4]
     */
    protected function convertSkillLevelToNumeric(?string $skillLevel): int
    {
        // Handle null or empty values
        if (empty($skillLevel)) {
            return 2; // Default to intermediate
        }

        // Map categorical to numeric
        return match(strtolower($skillLevel)) {
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
            'expert' => 4,
            default => 2 // Default to intermediate for invalid values
        };
    }

    /**
     * Normalize skill difference to [0, 1] score with non-linear penalty
     *
     * Algorithm:
     * 1. Base normalization: score = 1.0 - (actualDifference / maxDifference)
     * 2. Apply 50% penalty multiplier for 2+ level gaps
     * 3. Ensure result stays within [0.0, 1.0] bounds
     *
     * Examples:
     * - 0 levels difference: 1.0 (100%)
     * - 1 level difference: 0.67 (~67%)
     * - 2 levels difference: 0.17 (~17% after penalty)
     * - 3 levels difference: 0.0 (0%)
     *
     * @param int $actualDifference Absolute skill level difference [0-3]
     * @return float Normalized score [0.0, 1.0]
     */
    protected function normalizeSkillScore(int $actualDifference): float
    {
        // Maximum possible difference between skill levels (expert - beginner = 4 - 1 = 3)
        $maxDifference = 3;

        // Base min-max normalization: maps [0, 3] to [1.0, 0.0]
        $score = 1.0 - ($actualDifference / $maxDifference);

        // Apply non-linear penalty for large skill gaps (2+ levels)
        if ($actualDifference >= 2) {
            $score *= 0.5; // 50% penalty multiplier
        }

        // Ensure bounds [0.0, 1.0]
        return max(0.0, min(1.0, $score));
    }
}