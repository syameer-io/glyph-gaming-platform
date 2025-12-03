<?php

namespace App\Listeners;

use App\Events\GoalProgressUpdated;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramGoalProgressUpdated implements ShouldQueue
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
    public function handle(GoalProgressUpdated $event): void
    {
        try {
            $goal = $event->goal;
            $server = $goal->server;

            // Only send progress notifications for significant milestones
            // to avoid spam (every 10% or major progress jumps)
            $progressPercent = $goal->completion_percentage;
            $shouldNotify = false;

            // Notify on milestone percentages (10, 20, 30, etc.)
            if ($progressPercent > 0 && $progressPercent % 10 == 0) {
                $shouldNotify = true;
            }

            // Also notify if this is a significant jump (5%+ progress in one update)
            if (isset($event->previousProgress)) {
                $previousPercent = ($event->previousProgress / $goal->target_value) * 100;
                $progressJump = $progressPercent - $previousPercent;

                if ($progressJump >= 5) {
                    $shouldNotify = true;
                }
            }

            if (!$shouldNotify) {
                return;
            }

            // Prevent duplicate notifications using a cache lock
            // The lock key includes the progress percentage to allow notifications at different milestones
            $lockKey = "telegram_goal_progress_notification_{$goal->id}_{$progressPercent}";

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram goal progress notification', [
                    'goal_id' => $goal->id,
                    'progress_percent' => $progressPercent,
                    'reason' => 'Already sent within lock window'
                ]);
                return;
            }

            // Set the lock before sending to prevent race conditions
            Cache::put($lockKey, true, 60);

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping goal progress notification', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['goal_progress'] ?? true)) {
                Log::info('Telegram goal progress notifications disabled for server', [
                    'goal_id' => $goal->id,
                    'server_id' => $server->id
                ]);
                return;
            }

            // Get contributor name if available
            $contributorName = null;
            if (isset($event->participant) && $event->participant->user) {
                $contributorName = $event->participant->user->display_name;
            }

            // Send the notification
            $success = $this->telegramService->sendGoalProgressNotification($goal, $contributorName);

            Log::info('Telegram goal progress notification processed', [
                'goal_id' => $goal->id,
                'server_id' => $server->id,
                'progress_percent' => $progressPercent,
                'contributor' => $contributorName,
                'telegram_chat_id' => $server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram goal progress notification', [
                'goal_id' => $event->goal->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(GoalProgressUpdated $event, \Throwable $exception): void
    {
        Log::error('TelegramGoalProgressUpdated listener failed', [
            'goal_id' => $event->goal->id ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}