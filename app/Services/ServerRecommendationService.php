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
     */
    protected function calculateTemporalScore(User $user, Server $server): float
    {
        try {
            $userSchedule = $user->profile->steam_data['gaming_schedule'] ?? [];
            
            if (empty($userSchedule['peak_hours']) && empty($userSchedule['peak_days'])) {
                return 50.0; // Neutral score if no data
            }

            // Analyze server member activity patterns
            $serverActivityScore = $this->analyzeServerActivityPatterns($server, $userSchedule);
            
            return $serverActivityScore;

        } catch (\Exception $e) {
            Log::warning("Temporal scoring failed: " . $e->getMessage());
            return 50.0;
        }
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
     * Analyze server activity patterns for temporal matching
     */
    protected function analyzeServerActivityPatterns(Server $server, array $userSchedule): float
    {
        // Simulate server activity analysis based on member sessions
        // In a real implementation, this would analyze when server members are most active
        
        $userPeakHours = $userSchedule['peak_hours'] ?? [];
        $userPeakDays = $userSchedule['peak_days'] ?? [];
        
        if (empty($userPeakHours) && empty($userPeakDays)) {
            return 50.0;
        }

        // For now, return a score based on server size and activity
        // Larger servers are more likely to have activity during user's peak times
        $memberCount = $server->members->count();
        
        if ($memberCount > 50) {
            return 75.0; // Large servers likely have activity during peak times
        } elseif ($memberCount > 20) {
            return 65.0; // Medium servers moderately likely
        } elseif ($memberCount > 5) {
            return 55.0; // Small servers less likely but possible
        }
        
        return 45.0; // Very small servers unlikely to match schedule
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
     */
    protected function getAdvancedRecommendationReasons(User $user, Server $server, array $scores): array
    {
        $reasons = [];
        
        // Content-based reasons
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

        // Collaborative reasons
        if ($scores['collaborative'] > 30) {
            $reasons[] = "Users with similar preferences joined this server";
        }

        // Social reasons
        if ($scores['social'] > 30) {
            $reasons[] = "Your Steam friends are active in this community";
        }

        // Activity reasons
        if ($scores['activity'] > 60) {
            $memberCount = $server->members->count();
            $reasons[] = "Active community with {$memberCount} members";
        }

        // Skill match reasons
        if ($scores['skill_match'] > 70) {
            $reasons[] = "Good skill level match for your gaming experience";
        }

        return array_slice($reasons, 0, 3); // Limit to 3 reasons
    }
}