<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\Server;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use App\Services\TeamService;
use App\Events\MatchmakingRequestCreated;
use App\Events\MatchFound;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MatchmakingController extends Controller
{
    protected MatchmakingService $matchmakingService;
    protected TeamService $teamService;

    public function __construct(
        MatchmakingService $matchmakingService,
        TeamService $teamService
    ) {
        $this->matchmakingService = $matchmakingService;
        $this->teamService = $teamService;
    }

    /**
     * Display the matchmaking dashboard
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get user's active matchmaking requests with user relationship
        $activeRequests = MatchmakingRequest::where('user_id', $user->id)
            ->active()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available teams for matchmaking (recruiting teams)
        $availableTeams = Team::where('status', 'recruiting')
            ->where('current_size', '<', DB::raw('max_size'))
            ->with(['server', 'creator', 'activeMembers.user'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        // Get recent matchmaking activity
        $recentMatches = Team::whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['server', 'creator'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get suggested teammates for user's preferred games
        $suggestions = [];
        $userPreferences = $user->gamingPreferences()
            ->where('preference_level', '>=', 50)
            ->orderBy('preference_level', 'desc')
            ->take(3)
            ->get();

        foreach ($userPreferences as $preference) {
            $teammates = $this->matchmakingService->findTeammates($user, [
                'game_appid' => $preference->game_appid,
                'max_results' => 5
            ]);

            if ($teammates->isNotEmpty()) {
                $suggestions[$preference->game_name] = $teammates;
            }
        }

        // Build team recommendations with real compatibility scores for each active request
        $recommendations = [];

        foreach ($activeRequests as $matchmakingRequest) {
            try {
                // Find compatible teams for this request using MatchmakingService
                $compatibleTeams = $this->matchmakingService->findCompatibleTeams($matchmakingRequest);

                // Limit to top 3 most compatible teams per request
                $topTeams = $compatibleTeams->take(3);

                $requestRecommendations = [];

                foreach ($topTeams as $team) {
                    try {
                        // Calculate detailed compatibility breakdown
                        $compatibility = $this->matchmakingService->calculateDetailedCompatibility(
                            $team,
                            $matchmakingRequest
                        );

                        // Get team's needed roles
                        $roleNeeds = $team->getNeededRoles();
                        $roleNeedsList = [];
                        foreach ($roleNeeds as $role => $count) {
                            $roleNeedsList[] = ucfirst(str_replace('_', ' ', $role));
                        }

                        // Build recommendation data structure
                        $requestRecommendations[] = [
                            'team' => $team, // Full team model with eager-loaded relations
                            'compatibility_score' => $compatibility['total_score'],
                            'match_reasons' => $compatibility['reasons'],
                            'role_needs' => $roleNeedsList,
                            'breakdown' => $compatibility['breakdown']
                        ];

                    } catch (\Exception $e) {
                        // Log error for this specific team but continue with others
                        \Log::error('Error calculating team compatibility in index: ' . $e->getMessage(), [
                            'team_id' => $team->id,
                            'request_id' => $matchmakingRequest->id,
                            'user_id' => $user->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                        continue;
                    }
                }

                // Store recommendations keyed by request ID
                $recommendations[$matchmakingRequest->id] = $requestRecommendations;

            } catch (\Exception $e) {
                // Log error for this request but continue with other requests
                \Log::error('Error finding compatible teams in index: ' . $e->getMessage(), [
                    'request_id' => $matchmakingRequest->id,
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);

                // Set empty array for this request to avoid view errors
                $recommendations[$matchmakingRequest->id] = [];
            }
        }

        return view('matchmaking.index', [
            'matchmakingRequests' => $activeRequests,
            'teams' => $availableTeams,
            'recentMatches' => $recentMatches,
            'suggestions' => $suggestions,
            'recommendations' => $recommendations // New: team recommendations with compatibility scores
        ]);
    }

    /**
     * Store a new matchmaking request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'game_appid' => 'required|string',
                'game_name' => 'required|string|max:255',
                'request_type' => 'required|in:find_teammates,find_team,substitute',
                'preferred_roles' => 'array',
                'preferred_roles.*' => 'string|max:50',
                'message' => 'nullable|string|max:500',
                'skill_level' => 'nullable|in:any,beginner,intermediate,advanced,expert',
                'priority' => 'nullable|in:low,normal,high,urgent',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = Auth::user();

            // Check if user already has an active request for this game
            $existingRequest = MatchmakingRequest::where('user_id', $user->id)
                ->where('game_appid', $request->game_appid)
                ->active()
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'error' => 'You already have an active matchmaking request for this game.'
                ], 409);
            }

            // Get user's skill score for the game
            $steamData = [];
            if ($user->profile && $user->profile->steam_data) {
                $steamData = $user->profile->steam_data;
            }
            $skillMetrics = $steamData['skill_metrics'] ?? [];
            $skillScore = $skillMetrics[$request->game_appid]['skill_score'] ?? 50;

            $matchmakingRequest = MatchmakingRequest::create([
                'user_id' => $user->id,
                'game_appid' => $request->game_appid,
                'game_name' => $request->game_name,
                'request_type' => $request->request_type,
                'preferred_roles' => $request->preferred_roles ?? [],
                'skill_level' => $request->skill_level ?? 'any',
                'skill_score' => $skillScore,
                'priority' => $request->priority ?? 'normal',
                'status' => 'active',
                'description' => $request->message,
                'expires_at' => now()->addDays(7), // Request expires in 7 days
                'last_activity_at' => now(),
            ]);

            // Trigger matchmaking request created event (broadcasting disabled for now)
            event(new MatchmakingRequestCreated($matchmakingRequest->load('user')));

            // Find compatible teams for the user based on their request
            $criteria = [
                'game_appid' => $matchmakingRequest->game_appid,
                'max_results' => 5
            ];
            $compatibleTeams = $this->matchmakingService->findTeams($user, $criteria);
            
            // Trigger match found events for available teams (simplified for now)
            foreach ($compatibleTeams->take(3) as $teamMatch) {
                $team = $teamMatch['team'] ?? $teamMatch; // Handle different response formats
                if ($team) {
                    event(new MatchFound($team->load('server'), $matchmakingRequest, 85)); // Default high compatibility
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Matchmaking request created successfully!',
                'request' => $matchmakingRequest,
                'compatible_teams_found' => $compatibleTeams->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Matchmaking request creation error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error creating matchmaking request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find compatible teammates
     */
    public function findTeammates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_appid' => 'required|string',
            'preferred_roles' => 'array',
            'skill_range' => 'integer|min:5|max:50',
            'max_results' => 'integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $criteria = $request->only(['game_appid', 'preferred_roles', 'skill_range', 'max_results']);

        $teammates = $this->matchmakingService->findTeammates($user, $criteria);

        return response()->json([
            'success' => true,
            'teammates' => $teammates->map(function ($match) {
                return [
                    'user' => [
                        'id' => $match['user']->id,
                        'name' => $match['user']->name,
                        'avatar_url' => $match['user']->avatar_url,
                        'profile' => $match['user']->profile,
                    ],
                    'compatibility_score' => $match['compatibility_score'],
                    'skill_difference' => $match['skill_difference'],
                    'role_compatibility' => $match['role_compatibility'],
                    'request_id' => $match['request']->id,
                ];
            })
        ]);
    }

    /**
     * Find compatible teams to join
     */
    public function findTeams(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_appid' => 'required|string',
            'server_id' => 'nullable|exists:servers,id',
            'preferred_roles' => 'array',
            'max_results' => 'integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $criteria = $request->only(['game_appid', 'server_id', 'preferred_roles', 'max_results']);

        $teams = $this->matchmakingService->findTeams($user, $criteria);

        return response()->json([
            'success' => true,
            'teams' => $teams->map(function ($match) {
                return [
                    'team' => [
                        'id' => $match['team']->id,
                        'name' => $match['team']->name,
                        'description' => $match['team']->description,
                        'current_size' => $match['team']->current_size,
                        'max_size' => $match['team']->max_size,
                        'skill_level' => $match['team']->skill_level,
                        'creator' => $match['team']->creator,
                        'server' => $match['team']->server,
                    ],
                    'compatibility_score' => $match['compatibility_score'],
                    'skill_match' => $match['skill_match'],
                    'role_needs' => $match['role_needs'],
                    'balance_score' => $match['balance_score'],
                ];
            })
        ]);
    }

    /**
     * Join a team via matchmaking (from matchmaking recommendations)
     *
     * This method is called when a user joins a team from the matchmaking page.
     * It uses the TeamService and automatically marks the matchmaking request as matched.
     */
    public function joinTeam(Request $request, Team $team): JsonResponse
    {
        try {
            $user = Auth::user();

            \Log::info('MatchmakingController::joinTeam called', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'game_appid' => $team->game_appid,
            ]);

            // Find active matchmaking request for this game
            $matchmakingRequest = MatchmakingRequest::where('user_id', $user->id)
                ->where('game_appid', $team->game_appid)
                ->active()
                ->first();

            \Log::info('MatchmakingController::joinTeam - Matchmaking request found', [
                'matchmaking_request_id' => $matchmakingRequest?->id ?? 'none',
                'has_request' => $matchmakingRequest !== null,
            ]);

            // Use TeamService for shared validation and logic
            // Pass matchmaking request for auto-marking as matched
            $result = $this->teamService->addMemberToTeam(
                $team,
                $user,
                [], // No custom member data for matchmaking joins
                $matchmakingRequest // Pass request to mark as matched
            );

            if ($result['success']) {
                \Log::info('MatchmakingController::joinTeam - Successfully joined team', [
                    'team_member_id' => $result['member']->id ?? null,
                    'matchmaking_request_fulfilled' => $matchmakingRequest !== null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'team' => $team->fresh()->load(['activeMembers.user', 'server', 'creator']),
                    'matchmaking_request_fulfilled' => $matchmakingRequest !== null,
                ]);
            }

            \Log::warning('MatchmakingController::joinTeam - Failed to join team', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'error' => $result['message'],
            ]);

            return response()->json([
                'success' => false,
                'error' => $result['message']
            ], 409);

        } catch (\Exception $e) {
            \Log::error('MatchmakingController::joinTeam - Exception occurred', [
                'user_id' => auth()->id(),
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while joining the team. Please try again later.'
            ], 500);
        }
    }

    /**
     * Auto-match players
     */
    public function autoMatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_appid' => 'required|string',
            'max_teams' => 'integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gameAppId = $request->game_appid;
        $maxTeams = $request->max_teams ?? 5;

        $formedTeams = $this->matchmakingService->autoMatch($gameAppId, $maxTeams);

        return response()->json([
            'success' => true,
            'message' => "Successfully formed {$formedTeams->count()} teams!",
            'teams' => $formedTeams->map(function ($teamData) {
                return [
                    'team' => $teamData['team']->load(['members.user', 'server', 'creator']),
                    'users_matched' => $teamData['users_matched'],
                    'average_compatibility' => round($teamData['average_compatibility'], 1),
                ];
            })
        ]);
    }

    /**
     * Cancel a matchmaking request
     */
    public function cancelRequest(MatchmakingRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($request->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Matchmaking request cancelled successfully.'
        ]);
    }

    /**
     * Get matchmaking statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'active_requests' => MatchmakingRequest::where('user_id', $user->id)
                ->active()
                ->count(),
            'teams_joined' => $user->teams()->count(),
            'successful_matches' => MatchmakingRequest::where('user_id', $user->id)
                ->where('status', 'matched')
                ->count(),
            'average_wait_time' => MatchmakingRequest::where('user_id', $user->id)
                ->where('status', 'matched')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_wait')
                ->value('avg_wait') ?? 0,
        ];

        // Recent activity
        $recentActivity = MatchmakingRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($request) {
                return [
                    'game_name' => $request->game_name,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * Get available games for matchmaking
     */
    public function availableGames(): JsonResponse
    {
        $supportedGames = [
            '730' => 'Counter-Strike 2',
            '570' => 'Dota 2',
            '230410' => 'Warframe',
            '1172470' => 'Apex Legends',
            '252490' => 'Rust',
            '578080' => 'PUBG',
            '359550' => 'Rainbow Six Siege',
            '433850' => 'Fall Guys',
        ];

        // Get active matchmaking requests count per game
        $activeRequests = MatchmakingRequest::active()
            ->selectRaw('game_appid, COUNT(*) as request_count')
            ->groupBy('game_appid')
            ->pluck('request_count', 'game_appid');

        // Get active teams count per game
        $activeTeams = Team::recruiting()
            ->selectRaw('game_appid, COUNT(*) as team_count')
            ->groupBy('game_appid')
            ->pluck('team_count', 'game_appid');

        $games = collect($supportedGames)->map(function ($name, $appId) use ($activeRequests, $activeTeams) {
            return [
                'app_id' => $appId,
                'name' => $name,
                'active_requests' => $activeRequests->get($appId, 0),
                'recruiting_teams' => $activeTeams->get($appId, 0),
                'popularity' => ($activeRequests->get($appId, 0) + $activeTeams->get($appId, 0)),
            ];
        })->sortByDesc('popularity')->values();

        return response()->json([
            'success' => true,
            'games' => $games
        ]);
    }

    /**
     * Get active matchmaking requests for authenticated user
     */
    public function getActiveRequests(): JsonResponse
    {
        try {
            $user = Auth::user();

            $activeRequests = MatchmakingRequest::where('user_id', $user->id)
                ->active()
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
                'requests' => $activeRequests
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
    public function findCompatibleTeamsForRequest(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:matchmaking_requests,id',
                'live_update' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $requestId = $request->input('request_id');
            $liveUpdate = $request->input('live_update', false);

            // Fetch the matchmaking request
            $matchmakingRequest = MatchmakingRequest::find($requestId);

            // Verify user owns the request
            if ($matchmakingRequest->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized - You do not own this request.'
                ], 403);
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

            // Use MatchmakingService to find compatible teams
            $compatibleTeams = $this->matchmakingService->findCompatibleTeams($matchmakingRequest);

            // Build response with detailed compatibility data
            $formattedTeams = $compatibleTeams->map(function ($team) use ($matchmakingRequest) {
                // Get detailed compatibility breakdown
                $compatibility = $this->matchmakingService->calculateDetailedCompatibility($team, $matchmakingRequest);

                // Get needed roles
                $neededRoles = $team->getNeededRoles();
                $roleNeedsList = [];
                foreach ($neededRoles as $role => $count) {
                    $roleNeedsList[] = ucfirst(str_replace('_', ' ', $role));
                }

                // Get team members with avatars
                $members = $team->activeMembers->map(function ($member) {
                    return [
                        'avatar_url' => $member->user->avatar_url ?? '/images/default-avatar.png',
                        'display_name' => $member->user->name ?? $member->user->email,
                    ];
                })->take(5)->toArray();

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'game_name' => $team->game_name,
                    'game_appid' => $team->game_appid,
                    'skill_level' => $team->skill_level,
                    'current_size' => $team->current_size,
                    'max_size' => $team->max_size,
                    'status' => $team->status,
                    'compatibility_score' => $compatibility['total_score'],
                    'match_reasons' => $compatibility['reasons'],
                    'role_needs' => $roleNeedsList,
                    'server' => [
                        'id' => $team->server->id,
                        'name' => $team->server->name,
                    ],
                    'creator' => [
                        'id' => $team->creator->id,
                        'display_name' => $team->creator->name ?? $team->creator->email,
                    ],
                    'members' => $members,
                ];
            })->toArray();

            // Cache the results for 30 seconds
            \Cache::put($cacheKey, $formattedTeams, now()->addSeconds(30));

            return response()->json([
                'success' => true,
                'teams' => $formattedTeams,
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
     * Get skill level string from score
     */
    private function getSkillLevel(float $skillScore): string
    {
        return match(true) {
            $skillScore >= 80 => 'expert',
            $skillScore >= 60 => 'advanced',
            $skillScore >= 40 => 'intermediate',
            default => 'beginner'
        };
    }
}
