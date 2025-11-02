<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Gaming Server',
            'description' => fake()->sentence(),
            'invite_code' => Str::random(8),
            'icon_url' => null,
            'creator_id' => User::factory(),
        ];
    }
}
