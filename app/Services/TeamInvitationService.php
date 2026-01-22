<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Events\TeamInvitationSent;
use App\Events\TeamInvitationAccepted;
use App\Events\TeamInvitationDeclined;
use App\Events\TeamInvitationCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamInvitationService
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    // ==================== SEND INVITATION ====================

    /**
     * Send a team invitation to a user
     *
     * @param Team $team The team to invite to
     * @param User $inviter The user sending the invitation (must be leader/co-leader)
     * @param array $data Contains: user_id|username|email, role, message
     * @return array ['success' => bool, 'message' => string, 'invitation' => ?TeamInvitation, 'status_code' => ?int]
     */
    public function sendInvitation(Team $team, User $inviter, array $data): array
    {
        Log::info('TeamInvitationService::sendInvitation START', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'inviter_id' => $inviter->id,
            'inviter_name' => $inviter->username,
            'data' => array_diff_key($data, ['email' => '']), // Don't log email for privacy
        ]);

        // 1. Verify inviter has permission to invite
        if (!$this->canManageTeam($inviter, $team)) {
            Log::warning('TeamInvitationService::sendInvitation - Unauthorized attempt', [
                'inviter_id' => $inviter->id,
                'team_id' => $team->id,
            ]);

            return [
                'success' => false,
                'message' => 'You do not have permission to invite members to this team.',
                'status_code' => 403,
            ];
        }

        // 2. Find the target user
        $invitee = $this->findUser($data);
        if (!$invitee) {
            Log::info('TeamInvitationService::sendInvitation - User not found', [
                'user_id' => $data['user_id'] ?? null,
                'username' => $data['username'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'User not found. Please verify the username or email address.',
                'status_code' => 404,
            ];
        }

        // 3. Validate business rules
        $validation = $this->validateInvitation($team, $inviter, $invitee);
        if (!$validation['valid']) {
            Log::info('TeamInvitationService::sendInvitation - Validation failed', [
                'reason' => $validation['message'],
                'team_id' => $team->id,
                'invitee_id' => $invitee->id,
            ]);

            return [
                'success' => false,
                'message' => $validation['message'],
                'status_code' => 409,
            ];
        }

        // 4. Create the invitation within a transaction
        DB::beginTransaction();

        try {
            $expiryDays = (int) config('teams.invitation_expiry_days', 7);

            $invitation = TeamInvitation::create([
                'team_id' => $team->id,
                'inviter_id' => $inviter->id,
                'invitee_id' => $invitee->id,
                'status' => 'pending',
                'role' => $data['role'] ?? 'member',
                'message' => isset($data['message']) ? trim($data['message']) : null,
                'expires_at' => now()->addDays($expiryDays),
            ]);

            DB::commit();

            Log::info('TeamInvitationService::sendInvitation SUCCESS', [
                'invitation_id' => $invitation->id,
                'team_id' => $team->id,
                'invitee_id' => $invitee->id,
                'expires_at' => $invitation->expires_at->toDateTimeString(),
            ]);

            // 5. Broadcast event for real-time notification
            event(new TeamInvitationSent($invitation->load(['team', 'inviter', 'invitee.profile'])));

            return [
                'success' => true,
                'message' => "Invitation sent to {$invitee->display_name}!",
                'invitation' => $invitation,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamInvitationService::sendInvitation FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'team_id' => $team->id,
                'invitee_id' => $invitee->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send invitation. Please try again.',
                'status_code' => 500,
            ];
        }
    }

    // ==================== ACCEPT INVITATION ====================

    /**
     * Accept a team invitation and add user to team
     *
     * @param TeamInvitation $invitation
     * @param User $user The user accepting (must be the invitee)
     * @return array ['success' => bool, 'message' => string, 'team' => ?Team, 'member' => ?TeamMember, 'status_code' => ?int]
     */
    public function acceptInvitation(TeamInvitation $invitation, User $user): array
    {
        Log::info('TeamInvitationService::acceptInvitation START', [
            'invitation_id' => $invitation->id,
            'user_id' => $user->id,
            'team_id' => $invitation->team_id,
        ]);

        // 1. Verify user is the invitee
        if ($invitation->invitee_id !== $user->id) {
            Log::warning('TeamInvitationService::acceptInvitation - Wrong user', [
                'invitation_invitee_id' => $invitation->invitee_id,
                'attempting_user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'This invitation is not for you.',
                'status_code' => 403,
            ];
        }

        // 2. Check invitation is still actionable
        if (!$invitation->isActionable()) {
            $reason = $invitation->isExpired() ? 'expired' : 'no longer valid';

            Log::info('TeamInvitationService::acceptInvitation - Not actionable', [
                'invitation_id' => $invitation->id,
                'status' => $invitation->status,
                'is_expired' => $invitation->isExpired(),
            ]);

            return [
                'success' => false,
                'message' => "This invitation has {$reason}.",
                'status_code' => 409,
            ];
        }

        $team = $invitation->team;

        // 3. Check team can still accept the member
        if ($team->isFull()) {
            Log::info('TeamInvitationService::acceptInvitation - Team full', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
            ]);

            return [
                'success' => false,
                'message' => 'This team is now full.',
                'status_code' => 409,
            ];
        }

        // 4. Accept invitation and add member within transaction
        DB::beginTransaction();

        try {
            // Update invitation status
            $invitation->accept();

            // Add member to team via TeamService
            // Use bypassRecruitmentCheck=true since invitation is authorization
            $memberData = ['role' => $invitation->role];
            $addResult = $this->teamService->addMemberToTeam(
                $team,
                $user,
                $memberData,
                null,  // No matchmaking request
                true   // Bypass recruitment check (invitation = authorized)
            );

            if (!$addResult['success']) {
                DB::rollBack();

                Log::error('TeamInvitationService::acceptInvitation - Failed to add member', [
                    'error' => $addResult['message'],
                    'invitation_id' => $invitation->id,
                ]);

                return [
                    'success' => false,
                    'message' => $addResult['message'],
                    'status_code' => 409,
                ];
            }

            DB::commit();

            Log::info('TeamInvitationService::acceptInvitation SUCCESS', [
                'invitation_id' => $invitation->id,
                'team_id' => $team->id,
                'new_member_id' => $addResult['member']->id ?? null,
            ]);

            // 5. Broadcast acceptance event
            event(new TeamInvitationAccepted($invitation->fresh()->load(['team', 'invitee', 'inviter'])));

            return [
                'success' => true,
                'message' => "You have joined {$team->name}!",
                'team' => $team->fresh(),
                'member' => $addResult['member'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamInvitationService::acceptInvitation FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invitation_id' => $invitation->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to accept invitation. Please try again.',
                'status_code' => 500,
            ];
        }
    }

    // ==================== DECLINE INVITATION ====================

    /**
     * Decline a team invitation
     *
     * @param TeamInvitation $invitation
     * @param User $user The user declining (must be the invitee)
     * @return array ['success' => bool, 'message' => string, 'status_code' => ?int]
     */
    public function declineInvitation(TeamInvitation $invitation, User $user): array
    {
        Log::info('TeamInvitationService::declineInvitation START', [
            'invitation_id' => $invitation->id,
            'user_id' => $user->id,
        ]);

        // 1. Verify user is the invitee
        if ($invitation->invitee_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This invitation is not for you.',
                'status_code' => 403,
            ];
        }

        // 2. Check invitation is actionable
        if (!$invitation->isActionable()) {
            return [
                'success' => false,
                'message' => 'This invitation is no longer valid.',
                'status_code' => 409,
            ];
        }

        // 3. Decline the invitation
        $invitation->decline();

        Log::info('TeamInvitationService::declineInvitation SUCCESS', [
            'invitation_id' => $invitation->id,
        ]);

        // 4. Broadcast event
        event(new TeamInvitationDeclined($invitation->fresh()->load(['team', 'invitee', 'inviter'])));

        return [
            'success' => true,
            'message' => 'Invitation declined.',
        ];
    }

    // ==================== CANCEL INVITATION ====================

    /**
     * Cancel a pending invitation (by team leader/co-leader)
     *
     * @param TeamInvitation $invitation
     * @param User $user The user cancelling (must be team leader/co-leader)
     * @param Team $team The team the invitation belongs to
     * @return array ['success' => bool, 'message' => string, 'status_code' => ?int]
     */
    public function cancelInvitation(TeamInvitation $invitation, User $user, Team $team): array
    {
        Log::info('TeamInvitationService::cancelInvitation START', [
            'invitation_id' => $invitation->id,
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        // 1. Verify invitation belongs to this team
        if ($invitation->team_id !== $team->id) {
            return [
                'success' => false,
                'message' => 'Invitation does not belong to this team.',
                'status_code' => 400,
            ];
        }

        // 2. Verify user can manage team
        if (!$this->canManageTeam($user, $team)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to cancel this invitation.',
                'status_code' => 403,
            ];
        }

        // 3. Check invitation is still pending
        if (!$invitation->isPending()) {
            return [
                'success' => false,
                'message' => 'This invitation has already been processed.',
                'status_code' => 409,
            ];
        }

        // 4. Cancel the invitation
        $inviteeName = $invitation->invitee->display_name;
        $invitation->cancel();

        Log::info('TeamInvitationService::cancelInvitation SUCCESS', [
            'invitation_id' => $invitation->id,
        ]);

        // 5. Broadcast event
        event(new TeamInvitationCancelled($invitation->fresh()->load(['team', 'invitee'])));

        return [
            'success' => true,
            'message' => "Invitation to {$inviteeName} cancelled.",
        ];
    }

    // ==================== HELPER METHODS ====================

    /**
     * Validate business rules for sending an invitation
     *
     * @return array ['valid' => bool, 'message' => string]
     */
    protected function validateInvitation(Team $team, User $inviter, User $invitee): array
    {
        // Cannot invite yourself
        if ($inviter->id === $invitee->id) {
            return ['valid' => false, 'message' => 'You cannot invite yourself.'];
        }

        // Cannot invite existing team member
        $existingMember = $team->members()
            ->where('user_id', $invitee->id)
            ->whereIn('status', ['active', 'inactive'])
            ->first();

        if ($existingMember) {
            return ['valid' => false, 'message' => 'This user is already a member of the team.'];
        }

        // Cannot invite if team is full
        if ($team->isFull()) {
            return ['valid' => false, 'message' => 'Team is full. Cannot send invitations.'];
        }

        // Check for existing pending invitation
        $existingInvitation = TeamInvitation::where('team_id', $team->id)
            ->where('invitee_id', $invitee->id)
            ->active()
            ->first();

        if ($existingInvitation) {
            return ['valid' => false, 'message' => 'This user already has a pending invitation to this team.'];
        }

        // Check if user is in another active team for the same game
        $activeTeamForGame = $invitee->teams()
            ->where('game_appid', $team->game_appid)
            ->whereIn('teams.status', ['recruiting', 'full', 'active'])
            ->wherePivot('status', 'active')
            ->first();

        if ($activeTeamForGame) {
            return [
                'valid' => false,
                'message' => "This user is already in '{$activeTeamForGame->name}' for {$team->game_name}. They must leave that team first."
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Find user by user_id, username, or email
     */
    protected function findUser(array $data): ?User
    {
        if (!empty($data['user_id'])) {
            return User::with('profile')->find($data['user_id']);
        }
        if (!empty($data['username'])) {
            return User::with('profile')->where('username', $data['username'])->first();
        }
        if (!empty($data['email'])) {
            return User::with('profile')->where('email', $data['email'])->first();
        }
        return null;
    }

    /**
     * Check if user can manage team (is creator or co-leader)
     */
    protected function canManageTeam(User $user, Team $team): bool
    {
        // Team creator always has permission
        if ($user->id === $team->creator_id) {
            return true;
        }

        // Co-leaders also have permission
        return $team->members()
            ->where('user_id', $user->id)
            ->where('role', 'co_leader')
            ->where('status', 'active')
            ->exists();
    }
}
