<?php

namespace App\Listeners;

use App\Events\GoalCompleted;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
     */
    public function handle(GoalCompleted $event): void
    {
        try {
            $goal = $event->goal;
            $server = $goal->server;

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) || 
                !($settings['notification_types']['goal_completed'] ?? true)) {
                return;
            }

            // Send the notification
            $success = $this->telegramService->sendGoalCompletedNotification($goal);

            Log::info('Telegram goal completed notification processed', [
                'goal_id' => $goal->id,
                'server_id' => $server->id,
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