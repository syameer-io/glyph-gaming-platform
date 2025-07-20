<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Phase1TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users with gaming preferences
        $user1 = \App\Models\User::factory()->create([
            'username' => 'csgo_pro',
            'display_name' => 'CS:GO Pro',
            'email' => 'csgopro@test.com',
        ]);

        $user2 = \App\Models\User::factory()->create([
            'username' => 'dota_player',
            'display_name' => 'Dota Player',
            'email' => 'dota@test.com',
        ]);

        // Create profiles for the users
        $user1->profile()->create([
            'status' => 'online',
            'steam_data' => json_encode([
                'profile' => ['personaname' => 'CS:GO Pro'],
                'games' => [],
                'last_updated' => now()
            ])
        ]);

        $user2->profile()->create([
            'status' => 'online',
            'steam_data' => json_encode([
                'profile' => ['personaname' => 'Dota Player'],
                'games' => [],
                'last_updated' => now()
            ])
        ]);

        // Create gaming preferences
        \App\Models\UserGamingPreference::create([
            'user_id' => $user1->id,
            'game_appid' => '730',
            'game_name' => 'Counter-Strike 2',
            'playtime_forever' => 15000, // 250 hours
            'playtime_2weeks' => 600, // 10 hours
            'preference_level' => 'high',
            'skill_level' => 'advanced',
            'last_played' => now()->subHours(2),
        ]);

        \App\Models\UserGamingPreference::create([
            'user_id' => $user2->id,
            'game_appid' => '570',
            'game_name' => 'Dota 2',
            'playtime_forever' => 12000, // 200 hours
            'playtime_2weeks' => 480, // 8 hours
            'preference_level' => 'high',
            'skill_level' => 'intermediate',
            'last_played' => now()->subHours(1),
        ]);

        // Create test servers with tags
        $csServer = \App\Models\Server::create([
            'name' => 'CS2 Competitive Hub',
            'description' => 'Serious Counter-Strike 2 competitive gaming community',
            'creator_id' => $user1->id,
        ]);

        $dotaServer = \App\Models\Server::create([
            'name' => 'Dota 2 Learning Zone',
            'description' => 'Learn and improve your Dota 2 skills with friendly players',
            'creator_id' => $user2->id,
        ]);

        $mixedServer = \App\Models\Server::create([
            'name' => 'Multi-Gaming Community',
            'description' => 'Play various games together in a friendly environment',
            'creator_id' => $user1->id,
        ]);

        // Add tags to servers
        $csServer->addTag('game', 'cs2');
        $csServer->addTag('skill_level', 'advanced');
        $csServer->addTag('region', 'na_east');
        $csServer->addTag('language', 'english');

        $dotaServer->addTag('game', 'dota2');
        $dotaServer->addTag('skill_level', 'intermediate');
        $dotaServer->addTag('region', 'na_east');
        $dotaServer->addTag('language', 'english');

        $mixedServer->addTag('game', 'cs2');
        $mixedServer->addTag('game', 'dota2');
        $mixedServer->addTag('skill_level', 'beginner');
        $mixedServer->addTag('region', 'na_east');

        // Create default channels for servers
        $csServer->channels()->create(['name' => 'general', 'type' => 'text', 'position' => 0]);
        $dotaServer->channels()->create(['name' => 'general', 'type' => 'text', 'position' => 0]);
        $mixedServer->channels()->create(['name' => 'general', 'type' => 'text', 'position' => 0]);

        echo "Phase 1 test data created successfully!\n";
        echo "- Created 2 test users with gaming preferences\n";
        echo "- Created 3 test servers with game tags\n";
        echo "- CS2 server tagged for advanced CS:GO players\n";
        echo "- Dota 2 server tagged for intermediate Dota players\n";
        echo "- Mixed gaming server for beginners\n";
    }
}
