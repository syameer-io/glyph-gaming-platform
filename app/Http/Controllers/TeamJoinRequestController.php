<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamJoinRequest;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamJoinRequestController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Create a join request for a closed/invite-only team
     */
    public function store(Request $request, Team $team): JsonResponse
    {
        $user = Auth::user();

        Log::info('TeamJoinRequestController::store - Creating join request', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'recruitment_status' => $team->recruitment_status,
        ]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 1. Check if team is full
        if ($team->isFull()) {
            return response()->json([
                'success' => false,
                'error' => 'This team is already full.'
            ], 409);
        }

        // 2. Check if user is already a member
        if ($team->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'You are already a member of this team.'
            ], 409);
        }

        // 3. Check if user already has a pending request for this team
        $existingRequest = TeamJoinRequest::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'error' => 'You already have a pending join request for this team.'
            ], 409);
        }

        // 4. Check if user is in another active team for the same game
        $activeTeamForGame = $user->teams()
            ->where('game_appid', $team->game_appid)
            ->whereIn('teams.status', ['recruiting', 'full', 'active'])
            ->wherePivot('status', 'active')
            ->first();

        if ($activeTeamForGame) {
            return response()->json([
                'success' => false,
                'error' => "You are already a member of '{$activeTeamForGame->name}' for this game. Please leave that team first."
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Create the join request
            $joinRequest = TeamJoinRequest::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'message' => $request->message,
            ]);

            DB::commit();

            Log::info('TeamJoinRequestController::store - Join request created', [
                'join_request_id' => $joinRequest->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Join request sent successfully! The team leader will review your request.',
                'join_request' => $joinRequest->load('user'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamJoinRequestController::store - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create join request. Please try again.'
            ], 500);
        }
    }

    /**
     * Get all pending join requests for a team (leader/co-leader only)
     */
    public function index(Team $team): JsonResponse
    {
        $user = Auth::user();

        // Check authorization - only team leaders/co-leaders can view join requests
        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pendingRequests = $team->pendingJoinRequests()
            ->with('user.profile')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'join_requests' => $pendingRequests,
        ]);
    }

    /**
     * Approve a join request
     */
    public function approve(Team $team, TeamJoinRequest $joinRequest): JsonResponse
    {
        $user = Auth::user();

        Log::info('TeamJoinRequestController::approve - Approving join request', [
            'team_id' => $team->id,
            'join_request_id' => $joinRequest->id,
            'approver_id' => $user->id,
        ]);

        // Check authorization
        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify the join request belongs to this team
        if ($joinRequest->team_id !== $team->id) {
            return response()->json(['error' => 'Join request does not belong to this team'], 400);
        }

        // Check if already processed
        if (!$joinRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => 'This join request has already been processed.'
            ], 409);
        }

        // Check if team is still accepting members
        if ($team->isFull()) {
            return response()->json([
                'success' => false,
                'error' => 'Team is now full. Cannot approve this request.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Approve the request
            $joinRequest->approve($user);

            // Add the user to the team using TeamService
            // IMPORTANT: Pass true for $bypassRecruitmentCheck to allow approved requests
            // to add members even to closed/invite-only teams
            $addResult = $this->teamService->addMemberToTeam(
                $team,
                $joinRequest->user,
                [], // memberData
                null, // matchmakingRequest
                true // bypassRecruitmentCheck - CRITICAL for closed teams
            );

            if (!$addResult['success']) {
                DB::rollBack();

                Log::error('TeamJoinRequestController::approve - Failed to add member', [
                    'team_id' => $team->id,
                    'join_request_id' => $joinRequest->id,
                    'error_message' => $addResult['message'],
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $addResult['message']
                ], 409);
            }

            DB::commit();

            Log::info('TeamJoinRequestController::approve - Request approved and user added', [
                'join_request_id' => $joinRequest->id,
                'team_member' => $addResult['member']->id ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Join request approved! User has been added to the team.',
                'team' => $team->fresh()->load(['activeMembers.user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamJoinRequestController::approve - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to approve join request. Please try again.'
            ], 500);
        }
    }

    /**
     * Reject a join request
     */
    public function reject(Team $team, TeamJoinRequest $joinRequest): JsonResponse
    {
        $user = Auth::user();

        Log::info('TeamJoinRequestController::reject - Rejecting join request', [
            'team_id' => $team->id,
            'join_request_id' => $joinRequest->id,
            'rejecter_id' => $user->id,
        ]);

        // Check authorization
        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify the join request belongs to this team
        if ($joinRequest->team_id !== $team->id) {
            return response()->json(['error' => 'Join request does not belong to this team'], 400);
        }

        // Check if already processed
        if (!$joinRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => 'This join request has already been processed.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Reject the request
            $joinRequest->reject($user);

            DB::commit();

            Log::info('TeamJoinRequestController::reject - Request rejected', [
                'join_request_id' => $joinRequest->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Join request rejected.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamJoinRequestController::reject - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reject join request. Please try again.'
            ], 500);
        }
    }

    /**
     * Cancel a join request (by the requester)
     */
    public function cancel(Team $team, TeamJoinRequest $joinRequest): JsonResponse
    {
        $user = Auth::user();

        Log::info('TeamJoinRequestController::cancel - Canceling join request', [
            'team_id' => $team->id,
            'join_request_id' => $joinRequest->id,
            'user_id' => $user->id,
        ]);

        // Check authorization - only the requester can cancel their own request
        if ($joinRequest->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if already processed
        if (!$joinRequest->isPending()) {
            return response()->json([
                'success' => false,
                'error' => 'This join request has already been processed and cannot be canceled.'
            ], 409);
        }

        try {
            $joinRequest->delete();

            Log::info('TeamJoinRequestController::cancel - Request canceled', [
                'join_request_id' => $joinRequest->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Join request canceled.',
            ]);

        } catch (\Exception $e) {
            Log::error('TeamJoinRequestController::cancel - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel join request. Please try again.'
            ], 500);
        }
    }

    /**
     * Get user's pending join requests (across all teams)
     */
    public function userRequests(): JsonResponse
    {
        $user = Auth::user();

        $userRequests = TeamJoinRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('team.creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'join_requests' => $userRequests,
        ]);
    }

    /**
     * Check if user can manage the team (leader or co-leader)
     */
    private function canManageTeam($user, Team $team): bool
    {
        return $user->id === $team->creator_id ||
               $team->members()->where('user_id', $user->id)->where('role', 'co_leader')->exists();
    }
}
