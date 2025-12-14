<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameJoinConfiguration;
use Illuminate\Support\Facades\DB;

class GameJoinConfigurationSeeder extends Seeder
{
    /**
     * Seed game join configurations for multi-game lobby system
     *
     * IMPORTANT: This seeder follows the implementation guide strictly.
     * Week 1: CS2, Dota 2, Warframe with steam_lobby ONLY
     * Week 5: Minecraft with server_address (not implemented yet)
     *
     * DO NOT add steam_connect or server_address to CS2!
     */
    public function run(): void
    {
        // Remove deprecated game configurations (Dota 2, Warframe)
        // These were replaced with Deep Rock Galactic and GTFO
        $deprecatedGameIds = [570, 230410]; // Dota 2 and Warframe
        $deleted = GameJoinConfiguration::whereIn('game_id', $deprecatedGameIds)->delete();
        if ($deleted > 0) {
            $this->command->info("Removed {$deleted} deprecated game configuration(s) (Dota 2, Warframe)");
        }

        $configurations = [
            // ============================================================
            // WEEK 1: CS2 (Counter-Strike 2)
            // ============================================================
            // CS2 ONLY supports steam_lobby join method
            // DO NOT add steam_connect - that's incorrect per implementation guide
            [
                'game_id' => 730, // CS2 App ID
                'join_method' => 'steam_lobby',
                'display_name' => 'Steam Lobby Link',
                'icon' => 'steam',
                'priority' => 10,
                'validation_pattern' => '^steam:\/\/joinlobby\/730\/\d+\/\d+$',
                'requires_manual_setup' => false,
                'steam_app_id' => 730,
                'default_port' => null,
                'expiration_minutes' => 30,
                'instructions_how_to_create' =>
                    "1. Create a lobby in Counter-Strike 2\n" .
                    "2. Press Shift+Tab to open Steam overlay\n" .
                    "3. Right-click on your name\n" .
                    "4. Select 'Copy Lobby Link'\n" .
                    "5. Paste the link here",
                'instructions_how_to_join' =>
                    "1. Click the 'Join Lobby' button below\n" .
                    "2. Steam will automatically open CS2 and join the lobby\n\n" .
                    "**Alternative:**\n" .
                    "1. Copy the lobby link\n" .
                    "2. Paste it in your browser or Steam chat\n" .
                    "3. Click the link to join",
                'is_enabled' => true,
            ],

            // ============================================================
            // Deep Rock Galactic
            // ============================================================
            [
                'game_id' => 548430, // Deep Rock Galactic App ID
                'join_method' => 'steam_lobby',
                'display_name' => 'Steam Lobby Link',
                'icon' => 'steam',
                'priority' => 10,
                'validation_pattern' => '^steam:\/\/joinlobby\/548430\/\d+\/\d+$',
                'requires_manual_setup' => false,
                'steam_app_id' => 548430,
                'default_port' => null,
                'expiration_minutes' => 30,
                'instructions_how_to_create' =>
                    "1. Launch Deep Rock Galactic and create a mission lobby\n" .
                    "2. Press Shift+Tab to open Steam overlay\n" .
                    "3. Click on your name in the friends list\n" .
                    "4. Right-click on 'Join Game' button\n" .
                    "5. Select 'Copy Link Address'\n" .
                    "6. Paste the link here\n\n" .
                    "**Rock and Stone!** Your fellow miners can now join your expedition.",
                'instructions_how_to_join' =>
                    "1. Click the 'Join Lobby' button below\n" .
                    "2. Steam will automatically launch Deep Rock Galactic and join the lobby\n\n" .
                    "**Alternative:**\n" .
                    "1. Copy the lobby link\n" .
                    "2. Paste it in your browser address bar\n" .
                    "3. Press Enter - Steam will launch the game and connect you\n\n" .
                    "**For Karl!**",
                'is_enabled' => true,
            ],

            // ============================================================
            // GTFO
            // ============================================================
            [
                'game_id' => 493520, // GTFO App ID
                'join_method' => 'steam_lobby',
                'display_name' => 'Steam Lobby Link',
                'icon' => 'steam',
                'priority' => 10,
                'validation_pattern' => '^steam:\/\/joinlobby\/493520\/\d+\/\d+$',
                'requires_manual_setup' => false,
                'steam_app_id' => 493520,
                'default_port' => null,
                'expiration_minutes' => 30,
                'instructions_how_to_create' =>
                    "1. Launch GTFO and create a lobby from the Rundown menu\n" .
                    "2. Press Shift+Tab to open Steam overlay\n" .
                    "3. Click on your name in the friends list\n" .
                    "4. Right-click on 'Join Game' button\n" .
                    "5. Select 'Copy Link Address'\n" .
                    "6. Paste the link here\n\n" .
                    "**Tip:** Make sure your lobby slots are not locked before sharing.",
                'instructions_how_to_join' =>
                    "1. Click the 'Join Lobby' button below\n" .
                    "2. Steam will automatically launch GTFO and join the lobby\n\n" .
                    "**Alternative:**\n" .
                    "1. Copy the lobby link\n" .
                    "2. Paste it in your browser address bar\n" .
                    "3. Press Enter - Steam will launch GTFO and connect you\n\n" .
                    "**Note:** All players should be on the same game version to avoid connection issues.",
                'is_enabled' => true,
            ],
        ];

        foreach ($configurations as $config) {
            GameJoinConfiguration::updateOrCreate(
                [
                    'game_id' => $config['game_id'],
                    'join_method' => $config['join_method'],
                ],
                $config
            );
        }

        $this->command->info('Game join configurations seeded successfully!');
        $this->command->info('Seeded ' . count($configurations) . ' configuration(s)');
        $this->command->info('- CS2: steam_lobby (confirmed working)');
        $this->command->info('- Deep Rock Galactic: steam_lobby (confirmed working)');
        $this->command->info('- GTFO: steam_lobby (confirmed working)');
        $this->command->info('');
        $this->command->info('All games support native steam://joinlobby protocol.');
    }
}
