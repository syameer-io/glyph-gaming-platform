<?php

namespace App\Listeners;

use App\Events\GoalCompleted;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramGoalCompleted implements ShouldQueue
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
    public function handle(GoalCompleted $event): void
    {
        try {
            $goal = $event->goal;
            $server = $goal->server;

            // Prevent duplicate notifications using a cache lock
            // The lock expires after 60 seconds to handle edge cases
            $lockKey = "telegram_goal_completed_notification_{$goal->id}";

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram goal completed notification', [
                    'goal_id' => $goal->id,
                    'reason' => 'Already sent within lock window'
                ]);
                return;
            }

            // Set the lock before sending to prevent race conditions
            Cache::put($lockKey, true, 60);

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping goal completed notification', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['goal_completed'] ?? true)) {
                Log::info('Telegram goal completed notifications disabled for server', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Send the notification
            $success = $this->telegramService->sendGoalCompletedNotification($goal);

            Log::info('Telegram goal completed notification processed', [
                'goal_id' => $goal->id,
                'server_id' => $server->id,
                'telegram_chat_id' => $server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram goal completed notification', [
                'goal_id' => $event->goal->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(GoalCompleted $event, \Throwable $exception): void
    {
        Log::error('TelegramGoalCompleted listener failed', [
            'goal_id' => $event->goal->id ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}