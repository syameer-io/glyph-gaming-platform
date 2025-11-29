<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Channel;
use App\Services\AgoraService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Voice Channel Controller
 *
 * Handles the dedicated voice channel main view page (Phase 6).
 * Provides a full-screen Discord-inspired voice channel experience
 * with user grid, speaking indicators, and activity integration.
 */
class VoiceChannelController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param AgoraService $agoraService Injected Agora service for voice session management
     */
    public function __construct(private AgoraService $agoraService)
    {
        // Authentication handled by middleware in routes/web.php
    }

    /**
     * Display the voice channel main view.
     *
     * Shows a full-screen voice channel interface with:
     * - Connected users in a responsive grid
     * - Speaking indicators and status icons
     * - Activity display (games, streaming)
     * - Invite friends and choose activity modals
     * - Enhanced control bar
     *
     * @param Server $server The server containing the voice channel
     * @param Channel $channel The voice channel to display
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Server $server, Channel $channel)
    {
        $user = auth()->user();

        // Verify channel belongs to this server
        if ($channel->server_id !== $server->id) {
            Log::warning('Voice channel server mismatch', [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'channel_id' => $channel->id,
                'channel_server_id' => $channel->server_id
            ]);

            return redirect()->route('server.show', $server)
                ->with('error', 'Channel not found in this server.');
        }

        // Verify channel is a voice channel
        if ($channel->type !== 'voice') {
            Log::info('Attempted to access voice view for non-voice channel', [
                'user_id' => $user->id,
                'channel_id' => $channel->id,
                'channel_type' => $channel->type
            ]);

            // Redirect to the text channel view instead
            return redirect()->route('channel.show', [$server, $channel]);
        }

        // Check user membership
        $membership = $server->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            Log::warning('Non-member attempted to access voice channel view', [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'channel_id' => $channel->id
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'You must be a member of this server to view voice channels.');
        }

        // Check if user is banned
        if ($membership->pivot->is_banned) {
            Log::warning('Banned user attempted to access voice channel view', [
                'user_id' => $user->id,
                'server_id' => $server->id,
                'channel_id' => $channel->id
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'You are banned from this server.');
        }

        // Get active participants in voice channel
        $participants = $this->getVoiceParticipants($channel->id);

        // Get user's friends for invite modal
        $friends = $this->getFriendsForInvite($user);

        // Available activities (static for now, can be dynamic in future)
        $activities = $this->getAvailableActivities();

        // Load server with channels for sidebar navigation
        $server->load(['channels' => function ($query) {
            $query->orderBy('type')->orderBy('position');
        }, 'roles' => function ($query) {
            $query->orderBy('position', 'desc');
        }]);

        Log::info('User viewing voice channel', [
            'user_id' => $user->id,
            'server_id' => $server->id,
            'channel_id' => $channel->id,
            'channel_name' => $channel->name,
            'participant_count' => count($participants)
        ]);

        return view('voice.show', [
            'server' => $server,
            'channel' => $channel,
            'participants' => $participants,
            'friends' => $friends,
            'activities' => $activities,
        ]);
    }

    /**
     * Get active participants in a voice channel.
     *
     * @param int $channelId The voice channel ID
     * @return array Array of participant data with user info and status
     */
    private function getVoiceParticipants(int $channelId): array
    {
        try {
            $activeUsers = $this->agoraService->getActiveUsers($channelId);

            // Enhance with additional user data if needed
            return collect($activeUsers)->map(function ($user) {
                return [
                    'id' => $user['id'] ?? $user['user_id'] ?? null,
                    'name' => $user['name'] ?? $user['display_name'] ?? $user['username'] ?? 'Unknown',
                    'avatar' => $user['avatar'] ?? $user['avatar_url'] ?? '/images/default-avatar.png',
                    'isSpeaking' => $user['is_speaking'] ?? false,
                    'isMuted' => $user['is_muted'] ?? false,
                    'isDeafened' => $user['is_deafened'] ?? false,
                    'isStreaming' => $user['is_streaming'] ?? false,
                    'activity' => $user['activity'] ?? null,
                    'joinedAt' => $user['joined_at'] ?? now()->toIso8601String(),
                ];
            })->values()->all();
        } catch (Exception $e) {
            Log::error('Failed to get voice participants', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get user's friends for the invite modal.
     *
     * Returns friends sorted by online status, filtering out
     * those who are already in the voice channel.
     *
     * @param \App\Models\User $user The current user
     * @return \Illuminate\Support\Collection Collection of friend data
     */
    private function getFriendsForInvite($user)
    {
        try {
            // Get accepted friends
            $friends = $user->friends()
                ->wherePivot('status', 'accepted')
                ->with('profile')
                ->get();

            return $friends->map(function ($friend) {
                $status = $friend->profile->status ?? 'offline';
                $isOnline = in_array($status, ['online', 'away', 'dnd']);

                return [
                    'id' => $friend->id,
                    'name' => $friend->display_name,
                    'username' => $friend->username,
                    'avatar' => $friend->profile->avatar_url ?? '/images/default-avatar.png',
                    'status' => $status,
                    'isOnline' => $isOnline,
                    'activity' => $friend->profile->custom_status ?? null,
                ];
            })->sortByDesc('isOnline')->values();
        } catch (Exception $e) {
            Log::error('Failed to get friends for invite', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return collect([]);
        }
    }

    /**
     * Get available activities for voice channels.
     *
     * Returns a static list of activities that can be started
     * in a voice channel. Future enhancement: load from database.
     *
     * @return array Array of activity definitions
     */
    private function getAvailableActivities(): array
    {
        return [
            [
                'id' => 'watch_together',
                'name' => 'Watch Together',
                'description' => 'Watch videos together with friends',
                'icon' => 'play-circle',
                'category' => 'media',
                'popular' => true,
            ],
            [
                'id' => 'chess',
                'name' => 'Chess',
                'description' => 'Play a game of chess',
                'icon' => 'puzzle',
                'category' => 'games',
                'popular' => true,
            ],
            [
                'id' => 'poker',
                'name' => 'Poker Night',
                'description' => 'Play Texas Hold\'em with friends',
                'icon' => 'cards',
                'category' => 'games',
                'popular' => true,
            ],
            [
                'id' => 'sketch',
                'name' => 'Sketch Heads',
                'description' => 'Draw and guess with friends',
                'icon' => 'pencil',
                'category' => 'games',
                'popular' => true,
            ],
            [
                'id' => 'trivia',
                'name' => 'Trivia',
                'description' => 'Test your knowledge',
                'icon' => 'question',
                'category' => 'games',
                'popular' => false,
            ],
            [
                'id' => 'music',
                'name' => 'Listen Together',
                'description' => 'Listen to music together',
                'icon' => 'music',
                'category' => 'media',
                'popular' => false,
            ],
        ];
    }

    /**
     * API: Get voice channel participants.
     *
     * Returns JSON data for real-time participant list updates.
     *
     * @param Channel $channel The voice channel
     * @return JsonResponse
     */
    public function participants(Channel $channel): JsonResponse
    {
        try {
            $user = auth()->user();
            $server = $channel->server;

            // Verify user is a member of the server
            $membership = $server->members()
                ->where('user_id', $user->id)
                ->first();

            if (!$membership || $membership->pivot->is_banned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.'
                ], 403);
            }

            $participants = $this->getVoiceParticipants($channel->id);

            return response()->json([
                'success' => true,
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'participant_count' => count($participants),
                'participants' => $participants,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get voice channel participants', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve participants.'
            ], 500);
        }
    }

    /**
     * API: Send invite to friends.
     *
     * Sends voice channel invite notifications to selected friends.
     *
     * @param Request $request
     * @param Channel $channel
     * @return JsonResponse
     */
    public function invite(Request $request, Channel $channel): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
            ]);

            $user = auth()->user();
            $server = $channel->server;

            // Verify user is in the voice channel
            $activeSession = $this->agoraService->getActiveSession($user->id, $channel->id);
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be in the voice channel to send invites.'
                ], 400);
            }

            $invitedCount = 0;
            foreach ($validated['user_ids'] as $userId) {
                // TODO: Send notification to user (can integrate with existing notification system)
                // For now, just log the invite
                Log::info('Voice channel invite sent', [
                    'from_user_id' => $user->id,
                    'to_user_id' => $userId,
                    'channel_id' => $channel->id,
                    'server_id' => $server->id
                ]);
                $invitedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Invited {$invitedCount} friend(s) to the voice channel.",
                'invited_count' => $invitedCount,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send voice channel invite', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invites.'
            ], 500);
        }
    }
}
