<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Server;
use App\Models\GameLobby;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Policies\ServerPolicy;
use App\Policies\LobbyPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\DirectMessagePolicy;
use App\Services\LobbyStatusService;
use App\Observers\GameLobbyObserver;
use App\Mail\Transport\SendGridTransport;
use Illuminate\Support\Facades\Mail;

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
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(DirectMessage::class, DirectMessagePolicy::class);

        // Register GameLobbyObserver to handle cache invalidation automatically
        GameLobby::observe(GameLobbyObserver::class);

        // Register SendGrid mail transport for HTTP API
        Mail::extend('sendgrid', function (array $config) {
            $apiKey = $config['api_key'] ?? config('services.sendgrid.api_key');

            if (empty($apiKey)) {
                throw new \InvalidArgumentException(
                    'SendGrid API key is not configured. Set SENDGRID_API_KEY in .env'
                );
            }

            return new SendGridTransport($apiKey);
        });
    }
}