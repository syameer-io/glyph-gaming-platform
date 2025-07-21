<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Channel;
use App\Models\Role;
use App\Models\User;
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
        Gate::authorize('manageMembers', $server);
        
        $server->members()->updateExistingPivot($member->id, [
            'is_muted' => true,
        ]);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'members')->with('success', 'Member muted successfully!');
    }

    public function unmuteMember(Server $server, User $member)
    {
        Gate::authorize('manageMembers', $server);
        
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

        $position = $server->roles()->where('name', '!=', 'Server Admin')->max('position') + 1;

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
        $user = $server->members()->findOrFail($request->user_id);

        // Don't allow changing Server Admin role
        if ($role->name === 'Server Admin') {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot manually assign Server Admin role.');
        }

        // Check if user already has this role
        if ($user->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'User already has this role.');
        }

        $user->roles()->attach($role->id, ['server_id' => $server->id]);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('success', 'Role assigned successfully!');
    }

    public function removeRole(Server $server, User $user, Role $role)
    {
        Gate::authorize('manageRoles', $server);
        
        // Don't allow removing Server Admin role
        if ($role->name === 'Server Admin') {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot remove Server Admin role.');
        }

        $user->roles()->wherePivot('server_id', $server->id)->detach($role->id);

        return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('success', 'Role removed successfully!');
    }

    public function updateRole(Request $request, Server $server, Role $role)
    {
        Gate::authorize('manageRoles', $server);
        
        // Verify the role belongs to this server
        if ($role->server_id !== $server->id) {
            abort(404);
        }
        
        // Don't allow editing default roles
        if (in_array($role->name, ['Server Admin', 'Member'])) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot edit default roles.');
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
        
        // Verify the role belongs to this server
        if ($role->server_id !== $server->id) {
            abort(404);
        }
        
        // Don't allow deleting default roles
        if (in_array($role->name, ['Server Admin', 'Member'])) {
            return redirect()->route('server.admin.settings', $server)->with('active_tab', 'roles')->with('error', 'Cannot delete default roles.');
        }
        
        // Remove role from all users first
        $role->users()->wherePivot('server_id', $server->id)->detach();
        
        // Delete the role
        $role->delete();

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
}