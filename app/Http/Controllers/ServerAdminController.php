<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Channel;
use App\Models\Role;
use App\Models\User;
use App\Models\ChannelPermissionOverride;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ServerAdminController extends Controller
{
    public function settings(Server $server)
    {
        Gate::authorize('manage', $server);
        
        $server->load(['channels', 'members.profile', 'roles', 'tags', 'goals']);
        
        return view('servers.admin.settings', compact('server'));
    }

    public function update(Request $request, Server $server)
    {
        Gate::authorize('manage', $server);
        
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $server->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Server settings updated successfully!');
    }

    public function createChannel(Request $request, Server $server)
    {
        Gate::authorize('manageChannels', $server);
        
        $request->validate([
            'name' => 'required|string|min:2|max:30|regex:/^[a-z0-9-]+$/',
            'type' => 'required|in:text,voice',
        ]);

        $position = $server->channels()->where('type', $request->type)->max('position') + 1;

        $server->channels()->create([
            'name' => $request->name,
            'type' => $request->type,
            'position' => $position,
        ]);

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Channel created successfully!')
            ->with('active_tab', 'channels');
    }

    public function updateChannel(Request $request, Server $server, Channel $channel)
    {
        Gate::authorize('manageChannels', $server);
        
        if ($channel->server_id !== $server->id) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|min:2|max:30|regex:/^[a-z0-9-]+$/',
            'type' => 'required|in:text,voice',
        ]);

        $channel->update([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Channel updated successfully!')
            ->with('active_tab', 'channels');
    }

    public function deleteChannel(Server $server, Channel $channel)
    {
        Gate::authorize('manageChannels', $server);
        
        if ($channel->server_id !== $server->id) {
            abort(404);
        }

        // Don't delete the last text channel
        if ($channel->type === 'text' && $server->channels()->where('type', 'text')->count() <= 1) {
            return redirect()->route('server.admin.settings', $server)
                ->with('error', 'Cannot delete the last text channel.')
                ->with('active_tab', 'channels');
        }

        $channel->delete();

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Channel deleted successfully!')
            ->with('active_tab', 'channels');
    }

    public function kickMember(Server $server, User $member)
    {
        Gate::authorize('kickMembers', $server);

        // Can't kick yourself
        if ($member->id === auth()->id()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot kick yourself.');
        }

        // Can't kick the server creator
        if ($member->id === $server->creator_id) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'Cannot kick the server creator.');
        }

        // Role hierarchy check: can only kick members with lower roles
        if (!auth()->user()->canManageUser($member, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot kick members with equal or higher roles.');
        }

        DB::transaction(function () use ($server, $member) {
            // Remove from server
            $server->members()->detach($member->id);
            
            // Remove all roles
            $member->roles()->wherePivot('server_id', $server->id)->detach();
        });

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Member kicked successfully!')
            ->with('active_tab', 'members');
    }

    public function banMember(Server $server, User $member)
    {
        Gate::authorize('banMembers', $server);

        // Can't ban yourself
        if ($member->id === auth()->id()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot ban yourself.');
        }

        // Can't ban the server creator
        if ($member->id === $server->creator_id) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'Cannot ban the server creator.');
        }

        // Role hierarchy check: can only ban members with lower roles
        if (!auth()->user()->canManageUser($member, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot ban members with equal or higher roles.');
        }

        DB::transaction(function () use ($server, $member) {
            // Update membership to banned
            $server->members()->updateExistingPivot($member->id, [
                'is_banned' => true,
            ]);
            
            // Remove all roles
            $member->roles()->wherePivot('server_id', $server->id)->detach();
        });

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('success', 'Member banned successfully!');
    }

    public function unbanMember(Server $server, User $member)
    {
        Gate::authorize('banMembers', $server);
        
        $server->members()->updateExistingPivot($member->id, [
            'is_banned' => false,
        ]);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('success', 'Member unbanned successfully!');
    }

    public function muteMember(Server $server, User $member)
    {
        Gate::authorize('muteMembers', $server);

        // Can't mute yourself
        if ($member->id === auth()->id()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot mute yourself.');
        }

        // Can't mute the server creator
        if ($member->id === $server->creator_id) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'Cannot mute the server creator.');
        }

        // Role hierarchy check: can only mute members with lower roles
        if (!auth()->user()->canManageUser($member, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot mute members with equal or higher roles.');
        }

        $server->members()->updateExistingPivot($member->id, [
            'is_muted' => true,
        ]);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('success', 'Member muted successfully!');
    }

    public function unmuteMember(Server $server, User $member)
    {
        Gate::authorize('muteMembers', $server);

        // Can't unmute yourself (shouldn't happen in practice but be consistent)
        if ($member->id === auth()->id()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot unmute yourself.');
        }

        // Role hierarchy check: can only unmute members with lower roles
        if (!auth()->user()->canManageUser($member, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('error', 'You cannot unmute members with equal or higher roles.');
        }

        $server->members()->updateExistingPivot($member->id, [
            'is_muted' => false,
        ]);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('success', 'Member unmuted successfully!');
    }

    public function createRole(Request $request, Server $server)
    {
        Gate::authorize('manageRoles', $server);

        $request->validate([
            'name' => 'required|string|min:2|max:30',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $user = auth()->user();

        // Determine the position for the new role
        // Non-creators can only create roles below their highest role
        if ($server->creator_id === $user->id) {
            // Server creator can create roles at any position below Server Admin
            $position = $server->roles()->where('name', '!=', 'Server Admin')->max('position') + 1;
        } else {
            // Non-creators: new role position must be below their highest role position
            $userPosition = $user->getHighestRolePosition($server->id);
            // Place new role just above Member role (position 0) but below user's highest role
            $position = max(1, $userPosition - 1);
            // Ensure position is valid (above 0, below user's highest)
            if ($position >= $userPosition) {
                $position = $userPosition - 1;
            }
            if ($position < 1) {
                return redirect()->route('server.admin.settings', $server)
                    ->with('error', 'Cannot create role: insufficient role hierarchy position.')
                    ->with('active_tab', 'roles');
            }
        }

        $server->roles()->create([
            'name' => $request->name,
            'color' => $request->color,
            'position' => $position,
            'permissions' => ['send_messages', 'view_channels'],
        ]);

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Role created successfully!')
            ->with('active_tab', 'roles');
    }

    public function assignRole(Request $request, Server $server)
    {
        Gate::authorize('manageRoles', $server);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = $server->roles()->findOrFail($request->role_id);
        $targetUser = $server->members()->findOrFail($request->user_id);
        $currentUser = auth()->user();

        // Don't allow changing Server Admin role
        if ($role->name === 'Server Admin') {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot manually assign Server Admin role.');
        }

        // Role hierarchy check: can only assign roles below your highest role
        if (!$currentUser->canManageRole($role, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot assign roles at or above your level.');
        }

        // User hierarchy check: can only assign roles to users with lower role positions
        if (!$currentUser->canManageUser($targetUser, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot manage roles for users with equal or higher roles.');
        }

        // Check if user already has this role
        if ($targetUser->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'User already has this role.');
        }

        $targetUser->roles()->attach($role->id, ['server_id' => $server->id]);

        // Invalidate permission cache for the target user
        $targetUser->invalidatePermissionCache($server->id);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('success', 'Role assigned successfully!');
    }

    public function removeRole(Server $server, User $targetUser, Role $role)
    {
        Gate::authorize('manageRoles', $server);

        $currentUser = auth()->user();

        // Don't allow removing Server Admin role
        if ($role->name === 'Server Admin') {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot remove Server Admin role.');
        }

        // Role hierarchy check: can only remove roles below your highest role
        if (!$currentUser->canManageRole($role, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot manage roles at or above your level.');
        }

        // User hierarchy check: can only remove roles from users with lower role positions
        if (!$currentUser->canManageUser($targetUser, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot manage roles for users with equal or higher roles.');
        }

        $targetUser->roles()->wherePivot('server_id', $server->id)->detach($role->id);

        // Invalidate permission cache for the target user
        $targetUser->invalidatePermissionCache($server->id);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('success', 'Role removed successfully!');
    }

    public function updateRole(Request $request, Server $server, Role $role)
    {
        Gate::authorize('manageRoles', $server);

        $currentUser = auth()->user();

        // Verify the role belongs to this server
        if ($role->server_id !== $server->id) {
            abort(404);
        }

        // Don't allow editing default roles
        if (in_array($role->name, ['Server Admin', 'Member'])) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot edit default roles.');
        }

        // Role hierarchy check: can only edit roles below your highest role
        if (!$currentUser->canManageRole($role, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot edit roles at or above your level.');
        }

        $request->validate([
            'name' => 'required|string|min:2|max:30',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
        ]);

        $role->update([
            'name' => $request->name,
            'color' => $request->color,
        ]);

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Role updated successfully!')
            ->with('active_tab', 'roles');
    }

    public function deleteRole(Server $server, Role $role)
    {
        Gate::authorize('manageRoles', $server);

        $currentUser = auth()->user();

        // Verify the role belongs to this server
        if ($role->server_id !== $server->id) {
            abort(404);
        }

        // Don't allow deleting default roles
        if (in_array($role->name, ['Server Admin', 'Member'])) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot delete default roles.');
        }

        // Role hierarchy check: can only delete roles below your highest role
        if (!$currentUser->canManageRole($role, $server->id)) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'You cannot delete roles at or above your level.');
        }

        // Get users who have this role (to invalidate their caches)
        $affectedUserIds = $role->users()->wherePivot('server_id', $server->id)->pluck('users.id');

        // Remove role from all users first
        $role->users()->wherePivot('server_id', $server->id)->detach();

        // Delete the role
        $role->delete();

        // Invalidate permission caches for affected users
        foreach ($affectedUserIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->invalidatePermissionCache($server->id);
            }
        }

        return redirect()->route('server.admin.settings', $server)
            ->with('success', 'Role deleted successfully!')
            ->with('active_tab', 'roles');
    }

    public function addTag(Request $request, Server $server)
    {
        Gate::authorize('manage', $server);
        
        $request->validate([
            'tag_type' => 'required|in:game,skill_level,region,language,activity_time',
            'tag_value' => 'required|string|max:100',
        ]);

        // Validate tag values based on type
        $validValues = [
            'game' => ['cs2', 'dota2', 'warframe', 'apex_legends', 'rust', 'pubg', 'rainbow_six_siege', 'fall_guys', 'valorant', 'overwatch', 'league_of_legends', 'minecraft'],
            'skill_level' => ['beginner', 'intermediate', 'advanced', 'expert', 'casual', 'competitive'],
            'region' => ['na_east', 'na_west', 'eu_west', 'eu_east', 'asia', 'oceania', 'south_america', 'africa'],
            'language' => ['english', 'spanish', 'french', 'german', 'russian', 'chinese', 'japanese', 'korean', 'portuguese', 'italian'],
            'activity_time' => ['morning', 'afternoon', 'evening', 'late_night', 'weekends', 'weekdays', '24_7']
        ];

        if (!in_array($request->tag_value, $validValues[$request->tag_type])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid tag value for the selected tag type.'
                ], 422);
            }
            
            return back()->withErrors(['tag_value' => 'Invalid tag value for the selected tag type.']);
        }

        try {
            $server->addTag($request->tag_type, $request->tag_value);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tag added successfully!'
                ]);
            }

            return redirect()->route('server.admin.settings', $server)
                ->with('success', 'Tag added successfully!')
                ->with('active_tab', 'tags');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding tag: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('server.admin.settings', $server)
                ->with('error', 'Error adding tag: ' . $e->getMessage())
                ->with('active_tab', 'tags');
        }
    }

    public function removeTag(Request $request, Server $server, $tagId = null)
    {
        Gate::authorize('manage', $server);
        
        try {
            if ($tagId) {
                // Remove by tag ID (for AJAX requests)
                $tag = $server->tags()->findOrFail($tagId);
                $tag->delete();
            } else {
                // Remove by tag type and value (for form requests)
                $request->validate([
                    'tag_type' => 'required|string',
                    'tag_value' => 'required|string',
                ]);
                
                $server->removeTag($request->tag_type, $request->tag_value);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tag removed successfully!'
                ]);
            }

            return redirect()->route('server.admin.settings', $server)
                ->with('success', 'Tag removed successfully!')
                ->with('active_tab', 'tags');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error removing tag: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('server.admin.settings', $server)
                ->with('error', 'Error removing tag: ' . $e->getMessage())
                ->with('active_tab', 'tags');
        }
    }

    public function getTagSuggestions(Server $server)
    {
        $suggestionService = app(\App\Services\ServerRecommendationService::class);
        $suggestions = $suggestionService->suggestTagsForServer($server);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Update permissions for a role.
     * Users can only grant permissions they themselves have.
     *
     * @param Request $request
     * @param Server $server
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRolePermissions(Request $request, Server $server, Role $role)
    {
        Gate::authorize('manageRoles', $server);

        $user = auth()->user();
        $permissionService = app(PermissionService::class);

        // Verify the role belongs to this server
        if ($role->server_id !== $server->id) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // Cannot edit protected roles (Server Admin, Member)
        if ($role->isProtected()) {
            return response()->json(['error' => 'Cannot edit default roles'], 403);
        }

        // Role hierarchy check: can only edit roles below your highest role
        if (!$user->canManageRole($role, $server->id)) {
            return response()->json(['error' => 'Cannot manage roles at or above your level'], 403);
        }

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|in:' . implode(',', config('permissions.all', [])),
        ]);

        // Non-creators can only grant permissions they have themselves
        if ($server->creator_id !== $user->id) {
            $userPermissions = $permissionService->getUserPermissions($user, $server);

            // If user doesn't have administrator, check each permission
            if (!in_array('administrator', $userPermissions)) {
                foreach ($request->permissions ?? [] as $permission) {
                    if (!in_array($permission, $userPermissions)) {
                        return response()->json([
                            'error' => "Cannot grant permission '{$permission}' that you don't have"
                        ], 403);
                    }
                }
            }
        }

        $role->setPermissions($request->permissions ?? []);
        $permissionService->invalidateServerCache($server);

        return response()->json(['success' => true, 'message' => 'Permissions updated successfully']);
    }

    /**
     * Update channel permission overrides.
     * Allows per-channel permission customization for roles.
     *
     * @param Request $request
     * @param Server $server
     * @param Channel $channel
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateChannelOverrides(Request $request, Server $server, Channel $channel)
    {
        Gate::authorize('manageChannels', $server);

        // Verify the channel belongs to this server
        if ($channel->server_id !== $server->id) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $request->validate([
            'overrides' => 'array',
            'overrides.*.role_id' => 'required|exists:roles,id',
            'overrides.*.permission' => 'required|string|in:' . implode(',', config('permissions.all', [])),
            'overrides.*.value' => 'required|in:allow,deny,inherit',
        ]);

        // Validate that all role_ids belong to this server
        $serverRoleIds = $server->roles()->pluck('id')->toArray();
        foreach ($request->overrides ?? [] as $override) {
            if (!in_array($override['role_id'], $serverRoleIds)) {
                return response()->json(['error' => 'Invalid role for this server'], 400);
            }
        }

        // Apply overrides
        foreach ($request->overrides ?? [] as $override) {
            ChannelPermissionOverride::updateOrCreate(
                [
                    'channel_id' => $channel->id,
                    'role_id' => $override['role_id'],
                    'permission' => $override['permission'],
                ],
                ['value' => $override['value']]
            );
        }

        // Remove 'inherit' overrides (they're the default, no need to store)
        ChannelPermissionOverride::where('channel_id', $channel->id)
            ->where('value', 'inherit')
            ->delete();

        return response()->json(['success' => true, 'message' => 'Channel permissions updated']);
    }
}