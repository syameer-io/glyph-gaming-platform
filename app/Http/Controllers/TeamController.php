<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Server;
use App\Services\TeamService;
use App\Events\TeamCreated;
use App\Events\TeamMemberJoined;
use App\Events\TeamMemberLeft;
use App\Events\TeamStatusChanged;
use App\Http\Requests\InviteMemberRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Display a listing of teams
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Team::with(['server', 'creator', 'activeMembers.user']);

        // Filter by game if specified
        if ($request->filled('game_appid')) {
            $query->where('game_appid', $request->game_appid);
        }

        // Filter by server if specified
        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        // Filter by skill level
        if ($request->filled('skill_level')) {
            $query->where('skill_level', $request->skill_level);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to recruiting teams
            $query->where('status', 'recruiting');
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teams = $query->orderBy('created_at', 'desc')->paginate(12);

        // Get user's teams for sidebar
        $userTeams = $user->teams()
            ->whereIn('teams.status', ['recruiting', 'full', 'active'])
            ->with(['server', 'creator'])
            ->get();

        return view('teams.index', compact('teams', 'userTeams'));
    }

    /**
     * Show the form for creating a new team
     */
    public function create(): View
    {
        $user = Auth::user();

        // Get user's servers (optional - user can create teams without being in any servers)
        $servers = $user->servers()->get();

        // Get user's gaming preferences for game selection
        $games = $user->gamingPreferences()
            ->orderBy('preference_level', 'desc')
            ->get();

        return view('teams.create', compact('servers', 'games'));
    }

    /**
     * Store a newly created team
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'game_appid' => 'required|string',
            'game_name' => 'required|string|max:255',
            'server_id' => 'nullable|exists:servers,id',  // Changed to nullable
            'max_size' => 'required|integer|min:2|max:10',
            'skill_level' => 'required|in:beginner,intermediate,advanced,expert',
            'preferred_region' => 'required|in:na_east,na_west,eu_west,eu_east,asia,oceania',
            'recruitment_status' => 'required|in:open,closed',
            'communication_required' => 'nullable|boolean',
            'competitive_focus' => 'nullable|boolean',
            'required_roles' => 'nullable|array',
            'required_roles.*' => 'string|in:entry_fragger,support,awper,igl,lurker,carry,mid,offlaner,jungler,hard_support,dps,tank,healer,scout,assault,recon',
            'activity_times' => 'nullable|array',
            'activity_times.*' => 'string|in:morning,afternoon,evening,night,flexible',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Server membership check only if a server is specified
        if ($request->filled('server_id')) {
            $server = Server::find($request->server_id);

            if ($server && !$server->members()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'You must be a member of the server to create a team associated with it.'], 403);
            }
        }

        // Check if user already leads a team for this game
        $existingTeam = $user->createdTeams()
            ->where('game_appid', $request->game_appid)
            ->whereIn('status', ['recruiting', 'full', 'active'])
            ->first();

        if ($existingTeam) {
            return response()->json([
                'error' => 'You already lead a team for this game. Leave your current team first.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Create the team
            $team = Team::create([
                'name' => $request->name,
                'description' => $request->description,
                'game_appid' => $request->game_appid,
                'game_name' => $request->game_name,
                'server_id' => $request->server_id,
                'creator_id' => $user->id,
                'max_size' => $request->max_size,
                'current_size' => 0,
                'skill_level' => $request->skill_level,
                'status' => 'recruiting',  // Always recruiting until max_size reached
                'recruitment_status' => $request->recruitment_status,  // Store in database column
                'team_data' => [
                    'preferred_region' => $request->preferred_region,
                    'communication_required' => (bool) $request->communication_required,
                    'competitive_focus' => (bool) $request->competitive_focus,
                ],
                'required_roles' => $request->required_roles ?? [],
                'activity_times' => $request->activity_times ?? [],
                'languages' => $request->languages ?? ['en'],
            ]);

            \Log::info('Team created with recruitment_status', [
                'team_id' => $team->id,
                'recruitment_status' => $team->recruitment_status,
                'team_data' => $team->team_data,
                'status' => $team->status,
            ]);

            // Add creator as team leader (bypass recruitment checks)
            $steamData = $user->profile->steam_data ?? [];
            $skillMetrics = $steamData['skill_metrics'] ?? [];
            $skillScore = $skillMetrics[$request->game_appid]['skill_score'] ?? 50;

            $addResult = $team->addMember($user, [
                'role' => 'leader',
                'skill_level' => $request->skill_level,
                'individual_skill_score' => $skillScore,
                'joined_at' => now(),
            ], true);

            if (!$addResult) {
                DB::rollback();
                return response()->json(['error' => 'Failed to add creator as team leader.'], 500);
            }

            DB::commit();

            // Broadcast team creation event
            event(new TeamCreated($team->load(['server', 'creator'])));

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully!',
                'team' => $team->load(['server', 'creator', 'activeMembers.user'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create team. Please try again.'], 500);
        }
    }

    /**
     * Display the specified team
     */
    public function show(Team $team): View
    {
        // Eager load team members with their active lobbies to prevent N+1 queries (Phase 1: Lobby Integration)
        $team->load([
            'server',
            'creator',
            'activeMembers.user.activeLobbies', // Load lobbies for team members
            'activeMembers' => function ($query) {
                $query->orderBy('role', 'desc')->orderBy('joined_at', 'asc');
            }
        ]);

        $user = Auth::user();

        // Debug logging - what recruitment_status are we passing to view?
        \Log::info('TeamController::show - Team data being passed to view', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'recruitment_status' => $team->recruitment_status,
            'team_data' => $team->team_data,
            'isOpenForRecruitment' => $team->isOpenForRecruitment(),
            'isClosedForRecruitment' => $team->isClosedForRecruitment(),
        ]);

        // Check if user is a member
        $userMembership = $team->members()->where('user_id', $user->id)->first();
        $isMember = $userMembership !== null;
        $isLeader = $isMember && ($userMembership->role === 'leader' || $user->id === $team->creator_id);

        // Get user's pending join request for this team
        $userJoinRequest = \App\Models\TeamJoinRequest::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        \Log::info('TeamController::show - User membership status', [
            'user_id' => $user->id,
            'is_member' => $isMember,
            'is_leader' => $isLeader,
            'has_pending_request' => $userJoinRequest !== null,
        ]);

        // Get pending join requests for team leaders
        $pendingJoinRequests = collect([]);
        if ($isLeader) {
            $pendingJoinRequests = $team->pendingJoinRequests()
                ->with('user.profile')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get team statistics
        $stats = [
            'balance_score' => $team->calculateBalanceScore(),
            'average_skill' => $team->average_skill_score,
            'needed_roles' => $team->getNeededRoles(),
            'member_count' => $team->current_size,
            'recruitment_status' => $team->status,
        ];

        // Get recent team activity (placeholder for now)
        $recentActivity = collect([]);

        return view('teams.show', compact('team', 'userMembership', 'isMember', 'isLeader', 'userJoinRequest', 'pendingJoinRequests', 'stats', 'recentActivity'));
    }

    /**
     * Show the form for editing the specified team
     */
    public function edit(Team $team): View
    {
        $user = Auth::user();

        // Check if user can edit the team
        if (!$this->canManageTeam($user, $team)) {
            abort(403, 'You do not have permission to edit this team.');
        }

        return view('teams.edit', compact('team'));
    }

    /**
     * Update the specified team
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'max_size' => 'required|integer|min:2|max:10',
            'skill_level' => 'required|in:beginner,intermediate,advanced,expert',
            'preferred_region' => 'required|in:na_east,na_west,eu_west,eu_east,asia,oceania',
            'recruitment_status' => 'nullable|in:open,closed',
            'recruitment_message' => 'nullable|string|max:500',
            'required_roles' => 'nullable|array',
            'required_roles.*' => 'string|in:entry_fragger,support,awper,igl,lurker,carry,mid,offlaner,jungler,hard_support,dps,tank,healer,scout,assault,recon',
            'activity_times' => 'nullable|array',
            'activity_times.*' => 'string|in:morning,afternoon,evening,night,flexible',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:10',
            'team_settings' => 'nullable|array',
            'status' => 'nullable|in:recruiting,full,active,completed,disbanded',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Don't allow reducing max_size below current size
        if ($request->max_size < $team->current_size) {
            return response()->json([
                'error' => 'Cannot set maximum size below current team size.'
            ], 422);
        }

        // Get current team_data and merge with new preferred_region
        $teamData = $team->team_data ?? [];
        $teamData['preferred_region'] = $request->preferred_region;

        // Merge other team_data fields if they exist in request
        if ($request->filled('communication_required')) {
            $teamData['communication_required'] = (bool) $request->communication_required;
        }
        if ($request->filled('competitive_focus')) {
            $teamData['competitive_focus'] = (bool) $request->competitive_focus;
        }

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'max_size' => $request->max_size,
            'skill_level' => $request->skill_level,
            'team_data' => $teamData,
            'recruitment_message' => $request->recruitment_message,
            'required_roles' => $request->input('required_roles', []),
            'activity_times' => $request->input('activity_times', []),
            'languages' => $request->input('languages', ['en']),
            'status' => $request->status ?? $team->status,
        ];

        // Add recruitment_status if provided
        if ($request->filled('recruitment_status')) {
            $updateData['recruitment_status'] = $request->recruitment_status;
        }

        // Merge additional team_settings if provided
        if ($request->filled('team_settings')) {
            $updateData['team_settings'] = $request->team_settings;
        }

        $team->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully!',
            'team' => $team->fresh()->load(['server', 'creator', 'activeMembers.user'])
        ]);
    }

    /**
     * Remove the specified team
     */
    public function destroy(Team $team): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow deletion if team has no active members (except creator)
        if ($team->activeMembers()->where('user_id', '!=', $user->id)->exists()) {
            return response()->json([
                'error' => 'Cannot delete team with active members. Remove all members first.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Remove all members
            $team->members()->delete();
            
            // Delete the team
            $team->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Team deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to delete team.'], 500);
        }
    }

    /**
     * Direct team join (from teams page, not via matchmaking)
     *
     * This method handles team joining based on recruitment_status:
     * - 'open': Immediately adds user to team
     * - 'closed': Creates a join request for team leader approval
     */
    public function joinTeamDirect(Request $request, Team $team): JsonResponse
    {
        $user = Auth::user();

        \Log::info('TeamController::joinTeamDirect called', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'recruitment_status' => $team->recruitment_status,
        ]);

        // Check recruitment status and route accordingly
        if ($team->isOpenForRecruitment()) {
            // Open recruitment - add user directly to team
            \Log::info('TeamController::joinTeamDirect - Open recruitment, joining directly');

            $result = $this->teamService->addMemberToTeam($team, $user);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'team' => $team->fresh()->load(['activeMembers.user', 'server', 'creator'])
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['message']
            ], 409);

        } else {
            // Closed recruitment - redirect to join request creation
            \Log::info('TeamController::joinTeamDirect - Closed recruitment, creating join request');

            return response()->json([
                'success' => false,
                'error' => 'This team has closed recruitment. Please use "Request to Join" instead.',
                'requires_request' => true,
            ], 403);
        }
    }

    /**
     * Add a member to the team (by team leader/co-leader)
     *
     * This method is called when a team leader/co-leader manually invites a user to the team.
     * Supports flexible user lookup by user_id, username, or email.
     *
     * Flow:
     * 1. Validate request data (InviteMemberRequest)
     * 2. Authorize request (canManageTeam check)
     * 3. Look up target user by provided identifier
     * 4. Validate business rules (self-invite, team full, etc.)
     * 5. Add member via TeamService with transaction safety
     * 6. Return updated team data with eager-loaded relationships
     *
     * @param InviteMemberRequest $request Validated invitation request
     * @param Team $team Team to add member to (route model binding)
     * @return JsonResponse JSON response with success/error status
     */
    public function addMember(InviteMemberRequest $request, Team $team): JsonResponse
    {
        // Log request for debugging and audit trail
        \Log::info('TeamController::addMember - Invitation initiated', [
            'request_data' => $request->validated(),
            'team_id' => $team->id,
            'team_name' => $team->name,
            'auth_user_id' => Auth::id(),
            'auth_username' => Auth::user()->username,
        ]);

        $user = Auth::user();

        // AUTHORIZATION CHECK: Only team creator or co-leaders can invite members
        // This check is critical to prevent unauthorized member additions
        if (!$this->canManageTeam($user, $team)) {
            \Log::error('TeamController::addMember - Unauthorized invitation attempt', [
                'user_id' => $user->id,
                'username' => $user->username,
                'team_id' => $team->id,
                'team_name' => $team->name,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'You do not have permission to invite members to this team. Only team leaders and co-leaders can send invitations.'
            ], 403);
        }

        // USER LOOKUP: Find target user by user_id, username, or email
        // Validation ensures at least one identifier exists, but we need to
        // actually fetch the user model for further processing
        $targetUser = null;

        try {
            // Priority order: user_id > username > email
            // Eager load profile to prevent N+1 query when accessing display_name later
            if ($request->filled('user_id')) {
                $targetUser = User::with('profile')->find($request->user_id);
                \Log::debug('TeamController::addMember - Looking up by user_id', [
                    'user_id' => $request->user_id,
                    'found' => $targetUser !== null,
                ]);
            } elseif ($request->filled('username')) {
                $targetUser = User::with('profile')->where('username', $request->username)->first();
                \Log::debug('TeamController::addMember - Looking up by username', [
                    'username' => $request->username,
                    'found' => $targetUser !== null,
                ]);
            } elseif ($request->filled('email')) {
                $targetUser = User::with('profile')->where('email', $request->email)->first();
                \Log::debug('TeamController::addMember - Looking up by email', [
                    'email' => $request->email,
                    'found' => $targetUser !== null,
                ]);
            }
        } catch (\Exception $e) {
            // Catch any database errors during user lookup
            \Log::error('TeamController::addMember - Database error during user lookup', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user_id,
                'username' => $request->username,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while looking up the user. Please try again.'
            ], 500);
        }

        // VALIDATION: Ensure user was found after validation
        // This should rarely happen since validation checks 'exists:users',
        // but could occur in edge cases (race condition, database inconsistency)
        if (!$targetUser) {
            \Log::error('TeamController::addMember - User not found after validation passed', [
                'user_id' => $request->user_id,
                'username' => $request->username,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'User not found. Please verify the username or email address and try again.'
            ], 404);
        }

        // VALIDATION: Prevent self-invitation
        // Team leaders cannot invite themselves (they should already be members)
        if ($targetUser->id === $user->id) {
            \Log::warning('TeamController::addMember - Self-invitation attempt blocked', [
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'You cannot invite yourself to the team. You are already a member as the team leader.'
            ], 422);
        }

        // PREPARE MEMBER DATA: Build data array for TeamService
        // Default role is 'member' unless specified as 'co_leader'
        $memberData = [
            'role' => $request->input('role', 'member'), // Default to 'member'
            'game_role' => $request->game_role,          // Optional game-specific role
        ];

        \Log::info('TeamController::addMember - Calling TeamService to add member', [
            'target_user_id' => $targetUser->id,
            'target_username' => $targetUser->username,
            'target_display_name' => $targetUser->display_name,
            'member_data' => $memberData,
            'bypass_recruitment_check' => true,
        ]);

        // DELEGATE TO SERVICE LAYER: Use TeamService for shared business logic
        // Pass true for $bypassRecruitmentCheck parameter since this is a leader
        // invitation (bypasses open/closed recruitment status checks)
        //
        // TeamService handles:
        // - Team full validation
        // - Duplicate membership check
        // - Already in another team for game check
        // - Server membership auto-join
        // - Database transaction with rollback on error
        // - Event broadcasting (TeamMemberJoined)
        try {
            $result = $this->teamService->addMemberToTeam(
                $team,
                $targetUser,
                $memberData,
                null,  // No matchmaking request for manual invitations
                true   // Bypass recruitment status checks
            );
        } catch (\Exception $e) {
            // Catch any unexpected exceptions from TeamService
            \Log::error('TeamController::addMember - TeamService threw exception', [
                'team_id' => $team->id,
                'target_user_id' => $targetUser->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred while adding the member. Please try again or contact support if the issue persists.'
            ], 500);
        }

        // SUCCESS PATH: Member added successfully
        if ($result['success']) {
            \Log::info('TeamController::addMember - Member added successfully', [
                'team_id' => $team->id,
                'team_member_id' => $result['member']->id ?? null,
                'target_user_id' => $targetUser->id,
                'target_username' => $targetUser->username,
                'role' => $memberData['role'],
            ]);

            // Refresh team data with eager-loaded relationships to prevent N+1 queries
            // This ensures the frontend receives complete, up-to-date team data
            $refreshedTeam = $team->fresh()->load([
                'activeMembers.user.profile',  // All active members with user profiles
                'server',                       // Associated server (if any)
                'creator.profile',              // Team creator with profile
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$targetUser->display_name} has been successfully added to the team!",
                'team' => $refreshedTeam,
            ], 200);
        }

        // FAILURE PATH: TeamService returned failure
        \Log::error('TeamController::addMember - TeamService returned failure', [
            'team_id' => $team->id,
            'target_user_id' => $targetUser->id,
            'error_message' => $result['message'],
        ]);

        // Return user-friendly error message from TeamService
        // Common errors: team full, already a member, already in another team
        return response()->json([
            'success' => false,
            'error' => $result['message']
        ], 409); // 409 Conflict - request conflicts with current state
    }

    /**
     * Remove a member from the team
     *
     * @param Team $team - Route model binding for {team}
     * @param User $user - Route model binding for {user} - the member to remove
     */
    public function removeMember(Team $team, User $user): JsonResponse
    {
        \Log::info('TeamController::removeMember called', [
            'team_id' => $team->id,
            'target_user_id' => $user->id,
            'auth_user_id' => Auth::id()
        ]);

        $authUser = Auth::user();

        if (!$this->canManageTeam($authUser, $team) && $authUser->id !== $user->id) {
            \Log::error('TeamController::removeMember - Unauthorized', [
                'auth_user_id' => $authUser->id,
                'team_id' => $team->id,
                'target_user_id' => $user->id
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Cannot remove the team creator/leader
        if ($user->id === $team->creator_id) {
            \Log::error('TeamController::removeMember - Cannot remove creator', [
                'target_user_id' => $user->id,
                'creator_id' => $team->creator_id
            ]);
            return response()->json(['error' => 'Cannot remove team creator'], 409);
        }

        $member = $team->members()->where('user_id', $user->id)->first();

        if (!$member) {
            \Log::error('TeamController::removeMember - Member not found', [
                'team_id' => $team->id,
                'target_user_id' => $user->id
            ]);
            return response()->json(['error' => 'User is not a member of this team'], 404);
        }

        // Store member data for event before removal
        $memberData = [
            'role' => $member->role,
            'game_role' => $member->game_role,
            'skill_score' => $member->individual_skill_score,
        ];

        \Log::info('TeamController::removeMember - Calling team->removeMember()', [
            'member_id' => $member->id,
            'member_data' => $memberData
        ]);

        $removeResult = $team->removeMember($user);

        if (!$removeResult) {
            \Log::error('TeamController::removeMember - Failed to remove member', [
                'team_id' => $team->id,
                'target_user_id' => $user->id
            ]);
            return response()->json(['error' => 'Failed to remove member from team.'], 500);
        }

        \Log::info('TeamController::removeMember - Member removed successfully');

        // Broadcast team member left event
        event(new TeamMemberLeft($team->fresh(), $user, $memberData));

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully!',
            'team' => $team->fresh()->load(['activeMembers.user'])
        ]);
    }

    /**
     * Update member role
     *
     * @param Team $team - Route model binding for {team}
     * @param User $user - Route model binding for {user} - the member whose role to update
     */
    public function updateMemberRole(Request $request, Team $team, User $user): JsonResponse
    {
        $authUser = Auth::user();

        if (!$this->canManageTeam($authUser, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:member,co_leader',
            'game_role' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $member = $team->members()->where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json(['error' => 'User is not a member of this team'], 404);
        }

        // Cannot change creator's role
        if ($user->id === $team->creator_id) {
            return response()->json(['error' => 'Cannot change team creator role'], 409);
        }

        $member->update([
            'role' => $request->role,
            'game_role' => $request->game_role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member role updated successfully!',
            'member' => $member->fresh()
        ]);
    }

    /**
     * Get team statistics
     */
    public function stats(Team $team): JsonResponse
    {
        $stats = [
            'balance_score' => $team->calculateBalanceScore(),
            'average_skill' => $team->average_skill_score,
            'skill_distribution' => $team->getSkillDistribution(),
            'role_distribution' => $team->getRoleDistribution(),
            'member_count' => $team->current_size,
            'recruitment_status' => $team->status,
            'needed_roles' => $team->getNeededRoles(),
            'team_compatibility' => $team->getTeamCompatibility(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get teams for matchmaking
     */
    public function forMatchmaking(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_appid' => 'required|string',
            'server_id' => 'nullable|exists:servers,id',
            'skill_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'max_results' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Team::recruiting()
            ->where('game_appid', $request->game_appid)
            ->with(['server', 'creator', 'activeMembers.user']);

        if ($request->filled('server_id')) {
            $query->where('server_id', $request->server_id);
        }

        if ($request->filled('skill_level')) {
            $query->where('skill_level', $request->skill_level);
        }

        $teams = $query->orderBy('created_at', 'desc')
                      ->take($request->max_results ?? 20)
                      ->get();

        return response()->json([
            'success' => true,
            'teams' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'current_size' => $team->current_size,
                    'max_size' => $team->max_size,
                    'skill_level' => $team->skill_level,
                    'balance_score' => $team->calculateBalanceScore(),
                    'needed_roles' => $team->getNeededRoles(),
                    'server' => $team->server,
                    'creator' => $team->creator,
                ];
            })
        ]);
    }

    /**
     * Check if user can manage the team
     */
    private function canManageTeam(User $user, Team $team): bool
    {
        return $user->id === $team->creator_id || 
               $team->members()->where('user_id', $user->id)->where('role', 'co_leader')->exists();
    }

    /**
     * Get skill level from score
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
     * Get recommended team size based on game
     *
     * Returns the standard/optimal team size for competitive play in each game.
     * This is useful for team creation forms and validation.
     *
     * @param string $gameAppId Steam App ID
     * @return int Recommended team size (defaults to 5 if game not recognized)
     */
    protected function getRecommendedTeamSize(string $gameAppId): int
    {
        $recommendedSizes = [
            '730' => 5,      // Counter-Strike 2 (5v5 competitive)
            '570' => 5,      // Dota 2 (5v5 MOBA)
            '1172470' => 3,  // Apex Legends (3-player trios)
            '252490' => 5,   // Rust (zerg squad)
            '578080' => 4,   // PUBG (4-player squads)
            '359550' => 5,   // Rainbow Six Siege (5v5 ranked)
            '1446780' => 4,  // Fall Guys (4-player Squad Show)
            '230410' => 4,   // Warframe (4-player squads)
        ];

        return $recommendedSizes[$gameAppId] ?? 5; // Default to 5 if unknown
    }
}
