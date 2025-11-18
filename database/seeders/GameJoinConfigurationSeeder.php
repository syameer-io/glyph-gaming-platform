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
            // WEEK 1: Dota 2
            // ============================================================
            [
                'game_id' => 570, // Dota 2 App ID
                'join_method' => 'steam_lobby',
                'display_name' => 'Steam Lobby Link',
                'icon' => 'steam',
                'priority' => 10,
                'validation_pattern' => '^steam:\/\/joinlobby\/570\/\d+\/\d+$',
                'requires_manual_setup' => false,
                'steam_app_id' => 570,
                'default_port' => null,
                'expiration_minutes' => 30,
                'instructions_how_to_create' =>
                    "1. Create a lobby in Dota 2\n" .
                    "2. Press Shift+Tab to open Steam overlay\n" .
                    "3. Right-click on your name in the friends list\n" .
                    "4. Select 'Invite to Lobby' or 'Copy Lobby Link'\n" .
                    "5. Paste the link here",
                'instructions_how_to_join' =>
                    "1. Click the 'Join Lobby' button below\n" .
                    "2. Steam will automatically open Dota 2 and join the lobby\n\n" .
                    "**Alternative:**\n" .
                    "1. Copy the lobby link\n" .
                    "2. Paste it in your browser address bar\n" .
                    "3. Press Enter - Steam will launch Dota 2",
                'is_enabled' => true,
            ],

            // ============================================================
            // WEEK 3: Warframe
            // ============================================================
            [
                'game_id' => 230410, // Warframe App ID
                'join_method' => 'steam_lobby',
                'display_name' => 'Steam Lobby Link',
                'icon' => 'steam',
                'priority' => 10,
                'validation_pattern' => '^steam:\/\/joinlobby\/230410\/\d+\/\d+$',
                'requires_manual_setup' => false,
                'steam_app_id' => 230410,
                'default_port' => null,
                'expiration_minutes' => 30,
                'instructions_how_to_create' =>
                    "**Important:** You must be using the **Steam version** of Warframe for lobby links to work.\n\n" .
                    "1. Create a squad/lobby in Warframe\n" .
                    "2. Press Shift+Tab to open Steam overlay\n" .
                    "3. Right-click on your name in the friends list\n" .
                    "4. Select 'Copy Lobby Link'\n" .
                    "5. Paste the link here\n\n" .
                    "**Note:** Most Warframe players prefer using in-game invites through the squad menu. " .
                    "Steam lobby links work but are less commonly used in the community.",
                'instructions_how_to_join' =>
                    "**Steam Version Required:** You must have Warframe installed through Steam to use lobby links.\n\n" .
                    "1. Click the 'Join Lobby' button below\n" .
                    "2. Steam will automatically launch Warframe and join the squad\n\n" .
                    "**Alternative Method:**\n" .
                    "1. Copy the lobby link\n" .
                    "2. Paste it in your browser or Steam chat\n" .
                    "3. Click the link to launch Warframe\n\n" .
                    "**Preferred Method:** Ask the host to send you an in-game invite through Warframe's squad menu for better reliability.",
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
        $this->command->info('- CS2: steam_lobby ONLY (per implementation guide Week 1)');
        $this->command->info('- Dota 2: steam_lobby ONLY');
        $this->command->info('- Warframe: steam_lobby ONLY');
        $this->command->info('');
        $this->command->info('Note: server_address method is for Minecraft (Week 5, not yet implemented)');
    }
}
