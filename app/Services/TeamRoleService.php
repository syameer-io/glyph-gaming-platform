<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\PlayerGameRole;
use Illuminate\Support\Facades\Log;

class TeamRoleService
{
    /**
     * Get available roles for a specific game
     *
     * @param string $gameAppId Steam App ID
     * @return array Role configuration with display names and descriptions
     */
    public function getAvailableRolesForGame(string $gameAppId): array
    {
        $gameConfig = config("game_roles.games.{$gameAppId}");

        if ($gameConfig) {
            return [
                'game_id' => $gameAppId,
                'game_name' => $gameConfig['name'],
                'roles' => $gameConfig['roles'],
                'display_names' => $gameConfig['display_names'],
                'descriptions' => $gameConfig['descriptions'] ?? [],
            ];
        }

        // Fallback to generic roles
        $generic = config('game_roles.generic');
        return [
            'game_id' => $gameAppId,
            'game_name' => 'Generic',
            'roles' => $generic['roles'],
            'display_names' => $generic['display_names'],
            'descriptions' => $generic['descriptions'] ?? [],
        ];
    }

    /**
     * Get available roles for a team, including which roles are required/filled
     *
     * @param Team $team
     * @return array Detailed role options for the team
     */
    public function getAvailableRolesForTeam(Team $team): array
    {
        $gameRoles = $this->getAvailableRolesForGame($team->game_appid);
        $requiredRoles = $team->required_roles ?? $team->getIdealGameComposition();
        $filledRoles = $team->getFilledRoles();

        // Get current assignments
        $assignments = [];
        foreach ($team->activeMembers()->with('user')->get() as $member) {
            if ($member->game_role) {
                $assignments[$member->game_role] = [
                    'member_id' => $member->id,
                    'user_name' => $member->user->display_name ?? $member->user->name,
                ];
            }
        }

        // Build role options with metadata
        $roleOptions = [];
        foreach ($gameRoles['roles'] as $role) {
            $isFilled = isset($assignments[$role]);
            $roleOptions[] = [
                'value' => $role,
                'display_name' => $gameRoles['display_names'][$role] ?? ucfirst(str_replace('_', ' ', $role)),
                'description' => $gameRoles['descriptions'][$role] ?? null,
                'is_required' => in_array($role, $requiredRoles),
                'is_filled' => $isFilled,
                'filled_by' => $isFilled ? $assignments[$role]['user_name'] : null,
                'filled_by_member_id' => $isFilled ? $assignments[$role]['member_id'] : null,
            ];
        }

        return [
            'game_id' => $team->game_appid,
            'game_name' => $gameRoles['game_name'],
            'team_required_roles' => $requiredRoles,
            'available_roles' => $roleOptions,
        ];
    }

    /**
     * Get role options for a specific team member
     *
     * Includes preferred role highlighting for the member
     *
     * @param Team $team
     * @param TeamMember $member
     * @return array Role options with member-specific preferences
     */
    public function getMemberRoleOptions(Team $team, TeamMember $member): array
    {
        $teamRoles = $this->getAvailableRolesForTeam($team);
        $preferredRoles = $member->preferred_roles;

        // Enhance role options with member preferences
        $roleOptions = [];
        $preferredOptions = [];
        $otherOptions = [];

        foreach ($teamRoles['available_roles'] as $role) {
            $isPreferred = in_array($role['value'], $preferredRoles);
            $isCurrent = $member->game_role === $role['value'];

            $enhancedRole = array_merge($role, [
                'is_preferred' => $isPreferred,
                'is_current' => $isCurrent,
            ]);

            // Separate into preferred and other categories for UI
            if ($isPreferred) {
                $preferredOptions[] = $enhancedRole;
            } else {
                $otherOptions[] = $enhancedRole;
            }

            $roleOptions[] = $enhancedRole;
        }

        return [
            'game_id' => $team->game_appid,
            'game_name' => $teamRoles['game_name'],
            'member' => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'user_name' => $member->user->display_name ?? $member->user->name,
                'current_role' => $member->game_role,
                'current_role_display' => $member->getGameRoleDisplayName($team->game_appid),
                'preferred_roles' => $preferredRoles,
            ],
            'team_required_roles' => $teamRoles['team_required_roles'],
            'available_roles' => $roleOptions,
            'preferred_roles' => $preferredOptions,
            'other_roles' => $otherOptions,
        ];
    }

    /**
     * Assign a game role to a team member
     *
     * @param Team $team
     * @param TeamMember $member
     * @param string $role The role to assign
     * @param User $assignedBy The user performing the assignment
     * @return array Result with updated member and coverage info
     * @throws \InvalidArgumentException If role is invalid for the game
     */
    public function assignRoleToMember(Team $team, TeamMember $member, string $role, User $assignedBy): array
    {
        // Validate role is valid for this game
        if (!$this->validateRoleForGame($role, $team->game_appid)) {
            throw new \InvalidArgumentException(
                "The role '{$role}' is not valid for {$team->game_name}."
            );
        }

        // Assign the role
        $member->assignRole($role, $assignedBy->id);
        $member->refresh();

        // Get updated coverage
        $team->refresh();
        $coverage = $team->getRoleCoverageDetails();

        // Check if this role is in the required roles
        $requiredRoles = $team->required_roles ?? $team->getIdealGameComposition();
        $isRequiredRole = in_array($role, $requiredRoles);

        // Build warnings
        $warnings = [];
        if (!$isRequiredRole) {
            $warnings[] = "Note: This role is not in the team's required roles list.";
        }
        if (!$member->isPreferredRole($role)) {
            $warnings[] = "Note: This is not one of the member's preferred roles.";
        }

        Log::info('Role assigned to team member', [
            'team_id' => $team->id,
            'member_id' => $member->id,
            'user_id' => $member->user_id,
            'role' => $role,
            'assigned_by' => $assignedBy->id,
            'is_required_role' => $isRequiredRole,
            'is_preferred_role' => $member->isPreferredRole($role),
        ]);

        return [
            'success' => true,
            'member' => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'game_role' => $member->game_role,
                'game_role_display' => $member->getGameRoleDisplayName($team->game_appid),
                'is_preferred' => $member->isPreferredRole($role),
                'is_required' => $isRequiredRole,
                'member_data' => $member->member_data,
            ],
            'role_coverage' => [
                'percent' => $coverage['coverage_percent'],
                'filled' => $coverage['filled_count'],
                'required' => $coverage['required_count'],
                'unfilled_roles' => $coverage['unfilled_roles'],
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * Clear a member's assigned role
     *
     * @param Team $team
     * @param TeamMember $member
     * @return array Result with updated coverage info
     */
    public function clearMemberRole(Team $team, TeamMember $member): array
    {
        $member->clearRole();
        $member->refresh();

        $team->refresh();
        $coverage = $team->getRoleCoverageDetails();

        return [
            'success' => true,
            'member' => [
                'id' => $member->id,
                'game_role' => null,
                'game_role_display' => 'Unassigned',
            ],
            'role_coverage' => [
                'percent' => $coverage['coverage_percent'],
                'filled' => $coverage['filled_count'],
                'required' => $coverage['required_count'],
                'unfilled_roles' => $coverage['unfilled_roles'],
            ],
        ];
    }

    /**
     * Capture user's preferred roles when they join a team
     *
     * Pulls from PlayerGameRole for the team's game
     *
     * @param Team $team
     * @param User $user
     * @return array Preferred roles to store in member_data
     */
    public function capturePreferredRolesAtJoin(Team $team, User $user): array
    {
        $playerGameRole = PlayerGameRole::where('user_id', $user->id)
            ->where('game_appid', $team->game_appid)
            ->first();

        if (!$playerGameRole) {
            Log::debug('No PlayerGameRole found for user joining team', [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'game_appid' => $team->game_appid,
            ]);

            return [
                'preferred_roles' => [],
                'source' => 'none',
            ];
        }

        // Collect all role preferences
        $roles = [];

        // Add primary and secondary roles
        if ($playerGameRole->primary_role) {
            $roles[] = $playerGameRole->primary_role;
        }
        if ($playerGameRole->secondary_role) {
            $roles[] = $playerGameRole->secondary_role;
        }

        // Add additional preferred roles from the array field
        if (!empty($playerGameRole->preferred_roles) && is_array($playerGameRole->preferred_roles)) {
            $roles = array_merge($roles, $playerGameRole->preferred_roles);
        }

        // Remove duplicates and empty values
        $roles = array_values(array_unique(array_filter($roles)));

        Log::debug('Captured preferred roles for user joining team', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'roles' => $roles,
        ]);

        return [
            'preferred_roles' => $roles,
            'source' => 'player_game_role',
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate that a role is valid for a specific game
     *
     * @param string $role
     * @param string $gameAppId
     * @return bool
     */
    public function validateRoleForGame(string $role, string $gameAppId): bool
    {
        $gameConfig = config("game_roles.games.{$gameAppId}");

        if ($gameConfig && in_array($role, $gameConfig['roles'])) {
            return true;
        }

        // Check generic roles
        $genericRoles = config('game_roles.generic.roles', []);
        if (in_array($role, $genericRoles)) {
            return true;
        }

        // Check all_roles as final fallback
        $allRoles = config('game_roles.all_roles', []);
        return in_array($role, $allRoles);
    }

    /**
     * Get display name for a role
     *
     * @param string $role
     * @param string|null $gameAppId
     * @return string
     */
    public function getRoleDisplayName(string $role, ?string $gameAppId = null): string
    {
        if ($gameAppId) {
            $gameConfig = config("game_roles.games.{$gameAppId}");
            if ($gameConfig && isset($gameConfig['display_names'][$role])) {
                return $gameConfig['display_names'][$role];
            }
        }

        // Fallback to generic
        $genericNames = config('game_roles.generic.display_names', []);
        if (isset($genericNames[$role])) {
            return $genericNames[$role];
        }

        return ucfirst(str_replace('_', ' ', $role));
    }
}
