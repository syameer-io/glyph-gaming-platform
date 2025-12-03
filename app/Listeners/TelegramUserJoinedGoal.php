<?php

namespace App\Listeners;

use App\Events\UserJoinedGoal;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramUserJoinedGoal implements ShouldQueue
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
    public function handle(UserJoinedGoal $event): void
    {
        try {
            $goal = $event->goal;
            $participant = $event->participant;
            $server = $goal->server;

            // Prevent duplicate notifications using a cache lock
            // The lock expires after 60 seconds to handle edge cases
            $lockKey = "telegram_user_joined_goal_notification_{$goal->id}_{$participant->id}";

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram user joined goal notification', [
                    'goal_id' => $goal->id,
                    'participant_id' => $participant->id,
                    'reason' => 'Already sent within lock window'
                ]);
                return;
            }

            // Set the lock before sending to prevent race conditions
            Cache::put($lockKey, true, 60);

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping user joined goal notification', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['user_joined'] ?? true)) {
                Log::info('Telegram user joined goal notifications disabled for server', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Only notify for the first few participants to avoid spam
            // or for goals with less than 10 participants total
            if ($goal->participant_count > 10) {
                // For larger goals, only notify occasionally
                // (every 5th participant or significant milestones)
                if ($goal->participant_count % 5 !== 0) {
                    return;
                }
            }

            // Send the notification
            $success = $this->telegramService->sendUserJoinedGoalNotification($goal, $participant);

            Log::info('Telegram user joined goal notification processed', [
                'goal_id' => $goal->id,
                'server_id' => $server->id,
                'user_id' => $participant->user_id,
                'participant_count' => $goal->participant_count,
                'telegram_chat_id' => $server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram user joined goal notification', [
                'goal_id' => $event->goal->id ?? null,
                'participant_id' => $event->participant->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserJoinedGoal $event, \Throwable $exception): void
    {
        Log::error('TelegramUserJoinedGoal listener failed', [
            'goal_id' => $event->goal->id ?? null,
            'participant_id' => $event->participant->id ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}