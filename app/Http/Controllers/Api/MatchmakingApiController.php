<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchmakingRequest;
use App\Models\Team;
use App\Services\MatchmakingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MatchmakingApiController extends Controller
{
    protected MatchmakingService $matchmakingService;

    public function __construct(MatchmakingService $matchmakingService)
    {
        $this->middleware('auth');
        $this->matchmakingService = $matchmakingService;
    }

    /**
     * Get user's active matchmaking requests
     */
    public function getActiveRequests(): JsonResponse
    {
        try {
            $user = Auth::user();

            $requests = $user->activeMatchmakingRequests()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'game_appid' => $request->game_appid,
                        'game_name' => $request->game_name,
                        'request_type' => $request->request_type,
                        'preferred_roles' => $request->preferred_roles ?? [],
                        'skill_level' => $request->skill_level,
                        'skill_score' => $request->skill_score,
                        'status' => $request->status,
                        'created_at' => $request->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'requests' => $requests
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching active matchmaking requests: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error fetching active requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find compatible teams for a matchmaking request
     */
    public function findCompatibleTeams(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'request_id' => 'required|exists:matchmaking_requests,id',
                'live_update' => 'boolean'
            ]);

            $requestId = $request->input('request_id');
            $liveUpdate = $request->input('live_update', false);

            $matchmakingRequest = MatchmakingRequest::where('id', $requestId)
                ->where('user_id', $user->id)
                ->first();

            if (!$matchmakingRequest) {
                return response()->json([
                    'success' => false,
                    'error' => 'Matchmaking request not found or unauthorized'
                ], 404);
            }

            // Check cache if not a live update
            $cacheKey = "compatible_teams_request_{$requestId}";

            if (!$liveUpdate && \Cache::has($cacheKey)) {
                $cachedTeams = \Cache::get($cacheKey);

                return response()->json([
                    'success' => true,
                    'teams' => $cachedTeams,
                    'cached' => true
                ]);
            }

            // Find compatible teams using MatchmakingService
            $compatibleTeams = $this->matchmakingService->findCompatibleTeams($matchmakingRequest);

            // Calculate detailed compatibility scores
            $teamsWithDetails = $compatibleTeams->map(function ($team) use ($matchmakingRequest) {
                $compatibility = $this->matchmakingService->calculateDetailedCompatibility($team, $matchmakingRequest);

                // Get needed roles
                $neededRoles = $team->getNeededRoles();
                $roleNeedsList = [];
                foreach ($neededRoles as $role => $count) {
                    $roleNeedsList[] = ucfirst(str_replace('_', ' ', $role));
                }

                // Language code to name mapping
                $languageMap = [
                    'en' => 'English', 'es' => 'Spanish', 'zh' => 'Chinese',
                    'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese',
                    'ru' => 'Russian', 'ja' => 'Japanese', 'ko' => 'Korean'
                ];

                // Map language codes to names
                $languages = [];
                if (!empty($team->languages) && is_array($team->languages)) {
                    foreach ($team->languages as $langCode) {
                        $languages[] = $languageMap[$langCode] ?? ucfirst($langCode);
                    }
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'game_name' => $team->game_name,
                    'game_appid' => $team->game_appid,
                    'skill_level' => $team->skill_level,
                    'current_size' => $team->current_size,
                    'max_size' => $team->max_size,
                    'status' => $team->status,
                    'recruitment_status' => $team->recruitment_status ?? 'closed',
                    'compatibility_score' => $compatibility['total_score'],
                    'compatibility_breakdown' => $compatibility['breakdown'] ?? [],
                    'match_reasons' => $compatibility['reasons'],
                    'role_needs' => $roleNeedsList,
                    // Team tags for detailed display
                    'preferred_region' => $team->preferred_region,
                    'required_roles' => $team->required_roles ?? [],
                    'activity_times' => $team->activity_times ?? ($team->activity_time ? [$team->activity_time] : []),
                    'languages' => $languages,
                    'communication_required' => $team->communication_required ?? false,
                    'server' => [
                        'id' => $team->server->id,
                        'name' => $team->server->name,
                    ],
                    'creator' => [
                        'id' => $team->creator->id,
                        'display_name' => $team->creator->display_name ?? $team->creator->name,
                    ],
                    'members' => $team->activeMembers->take(5)->map(function ($member) {
                        return [
                            'avatar_url' => $member->user->avatar_url,
                            'display_name' => $member->user->display_name ?? $member->user->name ?? 'Member',
                        ];
                    })->toArray(),
                ];
            })->toArray();

            // Cache the results for 30 seconds
            \Cache::put($cacheKey, $teamsWithDetails, now()->addSeconds(30));

            return response()->json([
                'success' => true,
                'teams' => $teamsWithDetails,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            \Log::error('Error finding compatible teams: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_id' => $request->input('request_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error finding compatible teams: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get live team recommendations based on user preferences
     */
    public function getLiveRecommendations(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Get user's gaming preferences
        $gamingPreferences = $user->gamingPreferences()
            ->orderBy('preference_level', 'desc')
            ->take(3)
            ->get();

        if ($gamingPreferences->isEmpty()) {
            return response()->json([
                'success' => true,
                'recommendations' => [],
                'message' => 'No gaming preferences found. Play some games and sync your Steam data to get better recommendations.'
            ]);
        }

        $allRecommendations = [];

        foreach ($gamingPreferences as $preference) {
            // Find teams for this game
            $teams = Team::where('game_appid', $preference->game_appid)
                ->where('status', 'recruiting')
                ->where('current_size', '<', 'max_size')
                ->with(['server', 'activeMembers.user.profile', 'creator'])
                ->get();

            foreach ($teams as $team) {
                // Calculate compatibility based on gaming preference
                $compatibility = $this->calculatePreferenceCompatibility($team, $preference, $user);
                
                if ($compatibility['total_score'] >= 40) { // Minimum 40% compatibility
                    $allRecommendations[] = [
                        'id' => $team->id,
                        'name' => $team->name,
                        'description' => $team->description,
                        'game_appid' => $team->game_appid,
                        'game_name' => $team->game_name,
                        'skill_level' => $team->skill_level,
                        'current_size' => $team->current_size,
                        'max_size' => $team->max_size,
                        'status' => $team->status,
                        'compatibility_score' => $compatibility['total_score'],
                        'match_reasons' => $compatibility['reasons'],
                        'role_needs' => $this->getTeamRoleNeeds($team),
                        'members' => $team->activeMembers->take(5)->map(function ($member) {
                            return [
                                'user_id' => $member->user_id,
                                'display_name' => $member->user->display_name ?? $member->user->name,
                                'avatar_url' => $member->user->avatar_url ?? ($member->user->profile->avatar_url ?? null),
                                'role' => $member->role,
                                'game_role' => $member->game_role,
                            ];
                        }),
                        'preference_match' => $preference->game_name,
                        'based_on' => "You've played {$preference->game_name} for {$preference->playtime_forever} hours",
                    ];
                }
            }
        }

        // Sort by compatibility score and take top 5
        $topRecommendations = collect($allRecommendations)
            ->sortByDesc('compatibility_score')
            ->take(5)
            ->values();

        return response()->json([
            'success' => true,
            'recommendations' => $topRecommendations,
            'total_found' => count($allRecommendations),
            'based_on_preferences' => $gamingPreferences->pluck('game_name')->toArray()
        ]);
    }

    /**
     * Calculate compatibility between team and user gaming preference
     */
    private function calculatePreferenceCompatibility(Team $team, $preference, $user): array
    {
        $reasons = [];
        $scores = [];

        // Game match (40% weight)
        if ($team->game_appid === $preference->game_appid) {
            $scores['game'] = 40;
            $reasons[] = "Matches your favorite game: {$preference->game_name}";
        } else {
            $scores['game'] = 0;
        }

        // Skill level compatibility (25% weight)
        $userSkillLevel = $this->estimateSkillLevel($preference);
        $skillCompatibility = $this->getSkillCompatibility($team->skill_level, $userSkillLevel);
        $scores['skill'] = $skillCompatibility * 25;
        
        if ($skillCompatibility > 0.7) {
            $reasons[] = "Perfect skill level match";
        } else if ($skillCompatibility > 0.5) {
            $reasons[] = "Good skill level compatibility";
        }

        // Team size preference (15% weight)
        $sizeScore = 1 - (abs($team->current_size - 3) / 5); // Prefer teams around 3 members
        $scores['size'] = max(0, $sizeScore * 15);
        
        if ($team->current_size <= 3) {
            $reasons[] = "Small team - easier to get to know everyone";
        }

        // Activity level (10% weight)
        $activityScore = min($preference->playtime_2weeks / 10, 1); // More active = better
        $scores['activity'] = $activityScore * 10;

        // Server membership (10% weight)
        $isMember = $team->server->members()->where('user_id', $user->id)->exists();
        if ($isMember) {
            $scores['server'] = 10;
            $reasons[] = "You're already a member of this server";
        } else {
            $scores['server'] = 5; // Still some points for new communities
        }

        $totalScore = array_sum($scores);

        return [
            'total_score' => round($totalScore, 1),
            'breakdown' => $scores,
            'reasons' => $reasons
        ];
    }

    /**
     * Estimate skill level from gaming preference data
     */
    private function estimateSkillLevel($preference): string
    {
        $playtime = $preference->playtime_forever;
        
        if ($playtime > 500) return 'expert';
        if ($playtime > 200) return 'advanced';
        if ($playtime > 50) return 'intermediate';
        return 'beginner';
    }

    /**
     * Get skill compatibility score between two skill levels
     */
    private function getSkillCompatibility($teamSkill, $userSkill): float
    {
        $skillLevels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        
        $teamLevel = $skillLevels[$teamSkill] ?? 2;
        $userLevel = $skillLevels[$userSkill] ?? 2;
        
        $diff = abs($teamLevel - $userLevel);
        
        if ($diff === 0) return 1.0;
        if ($diff === 1) return 0.8;
        if ($diff === 2) return 0.5;
        return 0.2;
    }

    /**
     * Get team role needs based on current composition
     */
    private function getTeamRoleNeeds(Team $team): array
    {
        $roleNeeds = [];
        
        // Get current roles
        $currentRoles = $team->activeMembers->pluck('game_role')->filter()->toArray();
        
        // Common role needs based on game
        $gameRoles = $this->getGameRoles($team->game_appid);
        
        foreach ($gameRoles as $role) {
            if (!in_array($role, $currentRoles)) {
                $roleNeeds[] = $role;
            }
        }

        return array_slice($roleNeeds, 0, 3); // Return top 3 needed roles
    }

    /**
     * Get common roles for a game
     */
    private function getGameRoles($gameAppId): array
    {
        $gameRoles = [
            '730' => ['entry_fragger', 'support', 'awper', 'igl', 'lurker'], // CS2
            '570' => ['carry', 'mid', 'offlaner', 'support', 'jungler'], // Dota 2
            '440' => ['dps', 'healer', 'tank'], // Team Fortress 2
            // Add more games as needed
        ];

        return $gameRoles[$gameAppId] ?? ['dps', 'support', 'tank'];
    }

    /**
     * Get real-time team updates for live feed
     */
    public function getLiveTeamUpdates(Request $request): JsonResponse
    {
        $request->validate([
            'since' => 'nullable|date',
            'game_appid' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $since = $request->since ? \Carbon\Carbon::parse($request->since) : now()->subMinutes(5);
        $limit = $request->limit ?? 10;

        $query = Team::where('created_at', '>', $since)
            ->orWhere('updated_at', '>', $since)
            ->where('status', 'recruiting')
            ->with(['server', 'creator', 'activeMembers.user.profile']);

        if ($request->game_appid) {
            $query->where('game_appid', $request->game_appid);
        }

        $teams = $query->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get();

        $teamUpdates = $teams->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'game_name' => $team->game_name,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'status' => $team->status,
                'is_new' => $team->created_at->gt(now()->subMinutes(5)),
                'updated_at' => $team->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'updates' => $teamUpdates,
            'since' => $since->toISOString(),
            'next_check' => now()->toISOString()
        ]);
    }
}