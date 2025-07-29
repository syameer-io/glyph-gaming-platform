<?php

namespace App\Listeners;

use App\Events\UserJoinedGoal;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
     */
    public function handle(UserJoinedGoal $event): void
    {
        try {
            $goal = $event->goal;
            $participant = $event->participant;
            $server = $goal->server;

            // Check if server has Telegram integration enabled
            if (!$server->telegram_chat_id) {
                return;
            }

            // Check if this notification type is enabled
            $settings = $server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) || 
                !($settings['notification_types']['user_joined'] ?? true)) {
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