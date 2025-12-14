<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\TeamInvitationService;
use App\Http\Requests\StoreTeamInvitationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TeamInvitationController extends Controller
{
    protected TeamInvitationService $invitationService;

    public function __construct(TeamInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    // ==================== TEAM LEADER ENDPOINTS ====================

    /**
     * Send a team invitation
     * POST /teams/{team}/invitations
     */
    public function store(StoreTeamInvitationRequest $request, Team $team): JsonResponse
    {
        Log::info('TeamInvitationController::store - Invitation request received', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'auth_user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $result = $this->invitationService->sendInvitation($team, $user, $request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'],
            ], $result['status_code'] ?? 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'invitation' => $result['invitation']->load(['invitee.profile', 'team']),
        ]);
    }

    /**
     * Get pending invitations for a team (leader view)
     * GET /teams/{team}/invitations
     */
    public function index(Team $team): JsonResponse
    {
        $user = Auth::user();

        // Check permission
        if (!$this->canManageTeam($user, $team)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        $invitations = $team->pendingInvitations()
            ->with(['invitee.profile', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'invitee' => [
                        'id' => $invitation->invitee->id,
                        'username' => $invitation->invitee->username,
                        'display_name' => $invitation->invitee->display_name,
                        'avatar_url' => $invitation->invitee->profile->avatar_url ?? null,
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter->id,
                        'username' => $invitation->inviter->username,
                        'display_name' => $invitation->inviter->display_name,
                    ],
                    'role' => $invitation->role,
                    'role_display_name' => $invitation->role_display_name,
                    'message' => $invitation->message,
                    'expires_in' => $invitation->expires_in,
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                    'created_at_human' => $invitation->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
            'count' => $invitations->count(),
        ]);
    }

    /**
     * Cancel a pending invitation (by team leader/co-leader)
     * DELETE /teams/{team}/invitations/{invitation}
     */
    public function cancel(Team $team, TeamInvitation $invitation): JsonResponse
    {
        Log::info('TeamInvitationController::cancel - Cancel request received', [
            'invitation_id' => $invitation->id,
            'team_id' => $team->id,
            'auth_user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $result = $this->invitationService->cancelInvitation($invitation, $user, $team);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'],
            ], $result['status_code'] ?? 403);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    // ==================== INVITEE ENDPOINTS ====================

    /**
     * Get current user's pending invitations
     * GET /team-invitations
     */
    public function userInvitations(): JsonResponse
    {
        $user = Auth::user();

        $invitations = $user->pendingTeamInvitations()
            ->with([
                'team' => function ($query) {
                    $query->withCount('activeMembers');
                },
                'team.creator.profile',
                'inviter.profile',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'team' => [
                        'id' => $invitation->team->id,
                        'name' => $invitation->team->name,
                        'game_name' => $invitation->team->game_name,
                        'game_appid' => $invitation->team->game_appid,
                        'skill_level' => $invitation->team->skill_level,
                        'current_size' => $invitation->team->active_members_count,
                        'max_size' => $invitation->team->max_size,
                        'creator' => [
                            'id' => $invitation->team->creator->id,
                            'display_name' => $invitation->team->creator->display_name,
                        ],
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter->id,
                        'username' => $invitation->inviter->username,
                        'display_name' => $invitation->inviter->display_name,
                        'avatar_url' => $invitation->inviter->profile->avatar_url ?? null,
                    ],
                    'role' => $invitation->role,
                    'role_display_name' => $invitation->role_display_name,
                    'message' => $invitation->message,
                    'expires_in' => $invitation->expires_in,
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                    'created_at_human' => $invitation->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'invitations' => $invitations,
            'count' => $invitations->count(),
        ]);
    }

    /**
     * Accept a team invitation
     * POST /team-invitations/{invitation}/accept
     */
    public function accept(TeamInvitation $invitation): JsonResponse
    {
        Log::info('TeamInvitationController::accept - Accept request received', [
            'invitation_id' => $invitation->id,
            'auth_user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $result = $this->invitationService->acceptInvitation($invitation, $user);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'],
            ], $result['status_code'] ?? 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'team' => [
                'id' => $result['team']->id,
                'name' => $result['team']->name,
                'slug' => $result['team']->slug ?? $result['team']->id,
            ],
        ]);
    }

    /**
     * Decline a team invitation
     * POST /team-invitations/{invitation}/decline
     */
    public function decline(TeamInvitation $invitation): JsonResponse
    {
        Log::info('TeamInvitationController::decline - Decline request received', [
            'invitation_id' => $invitation->id,
            'auth_user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $result = $this->invitationService->declineInvitation($invitation, $user);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'],
            ], $result['status_code'] ?? 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if user can manage team (is creator or co-leader)
     */
    private function canManageTeam($user, Team $team): bool
    {
        if ($user->id === $team->creator_id) {
            return true;
        }

        return $team->members()
            ->where('user_id', $user->id)
            ->where('role', 'co_leader')
            ->where('status', 'active')
            ->exists();
    }
}
