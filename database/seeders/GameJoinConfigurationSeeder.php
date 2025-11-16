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
     * This seeder creates configuration records for each supported game,
     * defining their available join methods, validation patterns, and instructions.
     */
    public function run(): void
    {
        $configurations = [
            // CS2 (Counter-Strike 2)
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

            // CS2 also supports steam_connect for community servers
            [
                'game_id' => 730,
                'join_method' => 'steam_connect',
                'display_name' => 'Server Address',
                'icon' => 'server',
                'priority' => 5,
                'validation_pattern' => null, // Flexible validation
                'requires_manual_setup' => false,
                'steam_app_id' => 730,
                'default_port' => 27015,
                'expiration_minutes' => null, // Persistent for community servers
                'instructions_how_to_create' =>
                    "1. Get your CS2 community server IP and port\n" .
                    "2. Enter the server details below\n" .
                    "3. Optionally add server password if required",
                'instructions_how_to_join' =>
                    "1. Click the 'Connect' button to open CS2\n" .
                    "2. CS2 will automatically connect to the server\n\n" .
                    "**Alternative:**\n" .
                    "1. Open CS2 console (~)\n" .
                    "2. Type: connect [ip]:[port]\n" .
                    "3. Press Enter",
                'is_enabled' => true,
            ],

            // Dota 2
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

            // Warframe
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
        $this->command->info('Seeded ' . count($configurations) . ' configuration(s) for CS2, Dota 2, and Warframe');
    }
}
