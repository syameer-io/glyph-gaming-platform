<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\TeamMember;
use App\Models\MatchmakingRequest;
use App\Events\TeamMemberJoined;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TeamService - Production-ready service for team management operations
 *
 * Handles complex team membership operations including:
 * - Direct team joins from teams page
 * - Matchmaking-based team joins
 * - Comprehensive validation and error handling
 * - Database transaction management
 * - Real-time event broadcasting
 *
 * @package App\Services
 */
class TeamService
{
    /**
     * Add a member to a team with comprehensive validation and transaction handling
     *
     * This method consolidates all team joining logic for both:
     * 1. Direct joins (from teams page)
     * 2. Matchmaking joins (with MatchmakingRequest parameter)
     * 3. Approved join requests (with bypass parameter)
     *
     * Validation performed:
     * - Team recruiting status check (unless bypassed for approved requests)
     * - Team capacity check
     * - Duplicate membership check
     * - User already in another team for same game check
     * - Server membership verification
     *
     * @param Team $team The team to join
     * @param User $user The user joining the team
     * @param array $memberData Optional member data (role, game_role, skill_level, etc.)
     * @param MatchmakingRequest|null $matchmakingRequest Optional matchmaking request if joining via matchmaking
     * @param bool $bypassRecruitmentCheck Set to true to bypass recruitment status checks (for approved join requests)
     * @return array Returns ['success' => bool, 'message' => string, 'member' => TeamMember|null]
     */
    public function addMemberToTeam(
        Team $team,
        User $user,
        array $memberData = [],
        ?MatchmakingRequest $matchmakingRequest = null,
        bool $bypassRecruitmentCheck = false
    ): array {
        Log::info('TeamService::addMemberToTeam START', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'user_id' => $user->id,
            'username' => $user->username,
            'game_appid' => $team->game_appid,
            'via_matchmaking' => $matchmakingRequest !== null,
            'matchmaking_request_id' => $matchmakingRequest?->id,
            'bypass_recruitment_check' => $bypassRecruitmentCheck,
            'recruitment_status' => $team->recruitment_status,
            'is_recruiting' => $team->isRecruiting(),
        ]);

        // 1. Validate team is recruiting (unless bypassing for approved join requests)
        if (!$bypassRecruitmentCheck && !$team->isRecruiting()) {
            Log::warning('TeamService::addMemberToTeam - Team not recruiting', [
                'team_id' => $team->id,
                'status' => $team->status,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
                'recruitment_deadline' => $team->recruitment_deadline,
                'recruitment_status' => $team->recruitment_status,
            ]);

            return [
                'success' => false,
                'message' => 'This team is not currently recruiting members.',
                'member' => null,
            ];
        }

        // 2. Validate team is not full
        if ($team->isFull()) {
            Log::warning('TeamService::addMemberToTeam - Team is full', [
                'team_id' => $team->id,
                'current_size' => $team->current_size,
                'max_size' => $team->max_size,
            ]);

            return [
                'success' => false,
                'message' => 'This team is already full.',
                'member' => null,
            ];
        }

        // 3. Check if user is already a member of this team
        $existingMember = $team->members()->where('user_id', $user->id)->first();
        if ($existingMember) {
            Log::warning('TeamService::addMemberToTeam - User already a member', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'existing_member_id' => $existingMember->id,
                'existing_status' => $existingMember->status,
            ]);

            return [
                'success' => false,
                'message' => 'You are already a member of this team.',
                'member' => $existingMember,
            ];
        }

        // 4. Check if user is already in another active team for the same game
        $activeTeamForGame = $user->teams()
            ->where('game_appid', $team->game_appid)
            ->whereIn('teams.status', ['recruiting', 'full', 'active'])
            ->wherePivot('status', 'active')
            ->first();

        if ($activeTeamForGame) {
            Log::warning('TeamService::addMemberToTeam - User already in active team for game', [
                'user_id' => $user->id,
                'game_appid' => $team->game_appid,
                'existing_team_id' => $activeTeamForGame->id,
                'existing_team_name' => $activeTeamForGame->name,
            ]);

            return [
                'success' => false,
                'message' => "You are already a member of '{$activeTeamForGame->name}' for this game. Please leave that team first.",
                'member' => null,
            ];
        }

        // 5. Verify user is a member of the team's server (only if team has a server)
        // If team has a server and user is not a member, automatically join them
        if ($team->server_id) {
            $isServerMember = $user->servers()
                ->where('servers.id', $team->server_id)
                ->exists();

            if (!$isServerMember) {
                Log::info('TeamService::addMemberToTeam - User not a server member, auto-joining to server', [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'server_id' => $team->server_id,
                ]);

                try {
                    // Automatically join user to the server
                    $server = \App\Models\Server::findOrFail($team->server_id);

                    // Check if server is invite-only
                    if ($server->is_private && !$matchmakingRequest) {
                        // For private servers, only allow via matchmaking
                        Log::warning('TeamService::addMemberToTeam - Cannot join private server without matchmaking', [
                            'user_id' => $user->id,
                            'server_id' => $team->server_id,
                            'server_name' => $server->name,
                        ]);

                        return [
                            'success' => false,
                            'message' => 'This team is on a private server. You need an invite to join.',
                            'member' => null,
                        ];
                    }

                    // Add user to server (check for existing membership first to avoid duplicate key error)
                    $existingServerMembership = DB::table('server_members')
                        ->where('server_id', $server->id)
                        ->where('user_id', $user->id)
                        ->first();

                    if ($existingServerMembership) {
                        // User already has a server membership record - update it
                        DB::table('server_members')
                            ->where('server_id', $server->id)
                            ->where('user_id', $user->id)
                            ->update([
                                'is_banned' => false,
                                'is_muted' => false,
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Create new server membership
                        $server->members()->attach($user->id, [
                            'joined_at' => now(),
                            'is_banned' => false,
                            'is_muted' => false,
                        ]);
                    }

                    // Assign the default Member role if user doesn't have any role in this server
                    $hasAnyRole = $user->roles()
                        ->wherePivot('server_id', $server->id)
                        ->exists();

                    if (!$hasAnyRole) {
                        $memberRole = $server->roles()->where('name', 'Member')->first();
                        if ($memberRole) {
                            $user->roles()->attach($memberRole->id, ['server_id' => $server->id]);
                            Log::info('TeamService::addMemberToTeam - Assigned default Member role to user', [
                                'user_id' => $user->id,
                                'server_id' => $server->id,
                                'role_id' => $memberRole->id,
                            ]);
                        }
                    }

                    Log::info('TeamService::addMemberToTeam - User successfully joined server', [
                        'user_id' => $user->id,
                        'server_id' => $server->id,
                        'server_name' => $server->name,
                    ]);

                } catch (\Exception $e) {
                    Log::error('TeamService::addMemberToTeam - Failed to join user to server', [
                        'user_id' => $user->id,
                        'server_id' => $team->server_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return [
                        'success' => false,
                        'message' => 'Failed to join the team\'s server. Please try again.',
                        'member' => null,
                    ];
                }
            }
        } else {
            Log::info('TeamService::addMemberToTeam - Independent team (no server association)', [
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
        }

        // 6. Prepare member data with skill score from Steam profile
        $skillScore = $this->getUserSkillScore($user, $team->game_appid);
        $skillLevel = $this->getSkillLevel($skillScore);

        $finalMemberData = array_merge([
            'role' => 'member',
            'game_role' => null,
            'skill_level' => $skillLevel,
            'individual_skill_score' => $skillScore,
            'status' => 'active',
        ], $memberData);

        Log::info('TeamService::addMemberToTeam - Prepared member data', [
            'skill_score' => $skillScore,
            'skill_level' => $skillLevel,
            'final_data' => $finalMemberData,
        ]);

        // 7. Use database transaction for data integrity
        DB::beginTransaction();

        try {
            // 8. Add member using Team model method (pass bypass parameter)
            $success = $team->addMember($user, $finalMemberData, $bypassRecruitmentCheck);

            if (!$success) {
                DB::rollBack();

                Log::error('TeamService::addMemberToTeam - Team::addMember returned false', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'bypass_recruitment_check' => $bypassRecruitmentCheck,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to add member to team. Please try again.',
                    'member' => null,
                ];
            }

            // 9. Get the created TeamMember record
            $teamMember = $team->members()->where('user_id', $user->id)->first();

            if (!$teamMember) {
                DB::rollBack();

                Log::error('TeamService::addMemberToTeam - TeamMember not found after creation', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to retrieve member record. Please try again.',
                    'member' => null,
                ];
            }

            // 10. Handle matchmaking request if provided
            if ($matchmakingRequest) {
                Log::info('TeamService::addMemberToTeam - Processing matchmaking request', [
                    'matchmaking_request_id' => $matchmakingRequest->id,
                    'request_type' => $matchmakingRequest->request_type,
                ]);

                // Mark this matchmaking request as matched
                $matchmakingRequest->markAsMatched();

                Log::info('TeamService::addMemberToTeam - Marked request as matched', [
                    'matchmaking_request_id' => $matchmakingRequest->id,
                ]);

                // Cancel other active matchmaking requests for the same game
                $cancelledCount = MatchmakingRequest::where('user_id', $user->id)
                    ->where('game_appid', $team->game_appid)
                    ->where('id', '!=', $matchmakingRequest->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                Log::info('TeamService::addMemberToTeam - Cancelled other active requests', [
                    'user_id' => $user->id,
                    'game_appid' => $team->game_appid,
                    'cancelled_count' => $cancelledCount,
                ]);
            }

            // 11. Commit transaction
            DB::commit();

            Log::info('TeamService::addMemberToTeam - Transaction committed successfully', [
                'team_member_id' => $teamMember->id,
                'team_current_size' => $team->fresh()->current_size,
            ]);

            // 12. Broadcast TeamMemberJoined event for real-time updates
            try {
                event(new TeamMemberJoined($team, $teamMember));

                Log::info('TeamService::addMemberToTeam - Broadcasted TeamMemberJoined event', [
                    'team_id' => $team->id,
                    'team_member_id' => $teamMember->id,
                ]);
            } catch (\Exception $e) {
                // Non-critical error - log but don't fail the operation
                Log::error('TeamService::addMemberToTeam - Failed to broadcast event', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::info('TeamService::addMemberToTeam SUCCESS', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'team_member_id' => $teamMember->id,
                'via_matchmaking' => $matchmakingRequest !== null,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully joined the team!',
                'member' => $teamMember,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamService::addMemberToTeam - Exception during transaction', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while joining the team. Please try again later.',
                'member' => null,
            ];
        }
    }

    /**
     * Remove a member from a team with comprehensive cleanup
     *
     * Performs:
     * - Member removal via Team model method
     * - Team statistics update (current_size, average_skill_score)
     * - Team status update if applicable
     * - Transaction handling for data integrity
     *
     * @param Team $team The team to remove the member from
     * @param User $user The user to remove from the team
     * @return array Returns ['success' => bool, 'message' => string]
     */
    public function removeMemberFromTeam(Team $team, User $user): array
    {
        Log::info('TeamService::removeMemberFromTeam START', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        // 1. Verify user is a member of this team
        $teamMember = $team->members()->where('user_id', $user->id)->first();

        if (!$teamMember) {
            Log::warning('TeamService::removeMemberFromTeam - User not a member', [
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'You are not a member of this team.',
            ];
        }

        // 2. Prevent team creator from leaving (unless disbanding team)
        if ($team->creator_id === $user->id) {
            Log::warning('TeamService::removeMemberFromTeam - Team creator cannot leave', [
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'Team leaders cannot leave their own team. You must disband the team or transfer leadership first.',
            ];
        }

        // 3. Use database transaction
        DB::beginTransaction();

        try {
            // 4. Remove member using Team model method
            $success = $team->removeMember($user);

            if (!$success) {
                DB::rollBack();

                Log::error('TeamService::removeMemberFromTeam - Team::removeMember returned false', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to remove member from team. Please try again.',
                ];
            }

            // 5. Commit transaction
            DB::commit();

            Log::info('TeamService::removeMemberFromTeam SUCCESS', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'new_current_size' => $team->fresh()->current_size,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully left the team.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('TeamService::removeMemberFromTeam - Exception during transaction', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while leaving the team. Please try again later.',
            ];
        }
    }

    /**
     * Get user's skill score for a specific game from Steam profile data
     *
     * Retrieves skill score from:
     * 1. Steam skill metrics (primary source)
     * 2. Gaming preferences with playtime estimation (fallback)
     * 3. Default neutral score of 50 (if no data available)
     *
     * @param User $user The user to get skill score for
     * @param string $gameAppId The Steam app ID of the game
     * @return float Skill score between 0-100
     */
    protected function getUserSkillScore(User $user, string $gameAppId): float
    {
        // Try to get from Steam skill metrics first
        $steamData = $user->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];

        if (isset($skillMetrics[$gameAppId]['skill_score'])) {
            $score = (float) $skillMetrics[$gameAppId]['skill_score'];

            Log::info('TeamService::getUserSkillScore - From Steam metrics', [
                'user_id' => $user->id,
                'game_appid' => $gameAppId,
                'skill_score' => $score,
            ]);

            return $score;
        }

        // Fallback to gaming preferences with playtime estimation
        $preference = $user->gamingPreferences()
            ->where('game_appid', $gameAppId)
            ->first();

        if ($preference && $preference->playtime_forever > 0) {
            // Convert playtime to skill estimate (rough approximation)
            // Formula: Base 30 + (hours / 10), capped at 100, minimum 20
            $playtimeHours = $preference->playtime_forever / 60;
            $estimatedScore = min(100, max(20, 30 + ($playtimeHours / 10)));

            Log::info('TeamService::getUserSkillScore - Estimated from playtime', [
                'user_id' => $user->id,
                'game_appid' => $gameAppId,
                'playtime_hours' => $playtimeHours,
                'estimated_score' => $estimatedScore,
            ]);

            return $estimatedScore;
        }

        // Default neutral score
        Log::info('TeamService::getUserSkillScore - Using default score', [
            'user_id' => $user->id,
            'game_appid' => $gameAppId,
            'default_score' => 50,
        ]);

        return 50.0;
    }

    /**
     * Convert numeric skill score (0-100) to skill level enum
     *
     * Skill level brackets:
     * - expert: 80-100
     * - advanced: 60-79
     * - intermediate: 40-59
     * - beginner: 0-39
     *
     * @param float $skillScore Numeric skill score between 0-100
     * @return string Skill level enum value (beginner|intermediate|advanced|expert)
     */
    protected function getSkillLevel(float $skillScore): string
    {
        return match(true) {
            $skillScore >= 80 => 'expert',
            $skillScore >= 60 => 'advanced',
            $skillScore >= 40 => 'intermediate',
            default => 'beginner'
        };
    }
}
