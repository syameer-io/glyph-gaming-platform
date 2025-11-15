<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing CS2 lobby links from profiles table to game_lobbies table.
     */
    public function up(): void
    {
        // First, check if profiles table has steam_lobby_link column
        if (!Schema::hasColumn('profiles', 'steam_lobby_link')) {
            return; // Nothing to migrate
        }

        // Get CS2 game_appid (730)
        $cs2AppId = 730;

        // Migrate existing CS2 lobby links
        $migratedCount = 0;

        $profiles = DB::table('profiles')
            ->whereNotNull('steam_lobby_link')
            ->where('steam_lobby_link', '!=', '')
            ->get();

        foreach ($profiles as $profile) {
            // Parse Steam lobby link
            // Format: steam://joinlobby/730/{lobby_id}/{profile_id}
            if (preg_match('/^steam:\/\/joinlobby\/(\d+)\/(\d+)\/(\d+)$/', $profile->steam_lobby_link, $matches)) {
                $steamAppId = (int) $matches[1];
                $steamLobbyId = $matches[2];
                $steamProfileId = $matches[3];

                // Only migrate CS2 lobbies (app_id 730)
                if ($steamAppId !== $cs2AppId) {
                    continue;
                }

                // Calculate expiration (30 minutes from when it was last updated)
                $updatedAt = $profile->steam_lobby_link_updated_at
                    ? \Carbon\Carbon::parse($profile->steam_lobby_link_updated_at)
                    : now();

                $expiresAt = $updatedAt->copy()->addMinutes(30);
                $isActive = $expiresAt->isFuture();

                // Insert into game_lobbies table
                DB::table('game_lobbies')->insert([
                    'user_id' => $profile->user_id,
                    'game_id' => $cs2AppId,
                    'join_method' => 'steam_lobby',
                    'steam_app_id' => $steamAppId,
                    'steam_lobby_id' => $steamLobbyId,
                    'steam_profile_id' => $steamProfileId,
                    'is_active' => $isActive,
                    'created_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                    'expires_at' => $expiresAt,
                ]);

                $migratedCount++;
            }
        }

        // Log migration result
        \Log::info("CS2 lobby migration completed", [
            'profiles_checked' => $profiles->count(),
            'lobbies_migrated' => $migratedCount,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all CS2 lobbies from game_lobbies table
        DB::table('game_lobbies')
            ->where('game_id', 730)
            ->where('join_method', 'steam_lobby')
            ->delete();
    }
};
