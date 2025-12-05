<?php

namespace App\Services;

use App\Models\User;
use App\Services\GamingSessionService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SteamApiService
{
    protected $client;
    protected $apiKey;
    protected $gamingSessionService;
    /**
     * Supported games for skill calculation (Phase 1: Limited to 3 games)
     * Other games (Apex, Rust, PUBG, R6S, Fall Guys) can be added in future phases
     */
    protected $supportedGames = [
        730 => 'CS2',
        570 => 'Dota 2',
        230410 => 'Warframe',
    ];

    public function __construct(GamingSessionService $gamingSessionService)
    {
        $this->client = new Client([
            'base_uri' => 'http://api.steampowered.com/',
            'timeout' => 10.0,
        ]);
        $this->apiKey = config('services.steam.api_key');
        $this->gamingSessionService = $gamingSessionService;
    }

    public function validateOpenId($params)
    {
        $client = new Client();
        $response = $client->post('https://steamcommunity.com/openid/login', [
            'form_params' => $params,
        ]);

        return $response->getBody()->getContents();
    }

    public function fetchUserData(User $user)
    {
        if (!$user->steam_id) {
            return;
        }

        try {
            $cacheKey = "steam_data_{$user->steam_id}";
            
            // Check cache first
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                $user->profile->update(['steam_data' => $cachedData]);
                return;
            }

            $steamData = [
                'profile' => $this->getPlayerSummary($user->steam_id),
                'games' => $this->getOwnedGames($user->steam_id),
                'achievements' => $this->getPlayerAchievements($user->steam_id),
                'friends' => $this->getSteamFriends($user->steam_id),
                'current_game' => null,
                'play_sessions' => $this->getRecentPlaySessions($user->steam_id),
                'skill_metrics' => [],
                'gaming_schedule' => [],
                'last_updated' => now()->toDateTimeString(),
            ];

            // Check if currently in-game (Enhanced for Phase 2 real-time status)
            // Check both gameid and gameextrainfo for better detection
            if (isset($steamData['profile']['gameid']) || isset($steamData['profile']['gameextrainfo'])) {
                $steamData['current_game'] = [
                    'appid' => $steamData['profile']['gameid'] ?? null,
                    'name' => $steamData['profile']['gameextrainfo'] ?? 'Unknown Game',
                    'server_name' => $steamData['profile']['gameserver'] ?? null,
                    'server_ip' => $steamData['profile']['gameserverip'] ?? null,
                    'server_port' => $steamData['profile']['gameserverport'] ?? null,
                    'map' => $steamData['profile']['gameservermap'] ?? null,
                    'game_mode' => $steamData['profile']['gamemode'] ?? null,
                    'lobby_id' => $steamData['profile']['lobbysteamid'] ?? null,
                    'timestamp' => now()->toDateTimeString(),
                    'rich_presence' => $steamData['profile']['richpresence'] ?? null,
                ];
            }

            // Calculate skill metrics and gaming schedule (Phase 2)
            if (!empty($steamData['games']) && !empty($steamData['achievements'])) {
                $steamData['skill_metrics'] = $this->calculateSkillMetrics($steamData['games'], $steamData['achievements']);
                $steamData['gaming_schedule'] = $this->analyzeGamingSchedule($user->steam_id);
            }

            // Cache for 5 minutes (Phase 2: Enhanced real-time status updates)
            Cache::put($cacheKey, $steamData, 300);

            // Get previous game state for change detection (Phase 2)
            $previousSteamData = $user->profile->steam_data ?? [];
            $previousGame = $previousSteamData['current_game'] ?? null;
            $currentGame = $steamData['current_game'] ?? null;

            // Update profile
            $user->profile->update([
                'steam_data' => $steamData,
                'avatar_url' => $steamData['profile']['avatarfull'] ?? $user->profile->avatar_url,
            ]);

            // Update gaming preferences
            if (!empty($steamData['games'])) {
                $user->updateGamingPreferences($steamData['games']);
            }

            // Broadcast game state changes (Phase 2)
            $this->broadcastGameStateChanges($user, $previousGame, $currentGame);
            
            // Track gaming sessions (Phase 2)
            $this->trackGamingSessions($user, $previousGame, $currentGame);

        } catch (\Exception $e) {
            Log::error('Steam API Error: ' . $e->getMessage());
        }
    }

    /**
     * Get player summary from Steam API with enhanced connectable detection.
     *
     * Fetches player data from Steam Web API and enriches it with connectable
     * server detection for CS2 lobby integration. This determines if the player
     * is currently on a server that can be joined via Steam connect protocol.
     *
     * @param string $steamId The Steam ID of the player
     * @return array Player data with additional fields:
     *               - is_connectable (bool): Whether player is on a joinable server
     *               - connect_url (string|null): Steam connect URL if available
     * @throws \GuzzleHttp\Exception\GuzzleException If API request fails
     */
    protected function getPlayerSummary($steamId)
    {
        $response = $this->client->get('ISteamUser/GetPlayerSummaries/v0002/', [
            'query' => [
                'key' => $this->apiKey,
                'steamids' => $steamId,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $playerData = $data['response']['players'][0] ?? [];

        // Enhance player data with connectable detection
        if (!empty($playerData)) {
            $playerData['is_connectable'] = $this->isConnectable($playerData);
            $playerData['connect_url'] = $this->getConnectUrl($playerData);
        }

        return $playerData;
    }

    /**
     * Determine if a player is on a connectable game server.
     *
     * Checks if the player data contains valid game server IP information
     * that can be used to join their current session. A server is considered
     * connectable if the gameserverip field exists, is not empty, and is not
     * the default invalid value '0.0.0.0:0'.
     *
     * @param array $playerData Player data from Steam API
     * @return bool True if player is on a connectable server, false otherwise
     */
    protected function isConnectable(array $playerData): bool
    {
        // Check if gameserverip field exists
        if (!isset($playerData['gameserverip'])) {
            return false;
        }

        $serverIp = $playerData['gameserverip'];

        // Check if field is empty
        if (empty($serverIp)) {
            return false;
        }

        // Check if it's the default invalid value
        if ($serverIp === '0.0.0.0:0') {
            return false;
        }

        // Additional validation: Ensure it's a valid IP:port format
        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d+$/', $serverIp)) {
            Log::warning("Invalid game server IP format: {$serverIp}", [
                'steam_id' => $playerData['steamid'] ?? 'unknown',
                'game_id' => $playerData['gameid'] ?? 'unknown'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Generate Steam connect URL for joining a player's server.
     *
     * Creates a steam://connect/ protocol URL that can be used to join
     * the game server where the player is currently playing. This URL
     * can be clicked or triggered to launch Steam and connect directly
     * to the server.
     *
     * @param array $playerData Player data from Steam API
     * @return string|null Steam connect URL if server is connectable, null otherwise
     */
    protected function getConnectUrl(array $playerData): ?string
    {
        // Validate that the server is connectable
        if (!$this->isConnectable($playerData)) {
            return null;
        }

        $serverIp = $playerData['gameserverip'];

        // Generate Steam connect URL
        $connectUrl = "steam://connect/{$serverIp}";

        Log::info("Generated Steam connect URL", [
            'steam_id' => $playerData['steamid'] ?? 'unknown',
            'game_id' => $playerData['gameid'] ?? 'unknown',
            'server_ip' => $serverIp,
            'connect_url' => $connectUrl
        ]);

        return $connectUrl;
    }

    protected function getOwnedGames($steamId)
    {
        $response = $this->client->get('IPlayerService/GetOwnedGames/v0001/', [
            'query' => [
                'key' => $this->apiKey,
                'steamid' => $steamId,
                'include_appinfo' => true,
                'include_played_free_games' => true,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $allGames = $data['response']['games'] ?? [];

        // Return all games (no filtering) but prioritize supported games
        $supportedGames = [];
        $otherGames = [];

        foreach ($allGames as $game) {
            if (isset($this->supportedGames[$game['appid']])) {
                $supportedGames[] = $game;
            } elseif (($game['playtime_forever'] ?? 0) > 60) { // Only include games with 1+ hour
                $otherGames[] = $game;
            }
        }

        // Sort by playtime
        usort($supportedGames, function($a, $b) {
            return $b['playtime_forever'] - $a['playtime_forever'];
        });

        usort($otherGames, function($a, $b) {
            return $b['playtime_forever'] - $a['playtime_forever'];
        });

        // Return supported games first, then other games (limited to top 10)
        return array_merge($supportedGames, array_slice($otherGames, 0, 10));
    }

    protected function getPlayerAchievements($steamId)
    {
        $achievements = [];

        foreach (array_keys($this->supportedGames) as $gameId) {
            try {
                $response = $this->client->get('ISteamUserStats/GetPlayerAchievements/v0001/', [
                    'query' => [
                        'key' => $this->apiKey,
                        'steamid' => $steamId,
                        'appid' => $gameId,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                
                if (isset($data['playerstats']['achievements'])) {
                    $gameAchievements = $data['playerstats']['achievements'];
                    $totalAchievements = count($gameAchievements);
                    $unlockedAchievements = count(array_filter($gameAchievements, function($ach) {
                        return $ach['achieved'] == 1;
                    }));

                    $achievements[$gameId] = [
                        'total' => $totalAchievements,
                        'unlocked' => $unlockedAchievements,
                        'percentage' => $totalAchievements > 0 ? round(($unlockedAchievements / $totalAchievements) * 100) : 0,
                    ];
                }
            } catch (\Exception $e) {
                // Game might not have achievements or user might not own it
                Log::info("Could not fetch achievements for game $gameId: " . $e->getMessage());
            }
        }

        return $achievements;
    }

    /**
     * Fetch detailed game stats from Steam GetUserStatsForGame API
     *
     * This method retrieves player statistics for specific games that support
     * the Steam stats API. Currently only CS2 (730) provides reliable data.
     *
     * Stats returned for CS2 include:
     * - total_kills, total_deaths (for K/D ratio)
     * - total_shots_hit, total_shots_fired (for accuracy)
     * - total_wins, total_rounds_played (for win rate)
     *
     * @param string $steamId User's Steam ID (64-bit format)
     * @param int $gameAppId Game's Steam App ID
     * @return array|null Associative array of stat name => value, or null if unavailable
     */
    public function getGameStats(string $steamId, int $gameAppId): ?array
    {
        // Only CS2 supports this API reliably
        // Dota 2 and Warframe use different stat systems
        if ($gameAppId !== 730) {
            return null;
        }

        $cacheKey = "game_stats_{$steamId}_{$gameAppId}";

        return Cache::remember($cacheKey, 300, function () use ($steamId, $gameAppId) {
            try {
                $response = $this->client->get('ISteamUserStats/GetUserStatsForGame/v2/', [
                    'query' => [
                        'key' => $this->apiKey,
                        'steamid' => $steamId,
                        'appid' => $gameAppId,
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['playerstats']['stats'])) {
                    // Convert array of {name, value} objects to keyed format
                    // From: [{"name": "total_kills", "value": 1234}, ...]
                    // To: ["total_kills" => 1234, ...]
                    $stats = [];
                    foreach ($data['playerstats']['stats'] as $stat) {
                        $stats[$stat['name']] = $stat['value'];
                    }

                    Log::info("Fetched CS2 stats for Steam ID {$steamId}", [
                        'kills' => $stats['total_kills'] ?? 0,
                        'deaths' => $stats['total_deaths'] ?? 0,
                        'rounds' => $stats['total_rounds_played'] ?? 0,
                    ]);

                    return $stats;
                }

                return null;
            } catch (\Exception $e) {
                // Common reasons for failure:
                // - Profile is private
                // - User doesn't own the game
                // - Game has no stats schema
                Log::info("Could not fetch game stats for {$steamId}/{$gameAppId}: " . $e->getMessage());
                return null;
            }
        });
    }

    public function batchFetchUserData($users)
    {
        foreach ($users as $user) {
            $this->fetchUserData($user);
            sleep(1); // Rate limiting
        }
    }

    /**
     * Force refresh user Steam data (bypasses cache) - Phase 2
     */
    public function forceRefreshUserData(User $user)
    {
        if (!$user->steam_id) {
            return false;
        }

        try {
            $cacheKey = "steam_data_{$user->steam_id}";
            
            // Clear existing cache
            Cache::forget($cacheKey);
            
            // Fetch fresh data
            $this->fetchUserData($user);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Steam API Force Refresh Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect and broadcast game state changes (Phase 2)
     */
    protected function broadcastGameStateChanges(User $user, ?array $previousGame, ?array $currentGame)
    {
        // User started playing a game
        if (!$previousGame && $currentGame) {
            event(new \App\Events\UserStartedPlaying($user, $currentGame));
            Log::info("User {$user->id} started playing {$currentGame['name']}");
        }
        
        // User stopped playing a game
        elseif ($previousGame && !$currentGame) {
            event(new \App\Events\UserStoppedPlaying($user, $previousGame));
            Log::info("User {$user->id} stopped playing {$previousGame['name']}");
        }
        
        // User changed games
        elseif ($previousGame && $currentGame && $previousGame['appid'] !== $currentGame['appid']) {
            event(new \App\Events\UserChangedGame($user, $previousGame, $currentGame));
            Log::info("User {$user->id} changed from {$previousGame['name']} to {$currentGame['name']}");
        }
        
        // User's game status changed (same game, different server/map/mode)
        elseif ($previousGame && $currentGame && $previousGame['appid'] === $currentGame['appid']) {
            $statusChanged = false;
            $changes = [];
            
            if (($previousGame['server_name'] ?? null) !== ($currentGame['server_name'] ?? null)) {
                $statusChanged = true;
                $changes[] = 'server';
            }
            
            if (($previousGame['map'] ?? null) !== ($currentGame['map'] ?? null)) {
                $statusChanged = true;
                $changes[] = 'map';
            }
            
            if (($previousGame['game_mode'] ?? null) !== ($currentGame['game_mode'] ?? null)) {
                $statusChanged = true;
                $changes[] = 'mode';
            }
            
            if ($statusChanged) {
                event(new \App\Events\UserGameStatusChanged($user, $previousGame, $currentGame, $changes));
                Log::info("User {$user->id} game status changed: " . implode(', ', $changes));
            }
        }
    }

    /**
     * Track gaming sessions based on game state changes (Phase 2)
     */
    protected function trackGamingSessions(User $user, ?array $previousGame, ?array $currentGame)
    {
        // User started playing a game
        if (!$previousGame && $currentGame) {
            $this->gamingSessionService->startSession($user, $currentGame);
        }
        
        // User stopped playing a game
        elseif ($previousGame && !$currentGame) {
            $this->gamingSessionService->endSession($user, $previousGame['appid']);
        }
        
        // User changed games
        elseif ($previousGame && $currentGame && $previousGame['appid'] !== $currentGame['appid']) {
            $this->gamingSessionService->switchGame($user, $previousGame, $currentGame);
        }
        
        // User's game status changed (same game, different server/map/mode)
        elseif ($previousGame && $currentGame && $previousGame['appid'] === $currentGame['appid']) {
            $statusChanged = false;
            $updatedData = [];
            
            if (($previousGame['server_name'] ?? null) !== ($currentGame['server_name'] ?? null)) {
                $statusChanged = true;
                $updatedData['server_name'] = $currentGame['server_name'] ?? null;
            }
            
            if (($previousGame['map'] ?? null) !== ($currentGame['map'] ?? null)) {
                $statusChanged = true;
                $updatedData['map'] = $currentGame['map'] ?? null;
            }
            
            if (($previousGame['game_mode'] ?? null) !== ($currentGame['game_mode'] ?? null)) {
                $statusChanged = true;
                $updatedData['game_mode'] = $currentGame['game_mode'] ?? null;
            }
            
            if (($previousGame['lobby_id'] ?? null) !== ($currentGame['lobby_id'] ?? null)) {
                $statusChanged = true;
                $updatedData['lobby_id'] = $currentGame['lobby_id'] ?? null;
            }
            
            if ($statusChanged) {
                $this->gamingSessionService->updateSessionData($user, $currentGame['appid'], $updatedData);
            }
        }
    }

    /**
     * Get Steam friends list (Phase 2)
     */
    protected function getSteamFriends($steamId)
    {
        try {
            $response = $this->client->get('ISteamUser/GetFriendList/v0001/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamid' => $steamId,
                    'relationship' => 'friend',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $friendsList = $data['friendslist']['friends'] ?? [];

            // Get detailed info for first 20 friends (API rate limiting)
            $friendsDetails = [];
            $friendIds = array_slice(array_column($friendsList, 'steamid'), 0, 20);
            
            if (!empty($friendIds)) {
                $friendsData = $this->getPlayerSummaries($friendIds);
                foreach ($friendsData as $friend) {
                    $friendsDetails[] = [
                        'steamid' => $friend['steamid'],
                        'personaname' => $friend['personaname'],
                        'profileurl' => $friend['profileurl'],
                        'avatar' => $friend['avatarmedium'] ?? $friend['avatar'],
                        'personastate' => $friend['personastate'],
                        'gameid' => $friend['gameid'] ?? null,
                        'gameextrainfo' => $friend['gameextrainfo'] ?? null,
                    ];
                }
            }

            return [
                'count' => count($friendsList),
                'friends' => $friendsDetails,
                'last_updated' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::info("Could not fetch friends for Steam ID $steamId: " . $e->getMessage());
            return ['count' => 0, 'friends' => [], 'last_updated' => now()->toDateTimeString()];
        }
    }

    /**
     * Get player summaries for multiple Steam IDs
     */
    protected function getPlayerSummaries($steamIds)
    {
        try {
            $steamIdsString = implode(',', $steamIds);
            $response = $this->client->get('ISteamUser/GetPlayerSummaries/v0002/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamids' => $steamIdsString,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['response']['players'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error fetching player summaries: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent play sessions (Phase 2)
     * Simulated data based on recent games - Steam API doesn't provide session history
     */
    protected function getRecentPlaySessions($steamId)
    {
        try {
            // Get recently played games (games with playtime in last 2 weeks)
            $response = $this->client->get('IPlayerService/GetRecentlyPlayedGames/v0001/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamid' => $steamId,
                    'count' => 10,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $recentGames = $data['response']['games'] ?? [];

            $sessions = [];
            foreach ($recentGames as $game) {
                if (($game['playtime_2weeks'] ?? 0) > 0) {
                    // Estimate session data based on recent playtime
                    $sessions[] = [
                        'appid' => $game['appid'],
                        'name' => $game['name'],
                        'playtime_2weeks' => $game['playtime_2weeks'],
                        'estimated_sessions' => max(1, intval($game['playtime_2weeks'] / 120)), // ~2 hour sessions
                        'last_played' => now()->subDays(rand(0, 13))->toDateTimeString(),
                    ];
                }
            }

            return $sessions;
        } catch (\Exception $e) {
            Log::info("Could not fetch recent sessions for Steam ID $steamId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate skill metrics based on achievements and playtime (Phase 2)
     */
    protected function calculateSkillMetrics($games, $achievements)
    {
        $skillMetrics = [];

        foreach ($games as $game) {
            $appId = $game['appid'];
            $playtimeHours = ($game['playtime_forever'] ?? 0) / 60;
            
            if ($playtimeHours > 10 && isset($achievements[$appId])) { // Only analyze games with 10+ hours
                $achievementData = $achievements[$appId];
                $achievementPercentage = $achievementData['percentage'];
                
                // Calculate skill level based on playtime and achievement completion
                $skillLevel = $this->determineSkillLevel($playtimeHours, $achievementPercentage);
                
                $skillMetrics[$appId] = [
                    'game_name' => $game['name'],
                    'playtime_hours' => round($playtimeHours, 1),
                    'achievement_percentage' => $achievementPercentage,
                    'skill_level' => $skillLevel,
                    'skill_score' => $this->calculateSkillScore($playtimeHours, $achievementPercentage),
                    'competency_level' => $this->getCompetencyLevel($playtimeHours, $achievementPercentage),
                ];
            }
        }

        return $skillMetrics;
    }

    /**
     * Determine skill level based on playtime and achievements
     */
    protected function determineSkillLevel($playtimeHours, $achievementPercentage)
    {
        // Beginner: < 50 hours, < 25% achievements
        if ($playtimeHours < 50 && $achievementPercentage < 25) {
            return 'beginner';
        }
        
        // Intermediate: 50-200 hours, 25-60% achievements
        if ($playtimeHours < 200 && $achievementPercentage < 60) {
            return 'intermediate';
        }
        
        // Advanced: 200-500 hours, 60-85% achievements
        if ($playtimeHours < 500 && $achievementPercentage < 85) {
            return 'advanced';
        }
        
        // Expert: 500+ hours, 85%+ achievements
        return 'expert';
    }

    /**
     * Calculate numerical skill score (0-100)
     */
    protected function calculateSkillScore($playtimeHours, $achievementPercentage)
    {
        // Normalize playtime (cap at 1000 hours for scoring)
        $playtimeScore = min($playtimeHours / 1000 * 60, 60);
        
        // Achievement score (40% of total)
        $achievementScore = $achievementPercentage * 0.4;
        
        return round($playtimeScore + $achievementScore, 1);
    }

    /**
     * Get competency level description
     */
    protected function getCompetencyLevel($playtimeHours, $achievementPercentage)
    {
        $score = $this->calculateSkillScore($playtimeHours, $achievementPercentage);
        
        if ($score >= 80) return 'Master';
        if ($score >= 65) return 'Veteran';
        if ($score >= 45) return 'Experienced';
        if ($score >= 25) return 'Casual';
        return 'Newcomer';
    }

    /**
     * Analyze gaming schedule patterns using real session data (Phase 2)
     */
    protected function analyzeGamingSchedule($steamId)
    {
        try {
            // Find user by Steam ID
            $user = User::where('steam_id', $steamId)->first();
            
            if (!$user) {
                return [
                    'peak_hours' => [],
                    'peak_days' => [],
                    'average_session_length' => 0,
                    'timezone' => 'UTC',
                    'activity_pattern' => 'unknown',
                    'last_analyzed' => now()->toDateTimeString(),
                ];
            }

            // Use GamingSessionService to analyze schedule
            return $this->gamingSessionService->analyzeGamingSchedule($user, 30);

        } catch (\Exception $e) {
            Log::error("Failed to analyze gaming schedule for Steam ID $steamId: " . $e->getMessage());
            
            return [
                'peak_hours' => [],
                'peak_days' => [],
                'average_session_length' => 0,
                'timezone' => 'UTC',
                'activity_pattern' => 'unknown',
                'last_analyzed' => now()->toDateTimeString(),
            ];
        }
    }
}