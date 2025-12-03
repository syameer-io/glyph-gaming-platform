<?php

namespace App\Listeners;

use App\Events\TeamCreated;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramTeamCreated implements ShouldQueue
{
    use InteractsWithQueue;

    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle the event.
     *
     * Uses cache-based lock to prevent duplicate notifications.
     * This handles the case where the listener may be triggered multiple times
     * due to queue retries or Laravel's event registration behavior.
     */
    public function handle(TeamCreated $event): void
    {
        try {
            $team = $event->team;

            // Prevent duplicate notifications using a cache lock
            // The lock expires after 60 seconds to handle edge cases
            $lockKey = "telegram_team_created_notification_{$team->id}";

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram team created notification', [
                    'team_id' => $team->id,
                    'reason' => 'Already sent within lock window'
                ]);
                return;
            }

            // Set the lock before sending to prevent race conditions
            Cache::put($lockKey, true, 60);

            // Only notify for server-associated teams
            if (!$team->server_id) {
                Log::debug('Skipping Telegram notification - team has no server', [
                    'team_id' => $team->id
                ]);
                return;
            }

            // Load relationships for notification
            $team->load(['server', 'creator']);

            // Check if server has Telegram integration enabled
            if (!$team->server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping team created notification', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            // Check if this notification type is enabled
            $settings = $team->server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['team_created'] ?? true)) {
                Log::info('Telegram team created notifications disabled for server', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            $success = $this->telegramService->sendTeamCreatedNotification($team);

            Log::info('Telegram team created notification processed', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'server_id' => $team->server_id,
                'telegram_chat_id' => $team->server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram team created notification', [
                'team_id' => $event->team->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(TeamCreated $event, \Throwable $exception): void
    {
        Log::error('TelegramTeamCreated job failed', [
            'team_id' => $event->team->id ?? null,
            'error' => $exception->getMessage()
        ]);
    }
}
