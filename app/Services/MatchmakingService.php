<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use App\Models\MatchmakingRequest;
use App\Models\Server;
use App\Models\MatchmakingConfiguration;
use App\Models\MatchmakingAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Cached configuration for current request
     */
    protected ?MatchmakingConfiguration $config = null;
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
    public function createBalancedTeam(array $userIds, string $gameAppId, ?Server $server = null, array $teamData = []): ?Team
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
            // Create the team (server_id is now optional)
            $team = Team::create([
                'name' => $teamData['name'] ?? 'Auto-Matched Team',
                'description' => $teamData['description'] ?? 'Team created through intelligent matchmaking',
                'game_appid' => $gameAppId,
                'game_name' => $this->getGameName($gameAppId),
                'server_id' => $server ? $server->id : null,
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

            // Filter out full teams early (capacity check)
            if ($team->current_size >= $team->max_size) {
                \Log::debug('Team filtered: Team is full', [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'current_size' => $team->current_size,
                    'max_size' => $team->max_size,
                ]);
                $filteredReasons[$team->id] = 'Team is full (no open slots)';
                continue;
            }

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

                // Get minimum threshold from configuration
                $minThreshold = $this->getMinimumCompatibilityThreshold($request);

                // Only include teams with compatibility above threshold
                if ($compatibility['total_score'] >= $minThreshold) {
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
                        'minimum_required' => $minThreshold,
                    ]);
                    $filteredReasons[$team->id] = "Low compatibility: {$compatibility['total_score']}% (min: {$minThreshold}%)";
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
        // Get and validate weights from configuration
        $weights = $this->getMatchmakingWeights($request);
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

        // 5. Language Compatibility (already returns [0, 1])
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

        // Store analytics for this match calculation
        try {
            MatchmakingAnalytics::create([
                'matchmaking_request_id' => $request->id,
                'team_id' => $team->id,
                'compatibility_score' => round($totalScore * 100, 1),
                'score_breakdown' => $breakdown,
                'configuration_used' => $this->getConfiguration($request)->name,
                'match_shown_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to store matchmaking analytics', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
                'team_id' => $team->id,
            ]);
        }

        return [
            'total_score' => round($totalScore * 100, 1), // Convert to percentage
            'reasons' => $reasons,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get matchmaking configuration for request
     *
     * @param MatchmakingRequest $request The request
     * @return MatchmakingConfiguration
     */
    protected function getConfiguration(MatchmakingRequest $request): MatchmakingConfiguration
    {
        if ($this->config !== null) {
            return $this->config;
        }

        // Determine scope based on request
        $scope = 'all';

        if ($request->game_appid) {
            $scope = "game:{$request->game_appid}";
        }

        $this->config = MatchmakingConfiguration::getActiveForScope($scope);

        return $this->config;
    }

    /**
     * Get matchmaking weights from configuration
     *
     * Weights must sum to 1.0 (100%). Based on research from Awesomenauts algorithm,
     * TrueSkill 2, and industry best practices.
     *
     * Weight Distribution Rationale (default):
     * - Skill (40%): Primary factor for match quality and enjoyment
     * - Composition (30%): Role needs must be met for team success (increased from 25%)
     * - Region (15%): Affects latency and communication
     * - Schedule (10%): Nice-to-have but not critical (async possible)
     * - Language (5%): Often correlates with region, English is common
     *
     * Note: SIZE criterion removed - team capacity is now a filter (not a score).
     * The 5% from SIZE was redistributed to Composition, which is the most impactful
     * criterion after Skill.
     *
     * @param MatchmakingRequest|null $request Optional request for scoped config
     * @return array Associative array of criterion => weight
     */
    protected function getMatchmakingWeights(?MatchmakingRequest $request = null): array
    {
        if ($request) {
            $config = $this->getConfiguration($request);
            return $config->weights;
        }

        // Fallback to default if no request provided
        $config = MatchmakingConfiguration::getActiveForScope('all');
        return $config->weights;
    }

    /**
     * Get minimum compatibility threshold from configuration
     *
     * @param MatchmakingRequest $request The request
     * @return float Minimum compatibility percentage
     */
    protected function getMinimumCompatibilityThreshold(MatchmakingRequest $request): float
    {
        $config = $this->getConfiguration($request);
        return $config->thresholds['min_compatibility'] ?? 50;
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

        // Handle unranked players with neutral compatibility
        if ($requestSkillLevel === 'unranked') {
            // Unranked players get 50% compatibility with all teams
            // This allows them to match but with reduced confidence
            \Log::debug('MatchmakingService::calculateSkillCompatibilityForTeam - Unranked player', [
                'team_id' => $team->id,
                'request_user_id' => $request->user_id,
                'request_skill_level' => $requestSkillLevel,
                'compatibility' => 50.0,
            ]);
            return 50.0;
        }

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
     * Calculate region/server compatibility with geographic proximity
     *
     * Returns normalized score [0, 1] based on:
     * 1. Server preference match (highest priority)
     * 2. Geographic region proximity using latency-based matrix
     * 3. Neutral score if no preferences specified
     *
     * Scoring Logic (Phase 4 - Proximity-aware):
     * - Perfect server match: 1.0
     * - Same region: 1.0
     * - Adjacent regions: 0.45-0.70 (based on proximity matrix)
     * - Distant regions: 0.20-0.45
     * - Different preferred server: 0.40
     * - No preferences: 0.70 (neutral)
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized region compatibility score [0.0, 1.0]
     */
    protected function calculateRegionCompatibilityForTeam(Team $team, MatchmakingRequest $request): float
    {
        // Get team region from direct field (new) or fallback to team_data (old)
        $teamRegion = $team->team_data['preferred_region'] ?? null;

        // Get request regions from new direct field
        $requestRegions = $request->preferred_regions ?? [];

        // Legacy fallback to server_preferences
        $requestPrefs = $request->server_preferences ?? [];

        // Priority 1: Server preference match (highest priority)
        // Only apply server matching if team has a server
        if ($team->server_id && !empty($requestPrefs) && is_array($requestPrefs)) {
            // Check if team's server is in the preferred list
            if (in_array($team->server_id, $requestPrefs)) {
                \Log::debug('Region compatibility: Server preference match', [
                    'team_id' => $team->id,
                    'request_id' => $request->id,
                    'team_server_id' => $team->server_id,
                    'preferred_servers' => $requestPrefs,
                    'score' => 1.0,
                ]);

                return 1.0; // Perfect server match
            } else {
                \Log::debug('Region compatibility: Different preferred server', [
                    'team_id' => $team->id,
                    'request_id' => $request->id,
                    'team_server_id' => $team->server_id,
                    'preferred_servers' => $requestPrefs,
                    'score' => 0.40,
                ]);

                return 0.40; // Different server
            }
        }

        // Priority 2: Geographic region proximity
        // Convert team region to uppercase for matching
        if ($teamRegion) {
            $teamRegionUpper = strtoupper(str_replace(['_east', '_west'], ['', ''], $teamRegion));

            // Check if any request regions match team region
            if (!empty($requestRegions) && is_array($requestRegions)) {
                $bestScore = 0.0;

                foreach ($requestRegions as $requestRegion) {
                    $requestRegionUpper = strtoupper($requestRegion);
                    $proximityScore = $this->getRegionProximityScore($teamRegionUpper, $requestRegionUpper);
                    $bestScore = max($bestScore, $proximityScore);
                }

                if ($bestScore > 0) {
                    \Log::debug('Region compatibility: Geographic proximity', [
                        'team_id' => $team->id,
                        'request_id' => $request->id,
                        'team_region' => $teamRegion,
                        'request_regions' => $requestRegions,
                        'best_proximity_score' => $bestScore,
                    ]);

                    return $bestScore;
                }
            }
        }

        // No specific preferences - neutral score
        \Log::debug('Region compatibility: No preferences', [
            'team_id' => $team->id,
            'request_id' => $request->id,
            'score' => 0.70,
        ]);

        return 0.70;
    }

    /**
     * Calculate team size score with refined tier-based approach
     *
     * @deprecated This method is no longer used in the matchmaking algorithm.
     * Team capacity is now handled as a filter (teams with no open slots are excluded)
     * rather than a scoring criterion. This method is kept for backward compatibility
     * and potential future use in team analytics/statistics.
     *
     * Returns normalized score [0, 1] with optimal range at 40-60% capacity.
     * Uses gradual tier-based scoring with bounded scaling to prevent edge values.
     *
     * Scoring Logic (Phase 4 - Refined tiers):
     * - Optimal (40-60% full): 1.0
     * - Good (30-40% or 60-70% full): 0.90
     * - Acceptable (20-30% or 70-80% full): 0.75
     * - Poor (10-20% or 80-90% full): 0.60
     * - Very Poor (<10% or >90% full): 0.40-0.50
     *
     * @param Team $team The team to evaluate
     * @return float Normalized team size score [0.0, 1.0]
     */
    protected function calculateTeamSizeScore(Team $team): float
    {
        $fillPercentage = ($team->current_size / $team->max_size) * 100;

        // Tier 1: Optimal range (40-60% full)
        if ($fillPercentage >= 40 && $fillPercentage <= 60) {
            \Log::debug('Team size: Optimal range', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'optimal',
                'score' => 1.0,
            ]);

            return 1.0;
        }

        // Tier 2: Good range (30-40% or 60-70% full)
        if (($fillPercentage >= 30 && $fillPercentage < 40) ||
            ($fillPercentage > 60 && $fillPercentage <= 70)) {
            \Log::debug('Team size: Good range', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'good',
                'score' => 0.90,
            ]);

            return 0.90;
        }

        // Tier 3: Acceptable range (20-30% or 70-80% full)
        if (($fillPercentage >= 20 && $fillPercentage < 30) ||
            ($fillPercentage > 70 && $fillPercentage <= 80)) {
            \Log::debug('Team size: Acceptable range', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'acceptable',
                'score' => 0.75,
            ]);

            return 0.75;
        }

        // Tier 4: Poor range (10-20% or 80-90% full)
        if (($fillPercentage >= 10 && $fillPercentage < 20) ||
            ($fillPercentage > 80 && $fillPercentage <= 90)) {
            \Log::debug('Team size: Poor range', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'poor',
                'score' => 0.60,
            ]);

            return 0.60;
        }

        // Tier 5: Very poor (<10% or >90% full)
        // Apply bounded scaling to prevent extreme values
        if ($fillPercentage < 10) {
            // Very empty - scale from 0.40 to 0.60 based on fill
            $score = 0.40 + ($fillPercentage / 10) * 0.20;

            \Log::debug('Team size: Very empty', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'very_empty',
                'score' => $score,
            ]);

            return $score;
        } else {
            // Very full - scale from 0.60 down to 0.40 as it approaches 100%
            $score = 0.60 - (($fillPercentage - 90) / 10) * 0.20;

            \Log::debug('Team size: Very full', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'fill_percentage' => $fillPercentage,
                'tier' => 'very_full',
                'score' => $score,
            ]);

            return max(0.40, $score); // Ensure minimum 0.40
        }
    }

    /**
     * Calculate activity time match using Jaccard similarity
     *
     * Converts time ranges to discrete hour slots and calculates overlap.
     * Uses precise Jaccard similarity instead of fuzzy logic for gradual scoring.
     * Returns normalized score [0, 1].
     *
     * Scoring Logic (Phase 4 - Jaccard-based):
     * - Perfect overlap (Jaccard = 1.0): 1.0
     * - High overlap (Jaccard >= 0.7): 0.85-1.0
     * - Medium overlap (Jaccard >= 0.4): 0.70-0.85
     * - Low overlap (Jaccard >= 0.2): 0.50-0.70
     * - Minimal overlap (Jaccard < 0.2): 0.30-0.50
     * - No data available: 0.70 (neutral)
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized activity time score [0.0, 1.0]
     */
    protected function calculateActivityTimeMatch(Team $team, MatchmakingRequest $request): float
    {
        // Get team's activity times from new direct field (array) or fallback to team_data (single value)
        $teamActivityTimes = $team->activity_times ?? null;

        // Fallback to legacy team_data format
        if (empty($teamActivityTimes)) {
            $legacyActivityTime = $team->team_data['activity_time'] ?? null;
            $teamActivityTimes = $legacyActivityTime ? [$legacyActivityTime] : [];
        }

        // Get request availability hours (already an array)
        $requestAvailability = $request->availability_hours ?? [];

        // If no data from either side, return neutral score
        if (empty($teamActivityTimes) || empty($requestAvailability)) {
            \Log::debug('Activity time: No data available', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_activity_times' => $teamActivityTimes,
                'request_availability' => $requestAvailability,
                'score' => 0.70,
            ]);

            return 0.70;
        }

        // Convert to arrays for comparison
        $teamTimeSlots = is_array($teamActivityTimes) ? $teamActivityTimes : [$teamActivityTimes];
        $requestTimeSlots = is_array($requestAvailability) ? $requestAvailability : [];

        // Calculate Jaccard similarity for schedule overlap
        $jaccardScore = $this->calculateScheduleOverlap($requestTimeSlots, $teamTimeSlots);

        // Apply graduated scoring based on overlap percentage
        if ($jaccardScore >= 0.70) {
            // High overlap - excellent schedule match
            $finalScore = 0.85 + ($jaccardScore - 0.70) * 0.50; // Scale 0.85-1.0

            \Log::debug('Activity time: High overlap', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_slots' => $teamTimeSlots,
                'request_slots' => $requestTimeSlots,
                'jaccard_score' => $jaccardScore,
                'final_score' => $finalScore,
            ]);

            return min(1.0, $finalScore);
        } elseif ($jaccardScore >= 0.40) {
            // Medium overlap - good schedule match
            $finalScore = 0.70 + ($jaccardScore - 0.40) * 0.50; // Scale 0.70-0.85

            \Log::debug('Activity time: Medium overlap', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_slots' => $teamTimeSlots,
                'request_slots' => $requestTimeSlots,
                'jaccard_score' => $jaccardScore,
                'final_score' => $finalScore,
            ]);

            return $finalScore;
        } elseif ($jaccardScore >= 0.20) {
            // Low overlap - some schedule compatibility
            $finalScore = 0.50 + ($jaccardScore - 0.20) * 1.0; // Scale 0.50-0.70

            \Log::debug('Activity time: Low overlap', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_slots' => $teamTimeSlots,
                'request_slots' => $requestTimeSlots,
                'jaccard_score' => $jaccardScore,
                'final_score' => $finalScore,
            ]);

            return $finalScore;
        } else {
            // Minimal overlap - poor schedule match
            $finalScore = 0.30 + ($jaccardScore * 1.0); // Scale 0.30-0.50

            \Log::debug('Activity time: Minimal overlap', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_slots' => $teamTimeSlots,
                'request_slots' => $requestTimeSlots,
                'jaccard_score' => $jaccardScore,
                'final_score' => $finalScore,
            ]);

            return $finalScore;
        }
    }

    /**
     * Calculate language compatibility between team and request
     *
     * Uses Jaccard similarity for gradual scoring in multi-language scenarios.
     * Recognizes English as a common fallback language.
     * Returns normalized score [0, 1].
     *
     * Scoring Logic (Phase 4 - Jaccard-based):
     * - Perfect overlap (Jaccard = 1.0): 1.0
     * - Partial overlap: 0.50 + (jaccard * 0.50) → [0.50, 1.0]
     * - English fallback (one has EN, other doesn't): 0.60
     * - No overlap at all: 0.30
     *
     * @param Team $team The team to evaluate
     * @param MatchmakingRequest $request The matchmaking request
     * @return float Normalized language compatibility score [0.0, 1.0]
     */
    protected function calculateLanguageCompatibility(Team $team, MatchmakingRequest $request): float
    {
        // Get team languages from new direct field or fallback to team_data
        $teamLanguages = $team->languages ?? null;

        // Fallback to legacy team_data format
        if (empty($teamLanguages)) {
            $teamLanguages = $team->team_data['languages'] ?? ['en'];
        }

        // Get request languages from new direct field
        $requestLanguages = $request->languages ?? ['en'];

        // Ensure both are arrays
        $teamLanguages = is_array($teamLanguages) ? $teamLanguages : [$teamLanguages];
        $requestLanguages = is_array($requestLanguages) ? $requestLanguages : [$requestLanguages];

        // Normalize to lowercase for comparison
        $teamLanguages = array_map('strtolower', $teamLanguages);
        $requestLanguages = array_map('strtolower', $requestLanguages);

        // Check for any direct overlap
        $overlap = array_intersect($teamLanguages, $requestLanguages);

        if (!empty($overlap)) {
            // Use Jaccard similarity for gradual scoring
            $jaccardScore = $this->calculateJaccardSimilarity($teamLanguages, $requestLanguages);

            // Scale from 0.5 to 1.0 based on overlap percentage
            $score = 0.50 + ($jaccardScore * 0.50);

            \Log::debug('Language compatibility: Direct overlap', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_languages' => $teamLanguages,
                'request_languages' => $requestLanguages,
                'overlap' => $overlap,
                'jaccard_score' => $jaccardScore,
                'final_score' => $score,
            ]);

            return $score;
        }

        // Check for English fallback (common lingua franca)
        $teamHasEnglish = in_array('en', $teamLanguages) || in_array('english', $teamLanguages);
        $requestHasEnglish = in_array('en', $requestLanguages) || in_array('english', $requestLanguages);

        if ($teamHasEnglish || $requestHasEnglish) {
            // One has English - decent fallback communication
            \Log::debug('Language compatibility: English fallback', [
                'team_id' => $team->id,
                'request_id' => $request->id,
                'team_has_english' => $teamHasEnglish,
                'request_has_english' => $requestHasEnglish,
                'score' => 0.60,
            ]);

            return 0.60;
        }

        // No overlap at all - poor communication prospect
        \Log::debug('Language compatibility: No overlap', [
            'team_id' => $team->id,
            'request_id' => $request->id,
            'team_languages' => $teamLanguages,
            'request_languages' => $requestLanguages,
            'score' => 0.30,
        ]);

        return 0.30;
    }

    /**
     * Get proximity score between two regions
     *
     * Based on geographic proximity, typical latency, and time zone overlap.
     * Returns normalized score [0, 1] where 1.0 is same region, 0.0 is most distant.
     *
     * Proximity Matrix based on:
     * - Latency (ping): Same region ~20ms, adjacent ~80ms, far ~200ms+
     * - Time zones: Overlap affects scheduling compatibility
     * - Cultural/language correlation
     *
     * @param string $region1 First region code
     * @param string $region2 Second region code
     * @return float Proximity score [0.0, 1.0]
     */
    protected function getRegionProximityScore(string $region1, string $region2): float
    {
        // Normalize to uppercase
        $region1 = strtoupper($region1);
        $region2 = strtoupper($region2);

        // Same region = perfect match
        if ($region1 === $region2) {
            return 1.0;
        }

        // Region proximity matrix
        // Format: [region1][region2] = score
        // Based on latency zones and time zone overlap
        $proximityMatrix = [
            'NA' => [
                'NA' => 1.0,
                'SA' => 0.70,  // Close, similar time zones
                'EU' => 0.50,  // Transatlantic, ~100ms
                'OCEANIA' => 0.35, // Far, poor time zones
                'ASIA' => 0.25,    // Farthest, worst time zones
                'AFRICA' => 0.40,  // Moderate distance
            ],
            'SA' => [
                'SA' => 1.0,
                'NA' => 0.70,
                'EU' => 0.45,
                'OCEANIA' => 0.20,
                'ASIA' => 0.20,
                'AFRICA' => 0.35,
            ],
            'EU' => [
                'EU' => 1.0,
                'NA' => 0.50,
                'SA' => 0.45,
                'AFRICA' => 0.65,  // Close proximity
                'ASIA' => 0.45,    // Moderate (Russia spans both)
                'OCEANIA' => 0.25,
            ],
            'ASIA' => [
                'ASIA' => 1.0,
                'OCEANIA' => 0.60, // Close proximity
                'EU' => 0.45,
                'NA' => 0.25,
                'SA' => 0.20,
                'AFRICA' => 0.40,
            ],
            'OCEANIA' => [
                'OCEANIA' => 1.0,
                'ASIA' => 0.60,
                'NA' => 0.35,
                'EU' => 0.25,
                'SA' => 0.20,
                'AFRICA' => 0.20,
            ],
            'AFRICA' => [
                'AFRICA' => 1.0,
                'EU' => 0.65,
                'NA' => 0.40,
                'ASIA' => 0.40,
                'SA' => 0.35,
                'OCEANIA' => 0.20,
            ],
        ];

        // Look up score in matrix
        if (isset($proximityMatrix[$region1][$region2])) {
            return $proximityMatrix[$region1][$region2];
        }

        // Fallback for unknown regions
        return 0.50;
    }

    /**
     * Calculate schedule overlap using Jaccard similarity
     *
     * Converts time ranges to discrete time slots and calculates overlap.
     * Example: "evening" = [18, 19, 20, 21, 22]
     *
     * @param array $userSlots User's available time slots or ranges
     * @param array $teamSlots Team's active time slots or ranges
     * @return float Schedule overlap score [0.0, 1.0]
     */
    protected function calculateScheduleOverlap(array $userSlots, array $teamSlots): float
    {
        // If either is empty, return neutral
        if (empty($userSlots) || empty($teamSlots)) {
            return 0.70;
        }

        // Convert named ranges to hour slots if needed
        $userHours = $this->expandTimeRanges($userSlots);
        $teamHours = $this->expandTimeRanges($teamSlots);

        // Use Jaccard similarity for overlap
        return $this->calculateJaccardSimilarity($userHours, $teamHours);
    }

    /**
     * Expand time range names to hour arrays
     *
     * @param array $ranges Array of time range names or hours
     * @return array Array of hour integers (0-23)
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
            'weekday_morning' => [9, 10, 11], // Workday hours
            'weekday_evening' => [18, 19, 20, 21], // After work
            'weekend_all_day' => [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21],
            'flexible' => range(0, 23), // All hours
        ];

        foreach ($ranges as $range) {
            if (is_numeric($range)) {
                // Already an hour value
                $hours[] = (int)$range;
            } elseif (isset($rangeMap[strtolower($range)])) {
                // Named range
                $hours = array_merge($hours, $rangeMap[strtolower($range)]);
            }
        }

        return array_unique($hours);
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
            '548430' => 'Deep Rock Galactic',
            '493520' => 'GTFO',
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
     * - unranked → 2 (treated as intermediate for calculations)
     * - any → 2 (treated as intermediate for calculations)
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
            'unranked' => 2,  // Treat unranked as intermediate for calculations
            'any' => 2,       // Treat 'any' as intermediate too
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