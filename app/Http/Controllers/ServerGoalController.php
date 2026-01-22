<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerGoal;
use App\Models\GoalParticipant;
use App\Models\GoalMilestone;
use App\Services\ServerGoalService;
use App\Events\GoalCreated;
use App\Events\GoalProgressUpdated;
use App\Events\GoalMilestoneReached;
use App\Events\GoalCompleted;
use App\Events\UserJoinedGoal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class ServerGoalController extends Controller
{
    protected ServerGoalService $goalService;

    public function __construct(ServerGoalService $goalService)
    {
        $this->goalService = $goalService;
    }

    /**
     * Display server goals management page
     */
    public function index(Server $server): View
    {
        Gate::authorize('admin', $server);

        $goals = $server->goals()
            ->with(['creator', 'activeParticipants.user', 'milestones'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = $this->goalService->getServerGoalStatistics($server);
        $recommendations = $this->goalService->getGoalRecommendations($server);

        return view('admin.goals.index', compact('server', 'goals', 'stats', 'recommendations'));
    }

    /**
     * Show form for creating a new goal
     */
    public function create(Server $server): View
    {
        Gate::authorize('admin', $server);

        // Get server members' gaming data for recommendations
        $memberGames = $server->members()
            ->with('gamingPreferences')
            ->get()
            ->flatMap->gamingPreferences
            ->groupBy('game_appid')
            ->map(function ($preferences) {
                return [
                    'game_name' => $preferences->first()->game_name,
                    'player_count' => $preferences->count(),
                    'avg_playtime' => $preferences->avg('playtime_forever'),
                ];
            })
            ->sortByDesc('player_count')
            ->take(10);

        return view('admin.goals.create', compact('server', 'memberGames'));
    }

    /**
     * Store a new server goal
     */
    public function store(Request $request, Server $server): JsonResponse
    {
        Gate::authorize('admin', $server);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'game_appid' => 'nullable|string',
            'game_name' => 'nullable|string|max:255',
            'goal_type' => 'required|in:achievement,playtime,participation,community,custom',
            'target_criteria' => 'present', // Must be present but can be empty
            'target_value' => 'required|integer|min:1',
            'difficulty' => 'required|in:easy,medium,hard,extreme',
            'visibility' => 'required|in:public,members_only,private',
            'start_date' => 'nullable|date|after_or_equal:today',
            'deadline' => 'nullable|date|after:start_date',
            'rewards' => 'nullable|array',
            'goal_settings' => 'nullable|array',
            'milestones' => 'nullable|array',
            'milestones.*.milestone_name' => 'required_if:milestones,!=,null|string|max:255',
            'milestones.*.progress_required' => 'required_if:milestones,!=,null|integer|min:1',
            'milestones.*.percentage_required' => 'required_if:milestones,!=,null|numeric|min:0|max:100',
            'milestones.*.reward_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        try {
            $goal = $this->goalService->createGoal($server, $user, $request->all());

            // Fire the GoalCreated event for Telegram notifications and broadcasting
            event(new GoalCreated($goal->load(['creator', 'server'])));

            return response()->json([
                'success' => true,
                'message' => 'Goal created successfully!',
                'goal' => $goal->load(['creator', 'milestones'])
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create goal. Please try again.'], 500);
        }
    }

    /**
     * Display a specific goal
     */
    public function show(Server $server, ServerGoal $goal): View
    {
        Gate::authorize('view', $server);

        if ($goal->server_id !== $server->id) {
            abort(404);
        }

        $goal->load([
            'creator',
            'participants.user',
            'milestones' => function ($query) {
                $query->orderBy('order');
            }
        ]);

        // Get goal leaderboard
        $leaderboard = $this->goalService->getGoalLeaderboard($goal, 20);

        // Get participation statistics
        $participationStats = [
            'total_participants' => $goal->participant_count,
            'active_participants' => $goal->activeParticipants()->count(),
            'participation_rate' => $server->members()->count() > 0 
                ? ($goal->participant_count / $server->members()->count()) * 100 
                : 0,
            'average_progress' => $goal->activeParticipants()->avg('individual_progress') ?? 0,
        ];

        return view('goals.show', compact('server', 'goal', 'leaderboard', 'participationStats'));
    }

    /**
     * Show form for editing a goal
     */
    public function edit(Server $server, ServerGoal $goal): View
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            abort(404);
        }

        return view('admin.goals.edit', compact('server', 'goal'));
    }

    /**
     * Update a server goal
     */
    public function update(Request $request, Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'difficulty' => 'required|in:easy,medium,hard,extreme',
            'visibility' => 'required|in:public,members_only,private',
            'deadline' => 'nullable|date|after:start_date',
            'rewards' => 'array',
            'goal_settings' => 'array',
            'status' => 'nullable|in:draft,active,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Don't allow changing target_value or criteria for active goals with participants
        if ($goal->status === 'active' && $goal->participant_count > 0) {
            $restrictedFields = ['target_value', 'target_criteria', 'goal_type'];
            foreach ($restrictedFields as $field) {
                if ($request->has($field)) {
                    return response()->json([
                        'error' => 'Cannot modify goal criteria after participants have joined.'
                    ], 422);
                }
            }
        }

        $goal->update($request->only([
            'title', 'description', 'difficulty', 'visibility', 
            'deadline', 'rewards', 'goal_settings', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Goal updated successfully!',
            'goal' => $goal->fresh()
        ]);
    }

    /**
     * Delete a server goal
     */
    public function destroy(Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        // Only allow deletion if goal is not active or has no participants
        if ($goal->status === 'active' && $goal->participant_count > 0) {
            return response()->json([
                'error' => 'Cannot delete active goal with participants. Cancel the goal first.'
            ], 409);
        }

        try {
            // Delete related records
            $goal->participants()->delete();
            $goal->milestones()->delete();
            $goal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Goal deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete goal.'], 500);
        }
    }

    /**
     * Join a goal
     */
    public function join(Request $request, Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('view', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $user = Auth::user();

        $success = $this->goalService->joinGoal($goal, $user);

        if (!$success) {
            return response()->json(['error' => 'Unable to join goal. Check requirements.'], 409);
        }

        // Get the participant record and broadcast event
        $participant = $goal->participants()->where('user_id', $user->id)->first();
        if ($participant) {
            event(new UserJoinedGoal($goal->fresh(), $participant->load('user')));
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined the goal!',
            'goal' => $goal->fresh()->load(['activeParticipants.user'])
        ]);
    }

    /**
     * Leave a goal
     */
    public function leave(Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('view', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $user = Auth::user();
        $participant = $goal->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return response()->json(['error' => 'You are not participating in this goal'], 404);
        }

        $participant->update(['participation_status' => 'dropped']);
        $goal->decrement('participant_count');

        return response()->json([
            'success' => true,
            'message' => 'Left the goal successfully.'
        ]);
    }

    /**
     * Update user's own progress in a goal
     */
    public function updateUserProgress(Request $request, Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('view', $server);
        
        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $user = Auth::user();
        $participant = $goal->participants()->where('user_id', $user->id)->where('participation_status', 'active')->first();
        
        if (!$participant) {
            return response()->json(['error' => 'You are not participating in this goal'], 404);
        }

        $validator = Validator::make($request->all(), [
            'progress' => 'required|integer|min:0|max:' . ($goal->target_value * 2), // Allow some buffer over target
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Update participant's progress
            $participant->updateProgress($request->progress);

            // Get updated goal data
            $goal = $goal->fresh();
            
            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully!',
                'goal' => [
                    'id' => $goal->id,
                    'current_progress' => $goal->current_progress,
                    'target_value' => $goal->target_value,
                    'completion_percentage' => $goal->completion_percentage,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating user progress: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update progress: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update goal progress manually
     */
    public function updateProgress(Request $request, Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'progress' => 'required|integer|min:0',
            'progress_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = \App\Models\User::find($request->user_id);
        $success = $this->goalService->updateUserProgress(
            $goal, 
            $user, 
            $request->progress,
            $request->progress_data ?? []
        );

        if (!$success) {
            return response()->json(['error' => 'Failed to update progress'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully!',
            'goal' => $goal->fresh()
        ]);
    }

    /**
     * Get goal statistics
     */
    public function stats(Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('view', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $leaderboard = $this->goalService->getGoalLeaderboard($goal, 10);
        
        $stats = [
            'goal_info' => [
                'title' => $goal->title,
                'progress' => $goal->completion_percentage,
                'status' => $goal->status,
                'days_remaining' => $goal->getDaysRemaining(),
            ],
            'participation' => [
                'total_participants' => $goal->participant_count,
                'active_participants' => $goal->activeParticipants()->count(),
                'participation_rate' => $server->members()->count() > 0 
                    ? round(($goal->participant_count / $server->members()->count()) * 100, 1)
                    : 0,
            ],
            'progress_stats' => [
                'current_progress' => $goal->current_progress,
                'target_value' => $goal->target_value,
                'average_individual_progress' => round($goal->activeParticipants()->avg('individual_progress') ?? 0, 1),
                'top_contributor' => $leaderboard->first(),
            ],
            'milestones' => [
                'total' => $goal->milestones()->count(),
                'achieved' => $goal->milestones()->achieved()->count(),
                'next_milestone' => $goal->getNextMilestone(),
            ],
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'leaderboard' => $leaderboard
        ]);
    }

    /**
     * Get goal recommendations for the server
     */
    public function recommendations(Server $server): JsonResponse
    {
        Gate::authorize('admin', $server);

        $recommendations = $this->goalService->getGoalRecommendations($server);

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations
        ]);
    }

    /**
     * Process expired goals
     */
    public function processExpired(Server $server): JsonResponse
    {
        Gate::authorize('admin', $server);

        $processed = $this->goalService->processExpiredGoals();

        return response()->json([
            'success' => true,
            'message' => 'Processed expired goals successfully!',
            'processed' => $processed
        ]);
    }

    /**
     * Sync goal progress from Steam data
     */
    public function syncProgress(Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $updatedCount = 0;
        $participants = $goal->activeParticipants()->with('user')->get();

        foreach ($participants as $participant) {
            $updatedGoals = $this->goalService->updateProgressFromSteamData($participant->user);
            if (in_array($goal->id, $updatedGoals->pluck('id')->toArray())) {
                $updatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Updated progress for {$updatedCount} participants from Steam data!",
            'goal' => $goal->fresh()
        ]);
    }

    /**
     * Export goal data
     */
    public function export(Server $server, ServerGoal $goal): JsonResponse
    {
        Gate::authorize('admin', $server);

        if ($goal->server_id !== $server->id) {
            return response()->json(['error' => 'Goal not found'], 404);
        }

        $exportData = [
            'goal' => $goal->toArray(),
            'participants' => $goal->participants()->with('user')->get()->map(function ($participant) {
                return [
                    'user_name' => $participant->user->name,
                    'progress' => $participant->individual_progress,
                    'contribution' => $participant->contribution_percentage,
                    'joined_at' => $participant->joined_at,
                    'last_activity' => $participant->last_activity_at,
                ];
            }),
            'milestones' => $goal->milestones()->get(),
            'leaderboard' => $this->goalService->getGoalLeaderboard($goal, 50),
            'exported_at' => now(),
        ];

        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }
}
