<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\TelegramBotService;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TelegramController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle incoming webhook from Telegram
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook secret if configured
            // Note: Only verify if secret_token was set when registering webhook with Telegram
            $webhookSecret = config('services.telegram.webhook_secret');
            $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');

            // Only check secret if one was provided in the request (meaning it was set up with Telegram)
            if ($webhookSecret && $providedSecret) {
                if (!hash_equals($webhookSecret, $providedSecret)) {
                    Log::warning('Telegram webhook: Invalid secret token');
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
            }

            // Process the update
            $update = $request->all();
            
            Log::info('Received Telegram webhook', [
                'update_id' => $update['update_id'] ?? null,
                'has_message' => isset($update['message'])
            ]);

            $this->telegramService->processWebhook($update);

            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Error processing Telegram webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Set webhook URL for the bot
     */
    public function setWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $success = $this->telegramService->setWebhook($request->url);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook set successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to set webhook'
        ], 500);
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): JsonResponse
    {
        $success = $this->telegramService->removeWebhook();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook removed successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove webhook'
        ], 500);
    }

    /**
     * Get bot information
     */
    public function getBotInfo(): JsonResponse
    {
        $botInfo = $this->telegramService->getBotInfo();

        if ($botInfo) {
            return response()->json([
                'success' => true,
                'bot_info' => $botInfo
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to get bot information'
        ], 500);
    }

    /**
     * Test sending a message to a specific chat
     */
    public function testMessage(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string|max:4096'
        ]);

        $success = $this->telegramService->sendMessage(
            $request->chat_id,
            $request->message
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Test message sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test message'
        ], 500);
    }

    /**
     * Link a server to Telegram (admin endpoint)
     */
    public function linkServer(Request $request, Server $server): JsonResponse
    {
        $this->authorize('admin', $server);

        $request->validate([
            'chat_id' => 'required|string',
            'chat_title' => 'nullable|string|max:255'
        ]);

        // Check if this chat is already linked to another server
        $existingServer = Server::where('telegram_chat_id', $request->chat_id)
            ->where('id', '!=', $server->id)
            ->first();

        if ($existingServer) {
            return response()->json([
                'success' => false,
                'message' => "This Telegram chat is already linked to '{$existingServer->name}'"
            ], 422);
        }

        // Update server with Telegram info
        $server->update([
            'telegram_chat_id' => $request->chat_id,
            'telegram_linked_at' => now(),
            'telegram_settings' => [
                'chat_title' => $request->chat_title,
                'notifications_enabled' => true,
                'notification_types' => [
                    'goal_completed' => true,
                    'goal_progress' => true,
                    'new_goal' => true,
                    'user_joined' => true,
                    'milestone_reached' => true,
                ]
            ]
        ]);

        // Send welcome message to the Telegram chat
        $welcomeMessage = "✅ <b>Successfully linked to {$server->name}!</b>\n\n";
        $welcomeMessage .= "🎮 Server: <b>{$server->name}</b>\n";
        $welcomeMessage .= "👥 Members: " . $server->members()->count() . "\n";
        $welcomeMessage .= "🎯 Active Goals: " . $server->goals()->where('status', 'active')->count() . "\n\n";
        $welcomeMessage .= "I'll now send notifications about goal updates and achievements! 🚀\n\n";
        $welcomeMessage .= "Use /goals to see current active goals.";

        $this->telegramService->sendMessage($request->chat_id, $welcomeMessage);

        return response()->json([
            'success' => true,
            'message' => 'Server linked to Telegram successfully',
            'server' => $server->fresh()
        ]);
    }

    /**
     * Unlink a server from Telegram
     */
    public function unlinkServer(Request $request, Server $server): JsonResponse
    {
        $this->authorize('admin', $server);

        if (!$server->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Server is not linked to Telegram'
            ], 422);
        }

        // Send goodbye message
        $goodbyeMessage = "👋 <b>{$server->name}</b> has been unlinked from this chat.\n\n";
        $goodbyeMessage .= "You will no longer receive goal notifications.\n\n";
        $goodbyeMessage .= "To relink, use /link {$server->invite_code} or contact your server admin.";

        $this->telegramService->sendMessage($server->telegram_chat_id, $goodbyeMessage);

        // Remove Telegram info from server
        $server->update([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
            'telegram_settings' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Server unlinked from Telegram successfully'
        ]);
    }

    /**
     * Update Telegram notification settings for a server
     */
    public function updateNotificationSettings(Request $request, Server $server): JsonResponse
    {
        $this->authorize('admin', $server);

        if (!$server->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'Server is not linked to Telegram'
            ], 422);
        }

        $request->validate([
            'notifications_enabled' => 'required|boolean',
            'notification_types' => 'required|array',
            'notification_types.goal_completed' => 'boolean',
            'notification_types.goal_progress' => 'boolean',
            'notification_types.new_goal' => 'boolean',
            'notification_types.user_joined' => 'boolean',
            'notification_types.milestone_reached' => 'boolean',
        ]);

        $settings = $server->telegram_settings ?? [];
        $settings['notifications_enabled'] = $request->notifications_enabled;
        $settings['notification_types'] = $request->notification_types;

        $server->update(['telegram_settings' => $settings]);

        return response()->json([
            'success' => true,
            'message' => 'Telegram notification settings updated successfully',
            'settings' => $settings
        ]);
    }

    /**
     * Get Telegram status for a server
     */
    public function getServerStatus(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $status = [
            'is_linked' => !empty($server->telegram_chat_id),
            'chat_id' => $server->telegram_chat_id,
            'linked_at' => $server->telegram_linked_at,
            'settings' => $server->telegram_settings ?? [],
        ];

        if ($status['is_linked']) {
            // Get recent activity stats
            $status['stats'] = [
                'total_goals' => $server->goals()->count(),
                'active_goals' => $server->goals()->where('status', 'active')->count(),
                'completed_goals' => $server->goals()->where('status', 'completed')->count(),
                'total_participants' => $server->goals()
                    ->withCount('participants')
                    ->get()
                    ->sum('participants_count'),
            ];
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}