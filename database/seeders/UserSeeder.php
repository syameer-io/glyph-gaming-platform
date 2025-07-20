<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $users = [
            [
                'username' => 'gamer1',
                'display_name' => 'ProGamer One',
                'email' => 'gamer1@test.com',
                'password' => Hash::make('password123'),
                'steam_id' => '76561198000000001',
            ],
            [
                'username' => 'gamer2',
                'display_name' => 'Elite Player',
                'email' => 'gamer2@test.com',
                'password' => Hash::make('password123'),
                'steam_id' => '76561198000000002',
            ],
            [
                'username' => 'gamer3',
                'display_name' => 'Casual Gamer',
                'email' => 'gamer3@test.com',
                'password' => Hash::make('password123'),
                'steam_id' => null,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            
            // Create profile
            Profile::create([
                'user_id' => $user->id,
                'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name) . '&background=7289DA&color=fff',
                'bio' => 'Gaming enthusiast and community member.',
                'status' => 'online',
                'steam_data' => [
                    'games' => [
                        [
                            'appid' => 730,
                            'name' => 'Counter-Strike 2',
                            'playtime_forever' => rand(1000, 50000),
                            'img_icon_url' => '8dbc71957312bbd3baea65848b545be9265f0a83',
                        ],
                        [
                            'appid' => 570,
                            'name' => 'Dota 2',
                            'playtime_forever' => rand(500, 30000),
                            'img_icon_url' => '0bbb630d63262dd66d2fdd0f7d37e8661a410075',
                        ],
                        [
                            'appid' => 230410,
                            'name' => 'Warframe',
                            'playtime_forever' => rand(200, 20000),
                            'img_icon_url' => '9bf9879bc21fa0d1b435eac7b330c8fe1b02c10a',
                        ],
                    ],
                    'current_game' => null,
                ],
            ]);
        }

        // Create friend relationships
        $user1 = User::find(1);
        $user2 = User::find(2);
        
        // Only create one friendship record (bidirectional relationship)
        $user1->friends()->attach($user2->id, ['status' => 'accepted']);
    }
}