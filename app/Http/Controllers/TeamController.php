<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Server;
use App\Events\TeamCreated;
use App\Events\TeamMemberJoined;
use App\Events\TeamMemberLeft;
use App\Events\TeamStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function __construct()
    {
        // Middleware handled in routes
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
        
        // Get user's servers
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
            'server_id' => 'required|exists:servers,id',
            'max_size' => 'required|integer|min:2|max:10',
            'skill_level' => 'required|in:beginner,intermediate,advanced,expert',
            'preferred_region' => 'required|in:na_east,na_west,eu_west,eu_east,asia,oceania',
            'activity_time' => 'required|in:morning,afternoon,evening,late_night,weekends,flexible',
            'recruitment_status' => 'required|in:open,closed',
            'communication_required' => 'nullable|boolean',
            'competitive_focus' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $server = Server::find($request->server_id);

        // Check if user is a member of the server
        if (!$server->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'You must be a member of the server to create a team.'], 403);
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
                'current_size' => 1,
                'skill_level' => $request->skill_level,
                'status' => ($request->recruitment_status === 'open') ? 'recruiting' : 'full',
                'team_data' => [
                    'preferred_region' => $request->preferred_region,
                    'activity_time' => $request->activity_time,
                    'recruitment_status' => $request->recruitment_status,
                    'communication_required' => (bool) $request->communication_required,
                    'competitive_focus' => (bool) $request->competitive_focus,
                ],
            ]);

            // Add creator as team leader
            $steamData = $user->profile->steam_data ?? [];
            $skillMetrics = $steamData['skill_metrics'] ?? [];
            $skillScore = $skillMetrics[$request->game_appid]['skill_score'] ?? 50;

            $team->addMember($user, [
                'role' => 'leader',
                'skill_level' => $request->skill_level,
                'individual_skill_score' => $skillScore,
                'joined_at' => now(),
            ]);

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
        $team->load([
            'server',
            'creator',
            'activeMembers.user',
            'activeMembers' => function ($query) {
                $query->orderBy('role', 'desc')->orderBy('joined_at', 'asc');
            }
        ]);

        $user = Auth::user();
        
        // Check if user is a member
        $userMembership = $team->members()->where('user_id', $user->id)->first();
        $isMember = $userMembership !== null;
        $isLeader = $isMember && ($userMembership->role === 'leader' || $user->id === $team->creator_id);
        
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

        return view('teams.show', compact('team', 'userMembership', 'isMember', 'isLeader', 'stats', 'recentActivity'));
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
            'recruitment_message' => 'nullable|string|max:500',
            'required_roles' => 'array',
            'required_roles.*' => 'string|max:50',
            'team_settings' => 'array',
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

        $team->update([
            'name' => $request->name,
            'description' => $request->description,
            'max_size' => $request->max_size,
            'skill_level' => $request->skill_level,
            'recruitment_message' => $request->recruitment_message,
            'required_roles' => $request->required_roles ?? [],
            'team_settings' => $request->team_settings ?? [],
            'status' => $request->status ?? $team->status,
        ]);

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
     * Add a member to the team
     */
    public function addMember(Request $request, Team $team): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:member,co_leader',
            'game_role' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::find($request->user_id);

        // Check if team is full
        if ($team->current_size >= $team->max_size) {
            return response()->json(['error' => 'Team is full'], 409);
        }

        // Check if user is already a member
        if ($team->members()->where('user_id', $targetUser->id)->exists()) {
            return response()->json(['error' => 'User is already a member'], 409);
        }

        // Check if user is in another team for this game
        if ($targetUser->teams()->where('game_appid', $team->game_appid)
                ->whereIn('teams.status', ['recruiting', 'full', 'active'])
                ->exists()) {
            return response()->json(['error' => 'User is already in another team for this game'], 409);
        }

        // Get user's skill score
        $steamData = $targetUser->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];
        $skillScore = $skillMetrics[$team->game_appid]['skill_score'] ?? 50;

        $teamMember = $team->addMember($targetUser, [
            'role' => $request->role ?? 'member',
            'game_role' => $request->game_role,
            'skill_level' => $this->getSkillLevel($skillScore),
            'individual_skill_score' => $skillScore,
        ]);

        // Broadcast team member joined event
        event(new TeamMemberJoined($team->fresh(), $teamMember->load('user')));

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully!',
            'team' => $team->fresh()->load(['activeMembers.user'])
        ]);
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(Team $team, User $targetUser): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user, $team) && $user->id !== $targetUser->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Cannot remove the team creator/leader
        if ($targetUser->id === $team->creator_id) {
            return response()->json(['error' => 'Cannot remove team creator'], 409);
        }

        $member = $team->members()->where('user_id', $targetUser->id)->first();

        if (!$member) {
            return response()->json(['error' => 'User is not a member of this team'], 404);
        }

        // Store member data for event before removal
        $memberData = [
            'role' => $member->role,
            'game_role' => $member->game_role,
            'skill_score' => $member->individual_skill_score,
        ];

        $team->removeMember($targetUser);

        // Broadcast team member left event
        event(new TeamMemberLeft($team->fresh(), $targetUser, $memberData));

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully!',
            'team' => $team->fresh()->load(['activeMembers.user'])
        ]);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Request $request, Team $team, User $targetUser): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user, $team)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:member,co_leader',
            'game_role' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $member = $team->members()->where('user_id', $targetUser->id)->first();

        if (!$member) {
            return response()->json(['error' => 'User is not a member of this team'], 404);
        }

        // Cannot change creator's role
        if ($targetUser->id === $team->creator_id) {
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
}
