<?php

namespace App\Listeners;

use App\Events\TeamMemberLeft;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramTeamMemberLeft implements ShouldQueue
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
     *
     * NOTE: TeamMemberLeft event has $user (User model), not $teamMember
     */
    public function handle(TeamMemberLeft $event): void
    {
        try {
            $team = $event->team;
            $user = $event->user; // User model, NOT TeamMember

            // Prevent duplicate notifications using a cache lock
            // Use timestamp as part of key since same user could leave/rejoin
            $lockKey = "telegram_team_member_left_notification_{$team->id}_{$user->id}_" . now()->timestamp;

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram team member left notification', [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
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

            // Load server relationship
            $team->load('server');

            // Check if server has Telegram integration enabled
            if (!$team->server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping team member left notification', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            // Check if this notification type is enabled (defaults to false - lower priority)
            $settings = $team->server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['team_member_left'] ?? false)) {
                Log::info('Telegram team member left notifications disabled for server', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            // Refresh team to get updated current_size after member removal
            $team->refresh();

            $success = $this->telegramService->sendTeamMemberLeftNotification($team, $user);

            Log::info('Telegram team member left notification processed', [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'user_name' => $user->display_name,
                'telegram_chat_id' => $team->server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram team member left notification', [
                'team_id' => $event->team->id ?? null,
                'user_id' => $event->user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(TeamMemberLeft $event, \Throwable $exception): void
    {
        Log::error('TelegramTeamMemberLeft job failed', [
            'team_id' => $event->team->id ?? null,
            'error' => $exception->getMessage()
        ]);
    }
}
