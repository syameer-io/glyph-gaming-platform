<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\Channel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::first();
        
        // Create a test server
        $server = Server::create([
            'name' => 'Gaming Community Hub',
            'description' => 'A place for gamers to connect and play together',
            'creator_id' => $creator->id,
            'icon_url' => 'https://ui-avatars.com/api/?name=GCH&background=5865F2&color=fff',
            'invite_code' => Server::generateUniqueInviteCode(),
        ]);

        // Create default channels
        $channels = [
            ['name' => 'general', 'type' => 'text', 'position' => 0],
            ['name' => 'gaming-chat', 'type' => 'text', 'position' => 1],
            ['name' => 'voice-lobby', 'type' => 'voice', 'position' => 2],
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
        $server->members()->attach($creator->id, [
            'joined_at' => now(),
            'is_banned' => false,
            'is_muted' => false,
        ]);

        $creator->roles()->attach($adminRole->id, ['server_id' => $server->id]);

        // Add other users as members
        $otherUsers = User::where('id', '!=', $creator->id)->get();
        foreach ($otherUsers as $user) {
            $server->members()->attach($user->id, [
                'joined_at' => now(),
                'is_banned' => false,
                'is_muted' => false,
            ]);
            $user->roles()->attach($memberRole->id, ['server_id' => $server->id]);
        }
    }
}