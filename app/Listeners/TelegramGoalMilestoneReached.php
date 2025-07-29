<?php

namespace App\Listeners;

use App\Events\GoalMilestoneReached;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TelegramGoalMilestoneReached implements ShouldQueue
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
    public function handle(GoalMilestoneReached $event): void
    {
        try {
            $goal = $event->goal;
            $milestone = $event->milestone;
            $server = $goal->server;

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) || 
                !($settings['notification_types']['milestone_reached'] ?? true)) {
                return;
            }

            // Send the notification
            $success = $this->telegramService->sendMilestoneReachedNotification($goal, $milestone);

            Log::info('Telegram milestone reached notification processed', [
                'goal_id' => $goal->id,
                'milestone_id' => $milestone->id,
                'server_id' => $server->id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram milestone reached notification', [
                'goal_id' => $event->goal->id ?? null,
                'milestone_id' => $event->milestone->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(GoalMilestoneReached $event, \Throwable $exception): void
    {
        Log::error('TelegramGoalMilestoneReached listener failed', [
            'goal_id' => $event->goal->id ?? null,
            'milestone_id' => $event->milestone->id ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}