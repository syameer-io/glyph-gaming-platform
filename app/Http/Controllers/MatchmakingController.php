<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\Server;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
use App\Services\TeamService;
use App\Events\MatchmakingRequestCreated;
use App\Events\MatchmakingRequestCancelled;
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
            'recentMatches' => $recentMatches,
            'suggestions' => $suggestions,
            'recommendations' => $recommendations // Team recommendations with compatibility scores
        ]);
    }

    /**
     * Get players looking for teams (for team leaders)
     *
     * Returns players with active matchmaking requests for games
     * that match the current user's recruiting teams.
     */
    public function getPlayersLookingForTeams(): JsonResponse
    {
        $user = Auth::user();

        try {
            $players = $this->matchmakingService->findPlayersForTeamLeader($user);

            return response()->json([
                'success' => true,
                'players' => $players->map(function($item) {
                    return [
                        'request_id' => $item['request']->id,
                        'user_id' => $item['user']->id,
                        'username' => $item['user']->username,
                        'display_name' => $item['user']->name,
                        'avatar' => $item['user']->profile->avatar_url ?? null,
                        'game_appid' => $item['request']->game_appid,
                        'game_name' => $item['request']->game_name,
                        'skill_level' => $item['request']->skill_level,
                        'skill_score' => $item['request']->skill_score,
                        'preferred_roles' => $item['request']->preferred_roles ?? [],
                        'preferred_regions' => $item['request']->preferred_regions ?? [],
                        'availability_hours' => $item['request']->availability_hours ?? [],
                        'languages' => $item['request']->languages ?? [],
                        'compatibility_score' => $item['compatibility_score'],
                        'match_reasons' => $item['match_reasons'],
                        'best_team' => [
                            'id' => $item['best_team']->id,
                            'name' => $item['best_team']->name,
                        ],
                        'all_matching_teams' => $item['all_matching_teams']->map(fn($t) => [
                            'id' => $t->id,
                            'name' => $t->name,
                            'member_count' => $t->activeMembers->count(),
                            'max_size' => $t->max_size,
                        ]),
                        'created_at' => $item['request']->created_at->diffForHumans(),
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching players for team leader', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to load players'
            ], 500);
        }
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
                'request_type' => 'nullable|string|in:find_teammates,find_team,substitute', // Optional - bidirectional matching
                'preferred_roles' => 'array',
                'preferred_roles.*' => 'string|max:50',
                'message' => 'nullable|string|max:500',
                'preferred_regions' => 'nullable|array',
                'preferred_regions.*' => 'string|in:NA,EU,ASIA,SA,OCEANIA,AFRICA,MIDDLE_EAST',
                'availability_hours' => 'nullable|array',
                'availability_hours.*' => 'string|in:morning,afternoon,evening,night,flexible',
                'languages' => 'nullable|array',
                'languages.*' => 'string|max:10',
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

            // Calculate skill automatically using SkillCalculationService
            $skillCalculationService = app(\App\Services\SkillCalculationService::class);
            $skillData = $skillCalculationService->calculateSkillForGame($user, $request->game_appid);

            $matchmakingRequest = MatchmakingRequest::create([
                'user_id' => $user->id,
                'game_appid' => $request->game_appid,
                'game_name' => $request->game_name,
                'request_type' => $request->request_type ?? null, // Nullable for bidirectional matching
                'preferred_roles' => $request->preferred_roles ?? [],
                'skill_level' => $skillData['skill_level'],
                'skill_score' => $skillData['skill_score'] ?? 50,
                'status' => 'active',
                'description' => $request->message,
                'expires_at' => now()->addDays(7), // Request expires in 7 days
                'last_activity_at' => now(),
                'preferred_regions' => $request->preferred_regions ?? [],
                'availability_hours' => $request->availability_hours ?? [],
                'languages' => $request->languages ?? ['en'],
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
     * Get user's calculated skill for a specific game (AJAX endpoint)
     * Used to preview skill before creating matchmaking request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkillPreview(Request $request): JsonResponse
    {
        $request->validate([
            'game_appid' => 'required|string',
        ]);

        $user = Auth::user();
        $gameAppId = $request->input('game_appid');

        // Validate game is supported for skill calculation
        $supportedGames = [730, 548430, 493520]; // CS2, Deep Rock Galactic, GTFO
        if (!in_array((int)$gameAppId, $supportedGames)) {
            return response()->json([
                'success' => true,
                'skill_level' => 'unranked',
                'skill_score' => null,
                'breakdown' => ['note' => 'Game not supported for skill calculation'],
                'is_unranked' => true,
            ]);
        }

        try {
            $skillCalculationService = app(\App\Services\SkillCalculationService::class);
            $skillData = $skillCalculationService->calculateSkillForGame($user, $gameAppId);
            $breakdown = $skillCalculationService->getSkillBreakdown($user, $gameAppId);

            return response()->json([
                'success' => true,
                'skill_level' => $skillData['skill_level'],
                'skill_score' => $skillData['skill_score'],
                'breakdown' => $breakdown,
                'is_unranked' => $skillData['skill_level'] === 'unranked',
            ]);
        } catch (\Exception $e) {
            \Log::error('Skill preview error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'game_appid' => $gameAppId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error calculating skill preview',
                'skill_level' => 'unranked',
                'is_unranked' => true,
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

        // Broadcast the cancellation event for real-time updates
        event(new MatchmakingRequestCancelled($request));

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
            '548430' => 'Deep Rock Galactic',
            '493520' => 'GTFO',
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
            $formattedTeams = $compatibleTeams->filter(function ($team) {
                // Only require creator - server is optional for independent teams
                return $team->creator !== null;
            })->map(function ($team) use ($matchmakingRequest) {
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
                    'preferred_region' => $team->preferred_region,
                    'required_roles' => $team->required_roles ?? [],
                    'activity_times' => $team->activity_times ?? [],
                    'languages' => $team->languages ?? [],
                    'server' => $team->server ? [
                        'id' => $team->server->id,
                        'name' => $team->server->name,
                    ] : null,
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

        } catch (\Throwable $e) {
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

    /**
     * Test Phase 1 implementation - Skill compatibility calculations
     * Temporary method to validate the 76% bug fix
     */
    public function testPhase1(): JsonResponse
    {
        try {
            $skillLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
            $results = [];
            $bugFixValidation = [];

            // Test all skill level combinations
            foreach ($skillLevels as $teamSkill) {
                foreach ($skillLevels as $requestSkill) {
                    // Create temporary test team and request
                    $team = new Team([
                        'name' => "Test {$teamSkill} Team",
                        'skill_level' => $teamSkill,
                        'status' => 'recruiting',
                    ]);
                    $team->id = rand(1000, 9999); // Fake ID for testing

                    $request = new MatchmakingRequest([
                        'skill_level' => $requestSkill,
                        'status' => 'active',
                    ]);
                    $request->id = rand(1000, 9999); // Fake ID for testing

                    // Calculate skill compatibility using new algorithm
                    $skillScore = $this->matchmakingService->calculateDetailedCompatibility($team, $request);

                    $results[] = [
                        'team_skill' => strtoupper($teamSkill),
                        'request_skill' => strtoupper($requestSkill),
                        'skill_score' => $skillScore['breakdown']['skill'] ?? 0,
                        'total_score' => $skillScore['total_score'] ?? 0,
                    ];
                }
            }

            // Test the specific bug case: INTERMEDIATE vs EXPERT
            $intermediateTeam = new Team([
                'name' => 'INTERMEDIATE Test Team',
                'skill_level' => 'intermediate',
                'status' => 'recruiting',
            ]);
            $intermediateTeam->id = 8888;

            $expertRequest = new MatchmakingRequest([
                'skill_level' => 'expert',
                'status' => 'active',
            ]);
            $expertRequest->id = 9999;

            $bugTestResult = $this->matchmakingService->calculateDetailedCompatibility($intermediateTeam, $expertRequest);

            $bugFixValidation = [
                'team_skill' => 'INTERMEDIATE',
                'request_skill' => 'EXPERT',
                'skill_score' => $bugTestResult['breakdown']['skill'] ?? 0,
                'total_score' => $bugTestResult['total_score'] ?? 0,
                'expected_skill_score' => '~16.7%',
                'bug_fixed' => ($bugTestResult['breakdown']['skill'] ?? 0) < 20,
                'old_bug_value' => '76%',
            ];

            // Create matrix view for easier reading
            $matrix = [];
            foreach ($skillLevels as $teamSkill) {
                $row = ['team' => strtoupper($teamSkill)];
                foreach ($skillLevels as $requestSkill) {
                    $matchingResult = collect($results)->first(function ($r) use ($teamSkill, $requestSkill) {
                        return strtolower($r['team_skill']) === $teamSkill
                            && strtolower($r['request_skill']) === $requestSkill;
                    });
                    $row[strtoupper($requestSkill)] = $matchingResult['skill_score'] ?? 0;
                }
                $matrix[] = $row;
            }

            return response()->json([
                'success' => true,
                'message' => 'Phase 1 Skill Compatibility Test Results',
                'bug_fix_validation' => $bugFixValidation,
                'full_results' => $results,
                'compatibility_matrix' => $matrix,
                'expected_matrix' => [
                    'BEGINNER' => ['BEGINNER' => 100, 'INTERMEDIATE' => 66.7, 'ADVANCED' => 16.7, 'EXPERT' => 0],
                    'INTERMEDIATE' => ['BEGINNER' => 66.7, 'INTERMEDIATE' => 100, 'ADVANCED' => 66.7, 'EXPERT' => 16.7],
                    'ADVANCED' => ['BEGINNER' => 16.7, 'INTERMEDIATE' => 66.7, 'ADVANCED' => 100, 'EXPERT' => 66.7],
                    'EXPERT' => ['BEGINNER' => 0, 'INTERMEDIATE' => 16.7, 'ADVANCED' => 66.7, 'EXPERT' => 100],
                ],
                'test_timestamp' => now()->toDateTimeString(),
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Phase 1 test error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Test failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
