<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\ServerTag;
use App\Models\GamingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServerRecommendationService
{
    public function getRecommendationsForUser(User $user, int $limit = 5, string $strategy = 'hybrid'): Collection
    {
        $userPreferences = $user->gamingPreferences;
        
        if ($userPreferences->isEmpty()) {
            return $this->getFallbackRecommendations($user, $limit);
        }

        // Get all servers the user is NOT a member of
        $userServerIds = $user->servers->pluck('id')->toArray();
        $availableServers = Server::whereNotIn('id', $userServerIds)
            ->with(['tags', 'members'])
            ->get();

        $recommendations = [];

        foreach ($availableServers as $server) {
            $scores = $this->calculateAdvancedCompatibilityScores($user, $server);
            
            // Apply strategy-specific weighting
            $finalScore = $this->applyRecommendationStrategy($scores, $strategy);
            
            if ($finalScore > 0) {
                $recommendations[] = [
                    'server' => $server,
                    'score' => $finalScore,
                    'scores_breakdown' => $scores,
                    'reasons' => $this->getAdvancedRecommendationReasons($user, $server, $scores),
                    'strategy' => $strategy,
                ];
            }
        }

        // Sort by score and return top recommendations
        return collect($recommendations)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate advanced compatibility scores using multiple strategies (Phase 2)
     */
    protected function calculateAdvancedCompatibilityScores(User $user, Server $server): array
    {
        return [
            'content_based' => $this->calculateContentBasedScore($user, $server),
            'collaborative' => $this->calculateCollaborativeScore($user, $server),
            'social' => $this->calculateSocialScore($user, $server),
            'temporal' => $this->calculateTemporalScore($user, $server),
            'activity' => $this->calculateActivityScore($user, $server),
            'skill_match' => $this->calculateSkillMatchScore($user, $server),
        ];
    }

    /**
     * Content-based filtering (original algorithm enhanced)
     */
    protected function calculateContentBasedScore(User $user, Server $server): float
    {
        $userPreferences = $user->gamingPreferences;
        $serverTags = $server->tags;

        if ($userPreferences->isEmpty() || $serverTags->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0;
        $maxPossibleScore = 0;

        foreach ($userPreferences as $preference) {
            $gameTag = ServerTag::getGameTags()[$preference->game_appid] ?? null;
            $preferenceWeight = $preference->getPreferenceWeight();
            $playtimeHours = $preference->getPlaytimeHours();
            
            // Enhanced weighting using skill metrics
            $skillMultiplier = $this->getSkillMultiplier($user, $preference->game_appid);
            $baseScore = $preferenceWeight * (1 + min($playtimeHours / 200, 2)) * $skillMultiplier;

            $maxPossibleScore += $baseScore;

            if ($gameTag && $server->hasTag('game', $gameTag)) {
                $totalScore += $baseScore;

                // Enhanced bonus for recent activity with decay
                if ($preference->isRecentlyPlayed()) {
                    $recentBonus = $baseScore * 0.3 * ($preference->getRecentPlaytimeHours() / 10);
                    $totalScore += min($recentBonus, $baseScore * 0.5);
                    $maxPossibleScore += $baseScore * 0.5;
                }
            }
        }

        return $maxPossibleScore > 0 ? min(($totalScore / $maxPossibleScore) * 100, 100) : 0.0;
    }

    /**
     * Collaborative filtering - "Users like you also joined..."
     */
    protected function calculateCollaborativeScore(User $user, Server $server): float
    {
        try {
            // Find users with similar gaming preferences
            $similarUsers = $this->findSimilarUsers($user, 20);
            
            if ($similarUsers->isEmpty()) {
                return 0.0;
            }

            $joinCount = 0;
            $positiveActivity = 0;
            $totalSimilarUsers = $similarUsers->count();

            foreach ($similarUsers as $similarUser) {
                // Check if similar user is a member of this server
                if ($server->members->contains($similarUser)) {
                    $joinCount++;
                    
                    // Check recent activity in server (bonus for active members)
                    $recentActivity = GamingSession::where('user_id', $similarUser->id)
                        ->where('started_at', '>=', now()->subDays(7))
                        ->exists();
                    
                    if ($recentActivity) {
                        $positiveActivity++;
                    }
                }
            }

            $joinRate = $joinCount / $totalSimilarUsers;
            $activityRate = $joinCount > 0 ? $positiveActivity / $joinCount : 0;
            
            // Score based on join rate and activity rate
            $score = ($joinRate * 70) + ($activityRate * 30);
            
            return min($score, 100);

        } catch (\Exception $e) {
            Log::warning("Collaborative filtering failed: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Social network score - Steam friends influence
     */
    protected function calculateSocialScore(User $user, Server $server): float
    {
        try {
            $steamData = $user->profile->steam_data ?? [];
            $userFriends = $steamData['friends']['friends'] ?? [];
            
            if (empty($userFriends)) {
                return 0.0;
            }

            $friendsInServer = 0;
            $activeFriendsInServer = 0;
            
            foreach ($userFriends as $friend) {
                // Find Glyph user by Steam ID
                $friendUser = User::where('steam_id', $friend['steamid'])->first();
                
                if ($friendUser && $server->members->contains($friendUser)) {
                    $friendsInServer++;
                    
                    // Check if friend is currently active in gaming
                    if (($friend['personastate'] ?? 0) == 1) {
                        $activeFriendsInServer++;
                    }
                }
            }

            $friendRate = $friendsInServer / min(count($userFriends), 20); // Cap at 20 friends
            $activityBonus = $friendsInServer > 0 ? ($activeFriendsInServer / $friendsInServer) * 0.3 : 0;
            
            return min(($friendRate * 70) + ($activityBonus * 30), 100);

        } catch (\Exception $e) {
            Log::warning("Social scoring failed: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Temporal analysis - gaming schedule compatibility
     *
     * Calculates how well the user's gaming schedule matches the server's activity times.
     * Uses a multi-signal approach:
     * 1. Direct activity_pattern to activity_time tag matching (highest weight)
     * 2. Peak hours overlap with server's activity_time hour ranges (Jaccard similarity)
     * 3. Weekend preference matching for servers with 'weekend' tag
     *
     * Returns normalized score [0-100] where:
     * - 100: Perfect schedule alignment
     * - 70-90: Good overlap (same general time of day)
     * - 50: Neutral (no data or partial overlap)
     * - 20-40: Poor overlap (different times of day)
     *
     * @param User $user The user seeking recommendations
     * @param Server $server The server to evaluate
     * @return float Temporal compatibility score [0-100]
     */
    protected function calculateTemporalScore(User $user, Server $server): float
    {
        try {
            $userSchedule = $user->profile->steam_data['gaming_schedule'] ?? [];

            // Get user's temporal data
            $userPeakHours = $userSchedule['peak_hours'] ?? [];
            $userPeakDays = $userSchedule['peak_days'] ?? [];
            $userActivityPattern = $userSchedule['activity_pattern'] ?? null;

            // If user has no temporal data, return neutral score
            if (empty($userPeakHours) && empty($userPeakDays) && empty($userActivityPattern)) {
                return 50.0;
            }

            // Get server's activity_time tags
            $serverActivityTags = $server->tags
                ->where('tag_type', 'activity_time')
                ->pluck('tag_value')
                ->toArray();

            // If server has no activity_time tags, give slight positive (server is open to all times)
            if (empty($serverActivityTags)) {
                return 60.0;
            }

            $scores = [];

            // Signal 1: Direct activity pattern matching (if user has classified pattern)
            if ($userActivityPattern && $userActivityPattern !== 'unknown' && $userActivityPattern !== 'varied') {
                $patternScore = $this->matchActivityPattern($userActivityPattern, $serverActivityTags, $userPeakDays);
                $scores[] = ['score' => $patternScore, 'weight' => 0.5];
            }

            // Signal 2: Peak hours overlap with server's activity time ranges
            if (!empty($userPeakHours)) {
                $hoursScore = $this->matchPeakHoursToActivityTags($userPeakHours, $serverActivityTags);
                $scores[] = ['score' => $hoursScore, 'weight' => 0.35];
            }

            // Signal 3: Weekend matching (if server has weekend tag)
            if (in_array('weekend', $serverActivityTags) && !empty($userPeakDays)) {
                $weekendScore = $this->matchWeekendPreference($userPeakDays);
                $scores[] = ['score' => $weekendScore, 'weight' => 0.15];
            }

            // Calculate weighted average
            if (empty($scores)) {
                return 50.0;
            }

            $totalWeight = array_sum(array_column($scores, 'weight'));
            $weightedSum = 0;

            foreach ($scores as $scoreData) {
                $weightedSum += $scoreData['score'] * ($scoreData['weight'] / $totalWeight);
            }

            return min(max($weightedSum, 0), 100);

        } catch (\Exception $e) {
            Log::warning("Temporal scoring failed: " . $e->getMessage());
            return 50.0;
        }
    }

    /**
     * Match user's activity pattern against server's activity_time tags
     *
     * User patterns (from GamingSessionService): evening, evening_weekend, weekend, morning, consistent, varied
     * Server tags (from ServerTag): morning, afternoon, evening, night, weekend
     *
     * Scoring approach:
     * - Primary tag match (e.g., 'evening' user + 'evening' server): 85-100
     * - Secondary tag match (e.g., 'evening' user + 'night' server): 70-85
     * - Adjacent time slot: 40-60
     * - No overlap: 25
     *
     * @param string $userPattern User's classified activity pattern
     * @param array $serverTags Server's activity_time tag values
     * @param array $userPeakDays User's peak gaming days
     * @return float Match score [0-100]
     */
    protected function matchActivityPattern(string $userPattern, array $serverTags, array $userPeakDays = []): float
    {
        // Pattern to primary tag mapping (what the pattern primarily means)
        // Format: [primary_tag, ...secondary_tags]
        $patternToTagMap = [
            'evening' => ['evening', 'night'],           // Primarily evening, night is acceptable
            'evening_weekend' => ['evening', 'weekend', 'night'],
            'weekend' => ['weekend', 'evening', 'afternoon'],
            'morning' => ['morning', 'afternoon'],
            'consistent' => ['morning', 'afternoon', 'evening'], // Flexible throughout day
        ];

        $expectedTags = $patternToTagMap[$userPattern] ?? [];

        if (empty($expectedTags)) {
            return 50.0; // Unknown pattern, neutral score
        }

        $primaryTag = $expectedTags[0];
        $secondaryTags = array_slice($expectedTags, 1);

        // Check for primary tag match (highest score)
        if (in_array($primaryTag, $serverTags)) {
            // Primary match: 85 base + up to 15 bonus for additional matches
            $additionalMatches = count(array_intersect($secondaryTags, $serverTags));
            $bonus = min($additionalMatches * 5, 15);
            return min(85 + $bonus, 100);
        }

        // Check for secondary tag match (good score)
        $secondaryMatches = array_intersect($secondaryTags, $serverTags);
        if (!empty($secondaryMatches)) {
            // Secondary match: 65-80 based on how many secondary tags match
            $matchRatio = count($secondaryMatches) / count($secondaryTags);
            return 65 + ($matchRatio * 15);
        }

        // No direct match - check for adjacent time compatibility
        return $this->calculateAdjacentTimeScore($expectedTags, $serverTags);
    }

    /**
     * Calculate score for adjacent/compatible time slots
     *
     * Even if not exact match, evening users might be okay with night servers, etc.
     *
     * @param array $userTags Tags the user would match
     * @param array $serverTags Server's activity_time tags
     * @return float Adjacent compatibility score [0-100]
     */
    protected function calculateAdjacentTimeScore(array $userTags, array $serverTags): float
    {
        // Define time adjacency (which times are "close enough")
        $adjacencyMap = [
            'morning' => ['afternoon'],
            'afternoon' => ['morning', 'evening'],
            'evening' => ['afternoon', 'night'],
            'night' => ['evening'],
            'weekend' => ['evening', 'afternoon'], // Weekend gamers often play evening/afternoon
        ];

        $adjacentMatches = 0;

        foreach ($userTags as $userTag) {
            $adjacentTags = $adjacencyMap[$userTag] ?? [];
            if (!empty(array_intersect($adjacentTags, $serverTags))) {
                $adjacentMatches++;
            }
        }

        if ($adjacentMatches > 0) {
            // Adjacent match gives partial credit (40-60 range)
            return 40 + ($adjacentMatches / count($userTags)) * 20;
        }

        // No overlap at all - poor match
        return 25.0;
    }

    /**
     * Match user's peak hours against server's activity_time tags using Jaccard similarity
     *
     * Expands server's activity_time tags to hour ranges, then calculates
     * overlap with user's actual peak hours.
     *
     * @param array $userPeakHours User's peak gaming hours (0-23)
     * @param array $serverTags Server's activity_time tag values
     * @return float Hours overlap score [0-100]
     */
    protected function matchPeakHoursToActivityTags(array $userPeakHours, array $serverTags): float
    {
        // Expand server tags to hour ranges
        $serverHours = [];
        foreach ($serverTags as $tag) {
            $tagHours = $this->getActivityTimeHourRange($tag);
            $serverHours = array_merge($serverHours, $tagHours);
        }
        $serverHours = array_unique($serverHours);

        if (empty($serverHours)) {
            return 50.0;
        }

        // Calculate Jaccard similarity
        $jaccardScore = $this->calculateJaccardSimilarity($userPeakHours, $serverHours);

        // Convert to 0-100 scale with minimum floor
        // Even 0 overlap shouldn't go below 20 (user might still enjoy the server)
        return 20 + ($jaccardScore * 80);
    }

    /**
     * Map activity_time tag to hour ranges
     *
     * Based on common gaming patterns:
     * - Morning: 6 AM - 12 PM (early risers, before work/school)
     * - Afternoon: 12 PM - 6 PM (lunch breaks, after school)
     * - Evening: 6 PM - 11 PM (prime gaming time)
     * - Night: 11 PM - 3 AM (late night gamers)
     * - Weekend: All hours (flexible)
     *
     * @param string $activityTime The activity_time tag value
     * @return array Array of hours (0-23) for this activity time
     */
    protected function getActivityTimeHourRange(string $activityTime): array
    {
        return match($activityTime) {
            'morning' => [6, 7, 8, 9, 10, 11],
            'afternoon' => [12, 13, 14, 15, 16, 17],
            'evening' => [18, 19, 20, 21, 22],
            'night' => [23, 0, 1, 2, 3],
            'weekend' => range(0, 23), // All hours on weekends
            default => [],
        };
    }

    /**
     * Calculate Jaccard similarity between two sets
     *
     * Jaccard Index = |A ∩ B| / |A ∪ B|
     *
     * Standard set similarity metric used in recommendation systems.
     * Returns value between 0 (no overlap) and 1 (identical sets).
     *
     * @param array $set1 First set of values
     * @param array $set2 Second set of values
     * @return float Jaccard similarity [0.0 - 1.0]
     */
    protected function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        if (empty($set1) || empty($set2)) {
            return 0.0;
        }

        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        if (empty($union)) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    /**
     * Check if user's peak days align with weekend gaming
     *
     * @param array $userPeakDays User's most active gaming days (e.g., ['saturday', 'sunday', 'friday'])
     * @return float Weekend match score [0-100]
     */
    protected function matchWeekendPreference(array $userPeakDays): float
    {
        $weekendDays = ['friday', 'saturday', 'sunday'];

        $overlap = array_intersect($userPeakDays, $weekendDays);
        $overlapRatio = count($overlap) / count($weekendDays);

        // Strong weekend preference gets high score
        if ($overlapRatio >= 0.67) { // 2+ weekend days
            return 90.0;
        } elseif ($overlapRatio >= 0.33) { // 1 weekend day
            return 70.0;
        }

        // User prefers weekdays, weekend server is poor match
        return 35.0;
    }

    /**
     * Server activity score
     */
    protected function calculateActivityScore(User $user, Server $server): float
    {
        // Member count scoring (optimized curve)
        $memberCount = $server->members->count();
        $memberScore = 0;
        
        if ($memberCount >= 5 && $memberCount <= 20) {
            $memberScore = 40; // Sweet spot for small communities
        } elseif ($memberCount > 20 && $memberCount <= 100) {
            $memberScore = 35; // Good for medium communities
        } elseif ($memberCount > 100 && $memberCount <= 500) {
            $memberScore = 25; // Large but manageable
        } elseif ($memberCount > 500) {
            $memberScore = 15; // Very large communities
        }

        // Recent activity scoring
        $recentActivity = $server->channels()
            ->whereHas('messages', function($query) {
                $query->where('created_at', '>', now()->subDays(3));
            })
            ->count();
        
        $activityScore = min($recentActivity * 10, 30); // Max 30 points for activity

        // Server age scoring (newer servers get slight boost)
        $ageScore = 0;
        if ($server->created_at > now()->subDays(30)) {
            $ageScore = 15; // New server bonus
        } elseif ($server->created_at > now()->subDays(90)) {
            $ageScore = 10; // Recently created bonus
        }

        return min($memberScore + $activityScore + $ageScore, 100);
    }

    /**
     * Skill level compatibility
     */
    protected function calculateSkillMatchScore(User $user, Server $server): float
    {
        try {
            $userSkillMetrics = $user->profile->steam_data['skill_metrics'] ?? [];
            
            if (empty($userSkillMetrics)) {
                return 50.0; // Neutral if no skill data
            }

            // Analyze server's skill level tags
            $serverSkillTags = $server->tags->where('tag_type', 'skill_level')->pluck('tag_value')->toArray();
            
            if (empty($serverSkillTags)) {
                return 60.0; // Slight positive if server has no skill requirements
            }

            $compatibilityScores = [];
            
            foreach ($userSkillMetrics as $appId => $metrics) {
                $userSkillLevel = $metrics['skill_level'] ?? 'beginner';
                
                foreach ($serverSkillTags as $serverSkill) {
                    $compatibility = $this->calculateSkillCompatibility($userSkillLevel, $serverSkill);
                    $compatibilityScores[] = $compatibility;
                }
            }

            return empty($compatibilityScores) ? 50.0 : max($compatibilityScores);

        } catch (\Exception $e) {
            Log::warning("Skill matching failed: " . $e->getMessage());
            return 50.0;
        }
    }

    protected function calculateSecondaryScores(User $user, Server $server): float
    {
        $score = 0;

        // Member count scoring (sweet spot around 10-50 members)
        $memberCount = $server->members->count();
        if ($memberCount >= 5 && $memberCount <= 100) {
            $score += 10;
        } elseif ($memberCount > 100) {
            $score += 5;
        }

        // Activity scoring (servers with recent channels)
        $hasRecentActivity = $server->channels()
            ->whereHas('messages', function($query) {
                $query->where('created_at', '>', now()->subDays(7));
            })
            ->exists();

        if ($hasRecentActivity) {
            $score += 10;
        }

        // Freshness bonus (newer servers get slight boost)
        if ($server->created_at > now()->subDays(30)) {
            $score += 5;
        }

        return $score;
    }

    protected function getRecommendationReasons(User $user, Server $server): array
    {
        $reasons = [];
        $userPreferences = $user->gamingPreferences;

        foreach ($userPreferences as $preference) {
            $gameTag = ServerTag::getGameTags()[$preference->game_appid] ?? null;
            
            if ($gameTag && $server->hasTag('game', $gameTag)) {
                $hours = $preference->getPlaytimeHours();
                $reasons[] = "Based on your {$hours} hours in {$preference->game_name}";
            }
        }

        // Add member count reason
        $memberCount = $server->members->count();
        if ($memberCount >= 10) {
            $reasons[] = "Active community with {$memberCount} members";
        }

        return array_slice($reasons, 0, 2); // Limit to 2 main reasons
    }

    public function suggestTagsForServer(Server $server): array
    {
        $suggestions = [];
        
        // Analyze member gaming patterns with enhanced data
        $members = $server->members()
            ->with(['gamingPreferences', 'gamingSessions', 'profile'])
            ->get();

        if ($members->isEmpty()) {
            return $this->getDefaultServerSuggestions($server);
        }

        // Enhanced game analysis using multiple data sources
        $suggestions = array_merge($suggestions, $this->analyzeGamePatterns($server, $members));
        
        // Skill level analysis using Steam skill metrics
        $suggestions = array_merge($suggestions, $this->analyzeSkillLevels($server, $members));
        
        // Activity time analysis using gaming sessions
        $suggestions = array_merge($suggestions, $this->analyzeActivityTimes($server, $members));
        
        // Language and region analysis using Steam data
        $suggestions = array_merge($suggestions, $this->analyzeRegionAndLanguage($server, $members));
        
        // Advanced behavioral analysis
        $suggestions = array_merge($suggestions, $this->analyzeBehavioralPatterns($server, $members));

        // Sort by confidence and remove duplicates
        return collect($suggestions)
            ->unique(function($item) {
                return $item['type'] . '_' . $item['value'];
            })
            ->sortByDesc('confidence')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Enhanced game pattern analysis (Phase 2)
     */
    protected function analyzeGamePatterns(Server $server, Collection $members): array
    {
        $suggestions = [];
        $gameData = [];

        foreach ($members as $member) {
            // Analyze gaming preferences
            foreach ($member->gamingPreferences as $preference) {
                $appId = $preference->game_appid;
                if (!isset($gameData[$appId])) {
                    $gameData[$appId] = [
                        'name' => $preference->game_name,
                        'tag' => ServerTag::getGameTags()[$appId] ?? null,
                        'members' => 0,
                        'total_hours' => 0,
                        'skill_levels' => [],
                        'recent_activity' => 0,
                    ];
                }
                
                $gameData[$appId]['members']++;
                $gameData[$appId]['total_hours'] += $preference->getPlaytimeHours();
                $gameData[$appId]['skill_levels'][] = $preference->skill_level;
                
                if ($preference->isRecentlyPlayed()) {
                    $gameData[$appId]['recent_activity']++;
                }
            }

            // Analyze recent gaming sessions
            $recentSessions = $member->gamingSessions()
                ->where('started_at', '>=', now()->subDays(30))
                ->get();
                
            foreach ($recentSessions as $session) {
                $appId = $session->game_appid;
                if (isset($gameData[$appId])) {
                    $gameData[$appId]['recent_activity'] += 0.5; // Session boost
                }
            }
        }

        // Calculate game suggestions with enhanced scoring
        foreach ($gameData as $appId => $data) {
            if (!$data['tag'] || $server->hasTag('game', $data['tag'])) {
                continue;
            }

            $memberPercentage = ($data['members'] / $members->count()) * 100;
            $avgHours = $data['total_hours'] / max($data['members'], 1);
            $recentActivityScore = ($data['recent_activity'] / $members->count()) * 100;
            
            // Enhanced confidence calculation
            $confidence = min(
                ($memberPercentage * 0.4) + 
                (min($avgHours / 50, 2) * 20) + 
                ($recentActivityScore * 0.4), 
                100
            );

            if ($confidence >= 25) {
                $suggestions[] = [
                    'type' => 'game',
                    'value' => $data['tag'],
                    'reason' => "{$memberPercentage}% of members play {$data['name']} (avg {$avgHours}h)",
                    'confidence' => round($confidence),
                    'data' => $data,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Enhanced skill level analysis using Steam metrics (Phase 2)
     */
    protected function analyzeSkillLevels(Server $server, Collection $members): array
    {
        $suggestions = [];
        $skillData = [];

        foreach ($members as $member) {
            $steamData = $member->profile->steam_data ?? [];
            $skillMetrics = $steamData['skill_metrics'] ?? [];
            
            foreach ($skillMetrics as $appId => $metrics) {
                $skillLevel = $metrics['skill_level'] ?? 'beginner';
                $skillData[$skillLevel] = ($skillData[$skillLevel] ?? 0) + 1;
            }
        }

        if (!empty($skillData)) {
            arsort($skillData);
            $dominantSkill = array_key_first($skillData);
            $skillCount = $skillData[$dominantSkill];
            $percentage = ($skillCount / $members->count()) * 100;

            if ($percentage >= 40 && !$server->hasTag('skill_level', $dominantSkill)) {
                $suggestions[] = [
                    'type' => 'skill_level',
                    'value' => $dominantSkill,
                    'reason' => "{$percentage}% of members are {$dominantSkill} level players",
                    'confidence' => min($percentage + 20, 100),
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Activity time analysis using gaming sessions (Phase 2)
     */
    protected function analyzeActivityTimes(Server $server, Collection $members): array
    {
        $suggestions = [];
        $timePatterns = [];

        foreach ($members as $member) {
            $steamData = $member->profile->steam_data ?? [];
            $schedule = $steamData['gaming_schedule'] ?? [];
            
            if (!empty($schedule['activity_pattern'])) {
                $pattern = $schedule['activity_pattern'];
                $timePatterns[$pattern] = ($timePatterns[$pattern] ?? 0) + 1;
            }
        }

        if (!empty($timePatterns)) {
            arsort($timePatterns);
            $dominantPattern = array_key_first($timePatterns);
            $patternCount = $timePatterns[$dominantPattern];
            $percentage = ($patternCount / $members->count()) * 100;

            // Map patterns to activity_time tags
            $activityTimeMap = [
                'evening' => 'evening',
                'evening_weekend' => 'evening',
                'weekend' => 'weekend',
                'morning' => 'morning',
                'consistent' => 'daily',
            ];

            $activityTime = $activityTimeMap[$dominantPattern] ?? null;
            
            if ($activityTime && $percentage >= 30 && !$server->hasTag('activity_time', $activityTime)) {
                $suggestions[] = [
                    'type' => 'activity_time',
                    'value' => $activityTime,
                    'reason' => "{$percentage}% of members are most active during {$activityTime}",
                    'confidence' => min($percentage + 10, 90),
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Region and language analysis (Phase 2)
     */
    protected function analyzeRegionAndLanguage(Server $server, Collection $members): array
    {
        $suggestions = [];
        
        // For now, suggest English as default if no language tag
        if (!$server->hasTag('language', 'english') && $members->count() >= 3) {
            $suggestions[] = [
                'type' => 'language',
                'value' => 'english',
                'reason' => 'Default language for international gaming communities',
                'confidence' => 60,
            ];
        }

        return $suggestions;
    }

    /**
     * Behavioral pattern analysis (Phase 2)
     */
    protected function analyzeBehavioralPatterns(Server $server, Collection $members): array
    {
        $suggestions = [];
        
        // Analyze if this is a competitive vs casual community
        $competitiveIndicators = 0;
        $casualIndicators = 0;
        
        foreach ($members as $member) {
            $skillMetrics = $member->profile->steam_data['skill_metrics'] ?? [];
            
            foreach ($skillMetrics as $metrics) {
                $skillLevel = $metrics['skill_level'] ?? 'beginner';
                $playtimeHours = $metrics['playtime_hours'] ?? 0;
                
                if (in_array($skillLevel, ['advanced', 'expert']) || $playtimeHours > 200) {
                    $competitiveIndicators++;
                } else {
                    $casualIndicators++;
                }
            }
        }

        $total = $competitiveIndicators + $casualIndicators;
        if ($total > 0) {
            $competitiveRate = ($competitiveIndicators / $total) * 100;
            
            if ($competitiveRate >= 60 && !$server->hasTag('skill_level', 'advanced')) {
                $suggestions[] = [
                    'type' => 'skill_level',
                    'value' => 'advanced',
                    'reason' => "Community shows competitive gaming patterns ({$competitiveRate}% advanced players)",
                    'confidence' => min($competitiveRate, 85),
                ];
            } elseif ($competitiveRate <= 30 && !$server->hasTag('skill_level', 'beginner')) {
                $suggestions[] = [
                    'type' => 'skill_level',
                    'value' => 'beginner',
                    'reason' => "Community is welcoming to new players ({100-$competitiveRate}% casual players)",
                    'confidence' => min(100 - $competitiveRate, 80),
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Default suggestions for new servers (Phase 2)
     */
    protected function getDefaultServerSuggestions(Server $server): array
    {
        $suggestions = [];
        
        // Suggest common starting tags for new servers
        if (!$server->hasTag('language', 'english')) {
            $suggestions[] = [
                'type' => 'language',
                'value' => 'english',
                'reason' => 'Standard language for international gaming communities',
                'confidence' => 70,
            ];
        }

        if (!$server->hasTag('skill_level', 'intermediate')) {
            $suggestions[] = [
                'type' => 'skill_level',
                'value' => 'intermediate',
                'reason' => 'Good starting point for welcoming all skill levels',
                'confidence' => 60,
            ];
        }

        return $suggestions;
    }

    // ========== Phase 2 Advanced Algorithm Helper Methods ==========

    /**
     * Apply recommendation strategy weighting
     */
    protected function applyRecommendationStrategy(array $scores, string $strategy): float
    {
        switch ($strategy) {
            case 'content_based':
                return $scores['content_based'] * 0.8 + $scores['activity'] * 0.2;
                
            case 'collaborative':
                return $scores['collaborative'] * 0.6 + $scores['social'] * 0.4;
                
            case 'social':
                return $scores['social'] * 0.7 + $scores['collaborative'] * 0.3;
                
            case 'temporal':
                return $scores['temporal'] * 0.6 + $scores['activity'] * 0.4;
                
            case 'hybrid':
            default:
                return ($scores['content_based'] * 0.25) +
                       ($scores['collaborative'] * 0.20) +
                       ($scores['social'] * 0.15) +
                       ($scores['temporal'] * 0.15) +
                       ($scores['activity'] * 0.15) +
                       ($scores['skill_match'] * 0.10);
        }
    }

    /**
     * Find users with similar gaming preferences
     */
    protected function findSimilarUsers(User $user, int $limit = 20): Collection
    {
        $userGameAppIds = $user->gamingPreferences->pluck('game_appid')->toArray();
        
        if (empty($userGameAppIds)) {
            return collect();
        }

        // Find users with overlapping game preferences
        $similarUsers = User::whereHas('gamingPreferences', function($query) use ($userGameAppIds) {
            $query->whereIn('game_appid', $userGameAppIds);
        })
        ->where('id', '!=', $user->id)
        ->with('gamingPreferences')
        ->get();

        // Calculate similarity scores
        $scoredUsers = $similarUsers->map(function($otherUser) use ($user, $userGameAppIds) {
            $otherGameAppIds = $otherUser->gamingPreferences->pluck('game_appid')->toArray();
            $commonGames = array_intersect($userGameAppIds, $otherGameAppIds);
            $similarity = count($commonGames) / count(array_unique(array_merge($userGameAppIds, $otherGameAppIds)));
            
            return [
                'user' => $otherUser,
                'similarity' => $similarity
            ];
        })
        ->sortByDesc('similarity')
        ->take($limit);

        return $scoredUsers->pluck('user');
    }

    /**
     * Get skill multiplier for enhanced scoring
     */
    protected function getSkillMultiplier(User $user, string $gameAppId): float
    {
        $skillMetrics = $user->profile->steam_data['skill_metrics'] ?? [];
        
        if (!isset($skillMetrics[$gameAppId])) {
            return 1.0;
        }

        $skillLevel = $skillMetrics[$gameAppId]['skill_level'] ?? 'beginner';
        
        return match($skillLevel) {
            'expert' => 1.5,
            'advanced' => 1.3,
            'intermediate' => 1.1,
            'beginner' => 0.9,
            default => 1.0,
        };
    }

    /**
     * Calculate skill compatibility between user and server
     */
    protected function calculateSkillCompatibility(string $userSkill, string $serverSkill): float
    {
        $skillLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        
        $userLevel = $skillLevels[$userSkill] ?? 1;
        $serverLevel = $skillLevels[$serverSkill] ?? 1;
        
        $difference = abs($userLevel - $serverLevel);
        
        return match($difference) {
            0 => 100.0, // Perfect match
            1 => 80.0,  // Close match
            2 => 60.0,  // Moderate match
            3 => 30.0,  // Poor match
            default => 10.0,
        };
    }

    /**
     * Get fallback recommendations when user has no preferences
     */
    protected function getFallbackRecommendations(User $user, int $limit): Collection
    {
        // Get most popular servers for new users
        $popularServers = Server::withCount('members')
            ->whereDoesntHave('members', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('members_count')
            ->take($limit * 2) // Get more to filter
            ->get();

        $recommendations = $popularServers->map(function($server) {
            return [
                'server' => $server,
                'score' => $this->calculateActivityScore(new User(), $server),
                'scores_breakdown' => ['activity' => $this->calculateActivityScore(new User(), $server)],
                'reasons' => ['Popular community with ' . $server->members_count . ' members'],
                'strategy' => 'popular',
            ];
        });

        return $recommendations->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Get advanced recommendation reasons with multiple factors
     *
     * Generates human-readable reasons explaining why a server was recommended.
     * Only includes reasons for factors that actually contributed significantly
     * to the match score, ensuring honest and transparent recommendations.
     *
     * @param User $user The user receiving recommendations
     * @param Server $server The recommended server
     * @param array $scores Score breakdown by criterion
     * @return array Up to 3 human-readable reason strings
     */
    protected function getAdvancedRecommendationReasons(User $user, Server $server, array $scores): array
    {
        $reasons = [];

        // Content-based reasons (game matching)
        if ($scores['content_based'] > 50) {
            $userPreferences = $user->gamingPreferences;
            foreach ($userPreferences as $preference) {
                $gameTag = ServerTag::getGameTags()[$preference->game_appid] ?? null;
                if ($gameTag && $server->hasTag('game', $gameTag)) {
                    $hours = $preference->getPlaytimeHours();
                    $reasons[] = "Matches your {$hours}h in {$preference->game_name}";
                    break; // Only show top match
                }
            }
        }

        // Temporal reasons (schedule matching) - NEW
        if ($scores['temporal'] > 65) {
            $reason = $this->getTemporalReasonText($user, $server, $scores['temporal']);
            if ($reason) {
                $reasons[] = $reason;
            }
        }

        // Skill match reasons
        if ($scores['skill_match'] > 70) {
            $reasons[] = "Good skill level match for your gaming experience";
        }

        // Social reasons
        if ($scores['social'] > 30) {
            $reasons[] = "Your Steam friends are active in this community";
        }

        // Collaborative reasons
        if ($scores['collaborative'] > 30) {
            $reasons[] = "Users with similar preferences joined this server";
        }

        // Activity reasons
        if ($scores['activity'] > 60) {
            $memberCount = $server->members->count();
            $reasons[] = "Active community with {$memberCount} members";
        }

        return array_slice($reasons, 0, 3); // Limit to 3 reasons
    }

    /**
     * Generate human-readable reason text for temporal matching
     *
     * @param User $user The user
     * @param Server $server The server
     * @param float $temporalScore The temporal score achieved
     * @return string|null Reason text or null if not significant
     */
    protected function getTemporalReasonText(User $user, Server $server, float $temporalScore): ?string
    {
        $userSchedule = $user->profile->steam_data['gaming_schedule'] ?? [];
        $userActivityPattern = $userSchedule['activity_pattern'] ?? null;

        $serverActivityTags = $server->tags
            ->where('tag_type', 'activity_time')
            ->pluck('tag_value')
            ->toArray();

        if (empty($serverActivityTags)) {
            return null;
        }

        // Map activity patterns to readable text
        $patternText = match($userActivityPattern) {
            'evening' => 'evening',
            'evening_weekend' => 'evening and weekend',
            'weekend' => 'weekend',
            'morning' => 'morning',
            'consistent' => 'regular',
            default => null,
        };

        // Map server tags to readable text
        $serverTimeText = $this->formatActivityTags($serverActivityTags);

        if ($temporalScore >= 85 && $patternText) {
            return "Active during your {$patternText} gaming hours";
        } elseif ($temporalScore >= 70) {
            return "Members active {$serverTimeText} match your schedule";
        }

        return null;
    }

    /**
     * Format activity time tags into readable text
     *
     * @param array $tags Activity time tag values
     * @return string Formatted text like "evenings and weekends"
     */
    protected function formatActivityTags(array $tags): string
    {
        $tagLabels = [
            'morning' => 'mornings',
            'afternoon' => 'afternoons',
            'evening' => 'evenings',
            'night' => 'late nights',
            'weekend' => 'weekends',
        ];

        $labels = array_map(fn($tag) => $tagLabels[$tag] ?? $tag, $tags);

        if (count($labels) === 1) {
            return $labels[0];
        } elseif (count($labels) === 2) {
            return implode(' and ', $labels);
        } else {
            $last = array_pop($labels);
            return implode(', ', $labels) . ', and ' . $last;
        }
    }
}