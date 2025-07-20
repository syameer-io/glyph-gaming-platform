<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSteamId extends Command
{
    protected $signature = 'steam:clear-id {user_id? : The user ID to clear Steam ID for}';
    protected $description = 'Clear Steam ID from user account for testing';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        } else {
            // Get all users with Steam IDs
            $users = User::whereNotNull('steam_id')->get();
            
            if ($users->isEmpty()) {
                $this->info('No users with Steam IDs found.');
                return 0;
            }
            
            $this->info('Users with Steam IDs:');
            foreach ($users as $user) {
                $this->line("ID: {$user->id}, Username: {$user->username}, Steam ID: {$user->steam_id}");
            }
            
            $userId = $this->ask('Enter the user ID to clear Steam ID for');
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        }
        
        $steamId = $user->steam_id;
        
        if (!$steamId) {
            $this->info("User {$user->username} doesn't have a Steam ID linked.");
            return 0;
        }
        
        // Clear Steam ID
        $user->update(['steam_id' => null]);
        
        // Clear cached Steam data
        Cache::forget("steam_data_{$steamId}");
        
        // Clear steam_data from profile if it exists
        if ($user->profile) {
            $user->profile->update(['steam_data' => null]);
        }
        
        $this->info("Steam ID cleared for user {$user->username}. You can now test Steam linking again.");
        
        return 0;
    }
}