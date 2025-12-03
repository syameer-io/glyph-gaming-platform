<?php

namespace App\Listeners;

use App\Events\TeamMemberJoined;
use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramTeamMemberJoined implements ShouldQueue
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
    public function handle(TeamMemberJoined $event): void
    {
        try {
            $team = $event->team;
            $teamMember = $event->teamMember;

            // Prevent duplicate notifications using a cache lock
            // The lock expires after 60 seconds to handle edge cases
            $lockKey = "telegram_team_member_joined_notification_{$team->id}_{$teamMember->id}";

            if (Cache::has($lockKey)) {
                Log::debug('Skipping duplicate Telegram team member joined notification', [
                    'team_id' => $team->id,
                    'member_id' => $teamMember->id,
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

            // Load relationships
            $team->load('server');
            $teamMember->load('user');

            // Check if server has Telegram integration enabled
            if (!$team->server->telegram_chat_id) {
                Log::info('No Telegram chat ID for server, skipping team member joined notification', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            // Check if this notification type is enabled
            $settings = $team->server->telegram_settings ?? [];
            if (!($settings['notifications_enabled'] ?? true) ||
                !($settings['notification_types']['team_member_joined'] ?? true)) {
                Log::info('Telegram team member joined notifications disabled for server', [
                    'team_id' => $team->id,
                    'server_id' => $team->server_id
                ]);
                return;
            }

            // Refresh team to get updated current_size
            $team->refresh();

            $success = $this->telegramService->sendTeamMemberJoinedNotification($team, $teamMember);

            Log::info('Telegram team member joined notification processed', [
                'team_id' => $team->id,
                'member_id' => $teamMember->id,
                'user_id' => $teamMember->user_id,
                'telegram_chat_id' => $team->server->telegram_chat_id,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram team member joined notification', [
                'team_id' => $event->team->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(TeamMemberJoined $event, \Throwable $exception): void
    {
        Log::error('TelegramTeamMemberJoined job failed', [
            'team_id' => $event->team->id ?? null,
            'error' => $exception->getMessage()
        ]);
    }
}
