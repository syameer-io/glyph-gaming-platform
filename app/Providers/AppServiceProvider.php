<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Server;
use App\Models\GameLobby;
use App\Policies\ServerPolicy;
use App\Policies\LobbyPolicy;
use App\Services\LobbyStatusService;
use App\Observers\GameLobbyObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register LobbyStatusService as singleton to share instance across application
        // This improves performance by reusing the same service instance
        $this->app->singleton(LobbyStatusService::class, function ($app) {
            return new LobbyStatusService();
        });
    }

    public function boot(): void
    {
        Gate::policy(Server::class, ServerPolicy::class);
        Gate::policy(GameLobby::class, LobbyPolicy::class);

        // Register GameLobbyObserver to handle cache invalidation automatically
        GameLobby::observe(GameLobbyObserver::class);
    }
}