<?php

namespace App\Services;

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Exception;
use App\Models\Server;
use App\Models\ServerGoal;
use App\Models\GoalParticipant;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TelegramBotService
{
    protected ?BotApi $bot;
    protected string $botToken;
    
    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        
        if (!$this->botToken) {
            throw new \Exception('Telegram bot token not configured');
        }
        
        // Check if cURL is available before initializing the bot
        if (!function_exists('curl_init')) {
            Log::warning('cURL extension is not available. Telegram bot functionality will be disabled.');
            $this->bot = null;
            return;
        }
        
        try {
            $this->bot = new BotApi($this->botToken);
        } catch (\Error $e) {
            Log::error('Failed to initialize Telegram bot: ' . $e->getMessage());
            $this->bot = null;
        }
    }

    /**
     * Send a message to a Telegram chat
     */
    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        // If bot is not available (cURL issue), silently fail
        if (!$this->bot) {
            Log::warning('Telegram bot not available (cURL issue) - message not sent', [
                'chat_id' => $chatId,
                'message_length' => strlen($message)
            ]);
            return false;
        }
        
        try {
            $defaultOptions = [
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];
            
            $options = array_merge($defaultOptions, $options);

            $replyMarkup = $options['reply_markup'] ?? null;

            $this->bot->sendMessage(
                $chatId,
                $message,
                $options['parse_mode'],
                $options['disable_web_page_preview'],
                null, // reply_to_message_id
                $replyMarkup
            );

            Log::info('Telegram message sent successfully', [
                'chat_id' => $chatId,
                'message_length' => strlen($message),
                'has_keyboard' => $replyMarkup !== null
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'message' => $message
            ]);
            
            return false;
        }
    }

    /**
     * Send goal completed notification
     */
    public function sendGoalCompletedNotification(ServerGoal $goal): bool
    {
        $server = $goal->server;
        
        if (!$server->telegram_chat_id) {
            return false;
        }

        // Get top contributors
        $topContributors = $goal->participants()
            ->where('participation_status', 'active')
            ->orderBy('individual_progress', 'desc')
            ->with('user')
            ->take(3)
            ->get();

        $message = $this->buildGoalCompletedMessage($goal, $topContributors);
        
        return $this->sendMessage($server->telegram_chat_id, $message);
    }

    /**
     * Send goal progress update notification
     */
    public function sendGoalProgressNotification(ServerGoal $goal, ?string $contributorName = null): bool
    {
        $server = $goal->server;
        
        if (!$server->telegram_chat_id) {
            return false;
        }

        $message = $this->buildGoalProgressMessage($goal, $contributorName);
        
        return $this->sendMessage($server->telegram_chat_id, $message);
    }

    /**
     * Send new goal created notification
     */
    public function sendNewGoalNotification(ServerGoal $goal): bool
    {
        $server = $goal->server;

        if (!$server->telegram_chat_id) {
            return false;
        }

        $message = $this->buildNewGoalMessage($goal);
        $keyboard = $this->buildGoalKeyboard($goal);

        return $this->sendMessage($server->telegram_chat_id, $message, ['reply_markup' => $keyboard]);
    }

    /**
     * Send user joined goal notification
     */
    public function sendUserJoinedGoalNotification(ServerGoal $goal, GoalParticipant $participant): bool
    {
        $server = $goal->server;
        
        if (!$server->telegram_chat_id) {
            return false;
        }

        $message = $this->buildUserJoinedMessage($goal, $participant);
        
        return $this->sendMessage($server->telegram_chat_id, $message);
    }

    /**
     * Send goal milestone reached notification
     */
    public function sendMilestoneReachedNotification(ServerGoal $goal, $milestone): bool
    {
        $server = $goal->server;
        
        if (!$server->telegram_chat_id) {
            return false;
        }

        $message = $this->buildMilestoneReachedMessage($goal, $milestone);
        
        return $this->sendMessage($server->telegram_chat_id, $message);
    }

    /**
     * Process incoming webhook from Telegram
     */
    public function processWebhook(array $update): void
    {
        try {
            if (isset($update['message'])) {
                $message = $update['message'];
                $this->processMessage($message);
            }

            // Handle inline keyboard button presses
            if (isset($update['callback_query'])) {
                $this->processCallbackQuery($update['callback_query']);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Telegram webhook', [
                'error' => $e->getMessage(),
                'update' => $update
            ]);
        }
    }

    /**
     * Process incoming message
     */
    protected function processMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'] ?? null;

        Log::info('Processing Telegram message', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'text' => $text
        ]);

        // Handle commands
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text, $userId);
        }
    }

    /**
     * Process callback query from inline keyboard button press
     */
    protected function processCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;

        Log::info('Processing Telegram callback query', [
            'callback_id' => $callbackId,
            'data' => $data,
            'chat_id' => $chatId
        ]);

        try {
            // Answer callback to remove loading state on button
            $this->bot->answerCallbackQuery($callbackId, 'Loading...');

            // Parse callback data format: action:entity_type:entity_id
            $parts = explode(':', $data);
            $action = $parts[0] ?? '';
            $entityType = $parts[1] ?? '';
            $entityId = $parts[2] ?? '';

            if ($action === 'refresh' && $chatId) {
                $this->handleRefreshCallback($entityType, $entityId, $chatId);
            }

        } catch (\Exception $e) {
            Log::error('Error processing callback query', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Handle refresh callback - sends updated status message
     */
    protected function handleRefreshCallback(string $entityType, string $entityId, string $chatId): void
    {
        switch ($entityType) {
            case 'goal':
                $goal = ServerGoal::find($entityId);
                if ($goal) {
                    $message = $this->buildGoalStatusMessage($goal);
                    $keyboard = $this->buildGoalKeyboard($goal);
                    $this->sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
                }
                break;

            case 'team':
                $team = Team::with(['creator', 'activeMembers.user'])->find($entityId);
                if ($team) {
                    $message = $this->buildTeamStatusMessage($team);
                    $keyboard = $this->buildTeamKeyboard($team);
                    $this->sendMessage($chatId, $message, ['reply_markup' => $keyboard]);
                }
                break;
        }
    }

    /**
     * Handle bot commands
     */
    protected function handleCommand(string $chatId, string $command, ?int $userId): void
    {
        $parts = explode(' ', trim($command));
        $cmd = strtolower($parts[0]);

        // Strip bot username from command (e.g., /start@GlyphCommunityBot -> /start)
        if (str_contains($cmd, '@')) {
            $cmd = explode('@', $cmd)[0];
        }

        switch ($cmd) {
            case '/start':
                $this->handleStartCommand($chatId, $userId);
                break;
                
            case '/goals':
                $this->handleGoalsCommand($chatId);
                break;
                
            case '/link':
                $inviteCode = $parts[1] ?? null;
                $this->handleLinkCommand($chatId, $inviteCode);
                break;
                
            case '/help':
                $this->handleHelpCommand($chatId);
                break;
                
            default:
                $this->sendMessage($chatId, "Unknown command. Type /help for available commands.");
        }
    }

    /**
     * Handle /start command
     */
    protected function handleStartCommand(string $chatId, ?int $userId): void
    {
        $message = "🎮 <b>Welcome to Glyph Community Bot!</b>\n\n";
        $message .= "I help manage gaming community goals and achievements.\n\n";
        $message .= "Available commands:\n";
        $message .= "• /link {invite_code} - Link this chat to a server\n";
        $message .= "• /goals - View active community goals\n";
        $message .= "• /help - Show this help message\n\n";
        $message .= "Get started by linking me to your gaming server! 🚀";
        
        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /goals command
     */
    protected function handleGoalsCommand(string $chatId): void
    {
        $server = Server::where('telegram_chat_id', $chatId)->first();
        
        if (!$server) {
            $this->sendMessage($chatId, "❌ This chat is not linked to any server. Use /link {invite_code} first.");
            return;
        }

        $activeGoals = $server->goals()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if ($activeGoals->isEmpty()) {
            $this->sendMessage($chatId, "📋 No active goals found for <b>{$server->name}</b>.");
            return;
        }

        $message = "🎯 <b>Active Goals for {$server->name}</b>\n\n";
        
        foreach ($activeGoals as $goal) {
            $progress = round($goal->completion_percentage, 1);
            $message .= "• <b>{$goal->title}</b>\n";
            $message .= "  📊 {$goal->current_progress}/{$goal->target_value} ({$progress}%)\n";
            $message .= "  👥 {$goal->participant_count} participants\n";
            
            if ($goal->deadline) {
                $daysLeft = now()->diffInDays($goal->deadline, false);
                if ($daysLeft >= 0) {
                    $message .= "  ⏰ {$daysLeft} days remaining\n";
                }
            }
            $message .= "\n";
        }

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /link command
     */
    protected function handleLinkCommand(string $chatId, ?string $inviteCode): void
    {
        if (!$inviteCode) {
            $this->sendMessage($chatId, "❌ Please provide an invite code: /link {invite_code}");
            return;
        }

        $server = Server::where('invite_code', $inviteCode)->first();
        
        if (!$server) {
            $this->sendMessage($chatId, "❌ Invalid invite code. Please check and try again.");
            return;
        }

        if ($server->telegram_chat_id) {
            $this->sendMessage($chatId, "❌ This server is already linked to another Telegram chat.");
            return;
        }

        $server->update(['telegram_chat_id' => $chatId]);
        
        $message = "✅ <b>Successfully linked!</b>\n\n";
        $message .= "🎮 Server: <b>{$server->name}</b>\n";
        $message .= "👥 Members: {$server->members()->count()}\n";
        $message .= "🎯 Active Goals: {$server->goals()->where('status', 'active')->count()}\n\n";
        $message .= "I'll now send notifications about goal updates and achievements! 🚀\n\n";
        $message .= "Use /goals to see current active goals.";
        
        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /help command
     */
    protected function handleHelpCommand(string $chatId): void
    {
        $message = "🤖 <b>Glyph Bot Help</b>\n\n";
        $message .= "<b>Available Commands:</b>\n";
        $message .= "• /start - Welcome message and setup info\n";
        $message .= "• /link {invite_code} - Link chat to your gaming server\n";
        $message .= "• /goals - View all active community goals\n";
        $message .= "• /help - Show this help message\n\n";
        $message .= "<b>What I do:</b>\n";
        $message .= "🎯 Notify when goals are completed\n";
        $message .= "📈 Send progress updates\n";
        $message .= "🎉 Announce new community challenges\n";
        $message .= "👥 Track when members join goals\n\n";
        $message .= "Questions? Contact your server admin! 💬";
        
        $this->sendMessage($chatId, $message);
    }

    /**
     * Build goal completed message
     */
    protected function buildGoalCompletedMessage(ServerGoal $goal, Collection $topContributors): string
    {
        $message = "🏆 <b>GOAL COMPLETED!</b> 🎉\n\n";
        $message .= "\"<b>{$goal->title}</b>\" has been completed!\n";
        
        if ($goal->game_name) {
            $message .= "🎮 Game: {$goal->game_name}\n";
        }
        
        $message .= "📊 Final: {$goal->current_progress}/{$goal->target_value} (100%)\n";
        $message .= "👥 {$goal->participant_count} participants contributed\n\n";

        if ($topContributors->count() > 0) {
            $message .= "🥇 <b>Top Contributors:</b>\n";
            $medals = ['🥇', '🥈', '🥉'];
            
            foreach ($topContributors as $index => $participant) {
                $medal = $medals[$index] ?? '🏅';
                $contribution = round($participant->contribution_percentage, 1);
                $message .= "{$medal} {$participant->user->display_name} - {$contribution}%\n";
            }
            $message .= "\n";
        }

        $message .= "Great work, team! 🔥";
        
        return $message;
    }

    /**
     * Build goal progress message
     */
    protected function buildGoalProgressMessage(ServerGoal $goal, ?string $contributorName): string
    {
        $message = "📈 <b>Goal Progress Update</b>\n\n";
        $message .= "\"<b>{$goal->title}</b>\"\n";
        
        if ($goal->game_name) {
            $message .= "🎮 {$goal->game_name}\n";
        }
        
        $progress = round($goal->completion_percentage, 1);
        $message .= "📊 Progress: {$goal->current_progress}/{$goal->target_value} ({$progress}%)\n";

        if ($contributorName) {
            $message .= "🔥 Recent contributor: <b>{$contributorName}</b>\n";
        }

        $message .= "\nKeep it up, gamers! 💪";
        
        return $message;
    }

    /**
     * Build new goal message
     */
    protected function buildNewGoalMessage(ServerGoal $goal): string
    {
        $message = "🎯 <b>NEW COMMUNITY GOAL!</b>\n\n";
        $message .= "\"<b>{$goal->title}</b>\"\n";
        
        if ($goal->game_name) {
            $message .= "🎮 Game: {$goal->game_name}\n";
        }
        
        $message .= "🎯 Target: {$goal->target_value}\n";
        
        if ($goal->deadline) {
            $daysLeft = now()->diffInDays($goal->deadline, false);
            if ($daysLeft >= 0) {
                $message .= "⏰ Deadline: {$daysLeft} days\n";
            }
        }
        
        $difficulty = ucfirst($goal->difficulty);
        $difficultyEmoji = [
            'Easy' => '🟢',
            'Medium' => '🟡', 
            'Hard' => '🔴',
            'Extreme' => '💜'
        ];
        $message .= "{$difficultyEmoji[$difficulty]} Difficulty: {$difficulty}\n\n";
        
        if ($goal->description) {
            $message .= "{$goal->description}\n\n";
        }
        
        $message .= "Ready to join? Type /goals to see all active challenges! 🚀";
        
        return $message;
    }

    /**
     * Build user joined message
     */
    protected function buildUserJoinedMessage(ServerGoal $goal, GoalParticipant $participant): string
    {
        $message = "🎮 <b>{$participant->user->display_name}</b> joined the goal!\n\n";
        $message .= "\"<b>{$goal->title}</b>\"\n";
        $message .= "👥 Participants: {$goal->participant_count}\n";
        
        $progress = round($goal->completion_percentage, 1);
        $message .= "📊 Current: {$goal->current_progress}/{$goal->target_value} ({$progress}%)\n\n";
        $message .= "Welcome aboard! 🚀";
        
        return $message;
    }

    /**
     * Build milestone reached message
     */
    protected function buildMilestoneReachedMessage(ServerGoal $goal, $milestone): string
    {
        $message = "🎖️ <b>MILESTONE REACHED!</b>\n\n";
        $message .= "\"<b>{$goal->title}</b>\"\n";
        $message .= "🏅 Milestone: <b>{$milestone->milestone_name}</b>\n";
        
        $progress = round($goal->completion_percentage, 1);
        $message .= "📊 Progress: {$goal->current_progress}/{$goal->target_value} ({$progress}%)\n\n";
        
        if ($milestone->reward_description) {
            $message .= "🎁 Reward: {$milestone->reward_description}\n\n";
        }
        
        $message .= "Excellent progress, team! 🔥";

        return $message;
    }

    /**
     * Build inline keyboard markup from button array
     *
     * @param array $buttons Array of button rows, each row is array of buttons
     * @return InlineKeyboardMarkup
     */
    protected function buildInlineKeyboard(array $buttons): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup($buttons);
    }

    /**
     * Build inline keyboard for goal notifications
     * Provides View Goal, Join Goal (if active), and Refresh buttons
     */
    protected function buildGoalKeyboard(ServerGoal $goal): InlineKeyboardMarkup
    {
        $appUrl = config('app.url');
        $buttons = [];

        // Row 1: View Goal button (URL button)
        $viewUrl = "{$appUrl}/servers/{$goal->server_id}/goals/{$goal->id}";
        $buttons[] = [
            ['text' => '🎯 View Goal', 'url' => $viewUrl]
        ];

        // Row 2: Join (if active) and Refresh
        $row2 = [];
        if ($goal->isActive() && $goal->status === 'active') {
            $row2[] = ['text' => '✅ Join Goal', 'url' => $viewUrl];
        }
        $row2[] = ['text' => '🔄 Refresh', 'callback_data' => "refresh:goal:{$goal->id}"];
        $buttons[] = $row2;

        return $this->buildInlineKeyboard($buttons);
    }

    /**
     * Build inline keyboard for team notifications
     * Provides View Team, Join Team (if recruiting), and Refresh buttons
     */
    protected function buildTeamKeyboard(Team $team): InlineKeyboardMarkup
    {
        $appUrl = config('app.url');
        $buttons = [];

        // Row 1: View Team button
        $viewUrl = "{$appUrl}/teams/{$team->id}";
        $buttons[] = [
            ['text' => '👥 View Team', 'url' => $viewUrl]
        ];

        // Row 2: Join (if recruiting and not full) and Refresh
        $row2 = [];
        if ($team->isRecruiting() && !$team->isFull()) {
            $row2[] = ['text' => '✅ Join Team', 'url' => $viewUrl];
        }
        $row2[] = ['text' => '🔄 Refresh', 'callback_data' => "refresh:team:{$team->id}"];
        $buttons[] = $row2;

        return $this->buildInlineKeyboard($buttons);
    }

    /**
     * Build goal status message for refresh callback
     */
    protected function buildGoalStatusMessage(ServerGoal $goal): string
    {
        $message = "📊 <b>Goal Status</b>\n\n";
        $message .= "🎯 <b>{$goal->title}</b>\n";

        if ($goal->game_name) {
            $message .= "🎮 Game: {$goal->game_name}\n";
        }

        $progress = round($goal->completion_percentage, 1);
        $message .= "📈 Progress: {$goal->current_progress}/{$goal->target_value} ({$progress}%)\n";
        $message .= "👥 Participants: {$goal->participant_count}\n";
        $message .= "📋 Status: " . ucfirst($goal->status) . "\n";

        if ($goal->deadline) {
            $daysLeft = now()->diffInDays($goal->deadline, false);
            if ($daysLeft >= 0) {
                $message .= "⏰ {$daysLeft} days remaining\n";
            } else {
                $message .= "⏰ Deadline passed\n";
            }
        }

        return $message;
    }

    /**
     * Build team status message for refresh callback
     */
    protected function buildTeamStatusMessage(Team $team): string
    {
        $message = "📊 <b>Team Status</b>\n\n";
        $message .= "👥 <b>{$team->name}</b>\n";

        if ($team->game_name) {
            $message .= "🎮 Game: {$team->game_name}\n";
        }

        $message .= "📈 Members: {$team->current_size}/{$team->max_size}\n";
        $message .= "🏆 Skill Level: " . ucfirst($team->skill_level ?? 'Any') . "\n";
        $message .= "📋 Status: " . ucfirst($team->status) . "\n";

        // Show recruiting status
        if ($team->isRecruiting() && !$team->isFull()) {
            $spotsLeft = $team->max_size - $team->current_size;
            $message .= "🔓 Recruiting: {$spotsLeft} spot(s) available\n";
        } elseif ($team->isFull()) {
            $message .= "🔒 Team is full\n";
        }

        // List first 5 members
        if ($team->activeMembers && $team->activeMembers->count() > 0) {
            $message .= "\n<b>Members:</b>\n";
            foreach ($team->activeMembers->take(5) as $member) {
                $role = $member->game_role ? " ({$member->game_role})" : "";
                $message .= "• {$member->user->display_name}{$role}\n";
            }
            if ($team->activeMembers->count() > 5) {
                $remaining = $team->activeMembers->count() - 5;
                $message .= "• <i>+{$remaining} more...</i>\n";
            }
        }

        return $message;
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $url): bool
    {
        if (!$this->bot) {
            Log::warning('Telegram bot not available (cURL issue) - webhook not set');
            return false;
        }
        
        try {
            $this->bot->setWebhook($url);
            Log::info('Telegram webhook set successfully', ['url' => $url]);
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to set Telegram webhook', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): bool
    {
        if (!$this->bot) {
            Log::warning('Telegram bot not available (cURL issue) - webhook not removed');
            return false;
        }
        
        try {
            $this->bot->deleteWebhook();
            Log::info('Telegram webhook removed successfully');
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to remove Telegram webhook', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get bot info
     */
    public function getBotInfo(): ?array
    {
        if (!$this->bot) {
            Log::warning('Telegram bot not available (cURL issue) - bot info not available');
            return null;
        }
        
        try {
            $me = $this->bot->getMe();
            return [
                'id' => $me->getId(),
                'first_name' => $me->getFirstName(),
                'username' => $me->getUsername(),
                'can_join_groups' => $me->getCanJoinGroups(),
                'can_read_all_group_messages' => $me->getCanReadAllGroupMessages(),
                'supports_inline_queries' => $me->getSupportsInlineQueries(),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get bot info', ['error' => $e->getMessage()]);
            return null;
        }
    }
}