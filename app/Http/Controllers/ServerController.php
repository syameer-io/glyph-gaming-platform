<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Channel;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServerController extends Controller
{
    public function create()
    {
        return view('servers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            $user = Auth::user();
            
            // Create server
            $server = Server::create([
                'name' => $request->name,
                'description' => $request->description,
                'creator_id' => $user->id,
                'icon_url' => 'https://ui-avatars.com/api/?name=' . urlencode($request->name) . '&background=5865F2&color=fff',
            ]);

            // Create default channels
            $channels = [
                ['name' => 'general', 'type' => 'text', 'position' => 0],
                ['name' => 'announcements', 'type' => 'text', 'position' => 1],
                ['name' => 'voice-chat', 'type' => 'voice', 'position' => 2],
            ];

            foreach ($channels as $channelData) {
                $server->channels()->create($channelData);
            }

            // Create Server Admin role
            $adminRole = Role::create([
                'server_id' => $server->id,
                'name' => 'Server Admin',
                'color' => '#E91E63',
                'position' => 100,
                'permissions' => [
                    'manage_server',
                    'manage_channels',
                    'manage_roles',
                    'manage_members',
                    'kick_members',
                    'ban_members',
                    'mute_members',
                    'send_messages',
                    'view_channels',
                ],
            ]);

            // Create Member role
            $memberRole = Role::create([
                'server_id' => $server->id,
                'name' => 'Member',
                'color' => '#99AAB5',
                'position' => 0,
                'permissions' => [
                    'send_messages',
                    'view_channels',
                ],
            ]);

            // Add creator as server member with admin role
            $server->members()->attach($user->id, [
                'joined_at' => now(),
                'is_banned' => false,
                'is_muted' => false,
            ]);

            $user->roles()->attach($adminRole->id, ['server_id' => $server->id]);
        });

        return redirect()->route('dashboard')->with('success', 'Server created successfully!');
    }

    public function show(Server $server)
    {
        $user = Auth::user();
        
        // Check if user is a member and get membership status in one query
        $membership = $server->members()->where('user_id', $user->id)->first();
        
        if (!$membership) {
            return redirect()->route('dashboard')->with('error', 'You are not a member of this server.');
        }

        // Check if user is banned
        if ($membership->pivot->is_banned) {
            return redirect()->route('dashboard')->with('error', 'You are banned from this server.');
        }

        $server->load(['channels', 'members.profile', 'members.roles' => function ($query) use ($server) {
            $query->where('user_roles.server_id', $server->id);
        }]);

        $defaultChannel = $server->getDefaultChannel();

        return view('servers.show', compact('server', 'defaultChannel'));
    }

    public function join(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|size:8',
        ]);

        $server = Server::where('invite_code', $request->invite_code)->first();

        if (!$server) {
            return back()->with('error', 'Invalid invite code.');
        }

        $user = Auth::user();

        // Check if already a member
        if ($server->members->contains($user->id)) {
            return redirect()->route('server.show', $server)->with('info', 'You are already a member of this server.');
        }

        // Check if banned
        $membership = $server->members()->where('user_id', $user->id)->first();
        if ($membership && $membership->pivot->is_banned) {
            return back()->with('error', 'You are banned from this server.');
        }

        // Add user as member
        $server->members()->attach($user->id, [
            'joined_at' => now(),
            'is_banned' => false,
            'is_muted' => false,
        ]);

        // Assign default member role
        $memberRole = $server->roles()->where('name', 'Member')->first();
        if ($memberRole) {
            $user->roles()->attach($memberRole->id, ['server_id' => $server->id]);
        }

        return redirect()->route('server.show', $server)->with('success', 'Successfully joined the server!');
    }

    public function leave(Server $server)
    {
        $user = Auth::user();
        
        // Check if user is a member
        if (!$server->members->contains($user->id)) {
            return redirect()->route('dashboard')->with('error', 'You are not a member of this server.');
        }
        
        // Server creator cannot leave their own server
        if ($server->creator_id === $user->id) {
            return back()->with('error', 'Server creator cannot leave their own server.');
        }
        
        // Remove user from server (this will also remove roles due to foreign key constraints)
        $server->members()->detach($user->id);
        
        // Remove all roles for this user in this server
        $user->roles()->wherePivot('server_id', $server->id)->detach();
        
        return redirect()->route('dashboard')->with('success', 'Successfully left the server.');
    }

    public function destroy(Server $server)
    {
        $user = Auth::user();
        
        // Only the server creator can delete the server
        if ($server->creator_id !== $user->id) {
            return back()->with('error', 'Only the server creator can delete the server.');
        }
        
        // Store server name for success message
        $serverName = $server->name;
        
        // Use database transaction for safety
        DB::transaction(function () use ($server) {
            // Delete the server (cascade deletes will handle related data)
            // This will automatically delete:
            // - All channels and their messages (cascade via channels)
            // - All server members (cascade via server_members)
            // - All server roles and user_roles entries (cascade via roles)
            // - Any other related data with foreign key constraints
            $server->delete();
        });
        
        return redirect()->route('dashboard')->with('success', "Server '{$serverName}' has been deleted successfully.");
    }

    public function showJoinPage()
    {
        return view('servers.join');
    }
}