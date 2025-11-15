<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Server;
use App\Models\GameLobby;
use App\Policies\ServerPolicy;
use App\Policies\LobbyPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Server::class, ServerPolicy::class);
        Gate::policy(GameLobby::class, LobbyPolicy::class);
    }
}