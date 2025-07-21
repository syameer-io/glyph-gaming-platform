<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\Server;
use App\Models\MatchmakingRequest;
use App\Services\MatchmakingService;
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

    public function __construct(MatchmakingService $matchmakingService)
    {
        $this->matchmakingService = $matchmakingService;
    }

    /**
     * Display the matchmaking dashboard
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Get user's active matchmaking requests
        $activeRequests = MatchmakingRequest::where('user_id', $user->id)
            ->active()
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

        return view('matchmaking.index', [
            'matchmakingRequests' => $activeRequests,
            'teams' => $availableTeams,
            'recentMatches' => $recentMatches,
            'suggestions' => $suggestions
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
     * Join a team
     */
    public function joinTeam(Request $request, Team $team): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if team is still recruiting
            if (!$team->isRecruiting()) {
                return response()->json([
                    'error' => 'This team is not currently recruiting members.'
                ], 409);
            }

            // Check if team is full
            if ($team->isFull()) {
                return response()->json([
                    'error' => 'This team is already full.'
                ], 409);
            }

            // Check if user is already a member
            if ($team->users()->where('users.id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'You are already a member of this team.'
                ], 409);
            }

            // Check if user is already in another team for this game
            $existingTeam = $user->teams()
                ->where('game_appid', $team->game_appid)
                ->whereIn('teams.status', ['recruiting', 'full', 'active'])
                ->first();

            if ($existingTeam) {
                return response()->json([
                    'error' => 'You are already in a team for this game.'
                ], 409);
            }

            // Calculate user's skill score
            $steamData = [];
            if ($user->profile && $user->profile->steam_data) {
                $steamData = $user->profile->steam_data;
            }
            $skillMetrics = $steamData['skill_metrics'] ?? [];
            $skillScore = $skillMetrics[$team->game_appid]['skill_score'] ?? 50;

            // Add user to team
            $addMemberResult = $team->addMember($user, [
                'role' => 'member',
                'skill_level' => $this->getSkillLevel($skillScore),
                'individual_skill_score' => $skillScore,
            ]);

            if (!$addMemberResult) {
                return response()->json([
                    'error' => 'Failed to join the team. Please try again.'
                ], 500);
            }

            // Cancel any active matchmaking requests for this game
            MatchmakingRequest::where('user_id', $user->id)
                ->where('game_appid', $team->game_appid)
                ->active()
                ->update(['status' => 'matched']);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the team!',
                'team' => $team->fresh()->load(['activeMembers.user', 'server', 'creator'])
            ]);
        
        } catch (\Exception $e) {
            \Log::error('Team join error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'team_id' => $team->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'An error occurred while joining the team: ' . $e->getMessage()
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
