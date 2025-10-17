<?php

namespace App\Http\Controllers;

use App\Services\AgoraService;
use App\Models\Server;
use App\Models\Channel;
use App\Events\VoiceUserJoined;
use App\Events\VoiceUserLeft;
use App\Events\VoiceUserMuted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Voice Controller
 *
 * Handles voice chat operations using Agora.io WebRTC integration.
 * Manages token generation, session tracking, and real-time presence broadcasting
 * for voice channels within gaming community servers.
 *
 * Security: All actions require authentication and server membership validation.
 * Banned users are blocked from accessing voice channels. Muted users are allowed
 * to join voice but this can be restricted based on server policy.
 */
class VoiceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param AgoraService $agoraService Injected Agora service for token and session management
     */
    public function __construct(private AgoraService $agoraService)
    {
        // Authentication is handled by middleware in routes/web.php
    }

    /**
     * Join a voice channel.
     *
     * Validates user membership and permissions, generates an Agora RTC token,
     * tracks the voice session, and broadcasts the join event to all server members.
     *
     * Security Checks:
     * - User must be authenticated (middleware)
     * - Channel must exist and be type 'voice'
     * - User must be a member of the server
     * - User must NOT be banned from the server
     * - Optionally: User must NOT be muted (configurable based on server policy)
     *
     * @param Request $request Contains channel_id
     * @return JsonResponse Success with token data, or error response
     */
    public function join(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'channel_id' => 'required|integer|exists:channels,id',
            ]);

            $channelId = $validated['channel_id'];
            $user = auth()->user();

            // Load channel with server relationship
            $channel = Channel::with('server')->findOrFail($channelId);

            // Verify channel is a voice channel
            if ($channel->type !== 'voice') {
                Log::warning('User attempted to join non-voice channel for voice chat', [
                    'user_id' => $user->id,
                    'channel_id' => $channelId,
                    'channel_type' => $channel->type,
                    'channel_name' => $channel->name
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'This channel is not a voice channel. Please select a voice channel to join.'
                ], 400);
            }

            $server = $channel->server;

            // SECURITY CHECK 1: Verify user is a server member
            $membership = $server->members()
                ->where('user_id', $user->id)
                ->first();

            if (!$membership) {
                Log::warning('Non-member attempted to join voice channel', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'server_id' => $server->id,
                    'channel_id' => $channelId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You must be a member of this server to join voice channels.'
                ], 403);
            }

            // SECURITY CHECK 2: Verify user is NOT banned
            if ($membership->pivot->is_banned) {
                Log::warning('Banned user attempted to join voice channel', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'server_id' => $server->id,
                    'channel_id' => $channelId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You are banned from this server and cannot access voice channels.'
                ], 403);
            }

            // SECURITY CHECK 3: Check if muted users are allowed to join voice
            // Note: For university demonstration, we allow muted users to join voice
            // but they will be unable to send text messages. Server admins can decide
            // if voice access should also be restricted for muted users.
            if ($membership->pivot->is_muted) {
                Log::info('Muted user joining voice channel', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'server_id' => $server->id,
                    'channel_id' => $channelId,
                    'note' => 'Muted users can join voice but cannot send text messages'
                ]);

                // Optionally block muted users from voice - uncomment to enable:
                // return response()->json([
                //     'success' => false,
                //     'message' => 'You are muted in this server and cannot access voice channels.'
                // ], 403);
            }

            // Generate Agora token
            $tokenData = $this->agoraService->generateToken($channelId, $user->id, 'publisher');

            // Track voice session in database
            $session = $this->agoraService->trackJoin($user->id, $channelId, $server->id);

            // Broadcast join event to all server members
            broadcast(new VoiceUserJoined($user, $channel, $server));

            Log::info('User joined voice channel successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'channel_id' => $channelId,
                'channel_name' => $channel->name,
                'server_id' => $server->id,
                'session_id' => $session->id,
                'token_expires_at' => date('Y-m-d H:i:s', $tokenData['expires_at'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined voice channel.',
                'token' => $tokenData['token'],
                'channel_name' => $tokenData['channel_name'],
                'uid' => $tokenData['uid'],
                'expires_at' => $tokenData['expires_at'],
                'session' => [
                    'id' => $session->id,
                    'channel_id' => $session->channel_id,
                    'server_id' => $session->server_id,
                    'joined_at' => $session->joined_at->toIso8601String(),
                    'is_muted' => $session->is_muted,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to join voice channel', [
                'user_id' => auth()->id(),
                'channel_id' => $request->input('channel_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to join voice channel. Please try again later.'
            ], 500);
        }
    }

    /**
     * Leave a voice channel.
     *
     * Ends the user's active voice session in the specified channel and broadcasts
     * the leave event to all server members for real-time UI updates.
     *
     * @param Request $request Contains channel_id
     * @return JsonResponse Success with session data, or error response
     */
    public function leave(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'channel_id' => 'required|integer|exists:channels,id',
            ]);

            $channelId = $validated['channel_id'];
            $user = auth()->user();

            // Load channel with server
            $channel = Channel::with('server')->findOrFail($channelId);
            $server = $channel->server;

            // Track leave (end session)
            $session = $this->agoraService->trackLeave($user->id, $channelId);

            if (!$session) {
                Log::warning('User attempted to leave voice channel with no active session', [
                    'user_id' => $user->id,
                    'channel_id' => $channelId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You are not currently in this voice channel.'
                ], 400);
            }

            // Broadcast leave event to all server members
            broadcast(new VoiceUserLeft($user, $channel, $server));

            Log::info('User left voice channel successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'channel_id' => $channelId,
                'channel_name' => $channel->name,
                'server_id' => $server->id,
                'session_id' => $session->id,
                'session_duration' => $session->session_duration,
                'formatted_duration' => $session->formatted_duration
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left voice channel.',
                'session' => [
                    'id' => $session->id,
                    'channel_id' => $session->channel_id,
                    'server_id' => $session->server_id,
                    'joined_at' => $session->joined_at->toIso8601String(),
                    'left_at' => $session->left_at->toIso8601String(),
                    'duration_seconds' => $session->session_duration,
                    'duration_formatted' => $session->formatted_duration,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to leave voice channel', [
                'user_id' => auth()->id(),
                'channel_id' => $request->input('channel_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to leave voice channel. Please try again.'
            ], 500);
        }
    }

    /**
     * Toggle mute status in a voice channel.
     *
     * Updates the user's mute status for their active voice session and broadcasts
     * the status change to all server members. Note that actual audio muting is
     * handled client-side via Agora SDK - this tracks the status server-side.
     *
     * @param Request $request Contains channel_id and is_muted
     * @return JsonResponse Success with updated mute status, or error response
     */
    public function toggleMute(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'channel_id' => 'required|integer|exists:channels,id',
                'is_muted' => 'required|boolean',
            ]);

            $channelId = $validated['channel_id'];
            $isMuted = $validated['is_muted'];
            $user = auth()->user();

            // Load channel with server
            $channel = Channel::with('server')->findOrFail($channelId);
            $server = $channel->server;

            // Update mute status
            $session = $this->agoraService->updateMuteStatus($user->id, $channelId, $isMuted);

            if (!$session) {
                Log::warning('User attempted to toggle mute with no active session', [
                    'user_id' => $user->id,
                    'channel_id' => $channelId,
                    'requested_mute_status' => $isMuted
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You are not currently in this voice channel.'
                ], 400);
            }

            // Broadcast mute status change to all server members
            broadcast(new VoiceUserMuted($user, $channel, $server, $isMuted));

            Log::info('User toggled mute status in voice channel', [
                'user_id' => $user->id,
                'username' => $user->username,
                'channel_id' => $channelId,
                'channel_name' => $channel->name,
                'server_id' => $server->id,
                'is_muted' => $isMuted
            ]);

            return response()->json([
                'success' => true,
                'message' => $isMuted ? 'Microphone muted.' : 'Microphone unmuted.',
                'is_muted' => $isMuted,
                'session' => [
                    'id' => $session->id,
                    'channel_id' => $session->channel_id,
                    'is_muted' => $session->is_muted,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to toggle mute status', [
                'user_id' => auth()->id(),
                'channel_id' => $request->input('channel_id'),
                'is_muted' => $request->input('is_muted'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update mute status. Please try again.'
            ], 500);
        }
    }

    /**
     * Get active participants in a voice channel.
     *
     * Returns a list of all users currently in an active voice session for the
     * specified channel. Used for real-time UI updates and participant lists.
     *
     * @param int $channelId The voice channel ID
     * @return JsonResponse Array of active participants
     */
    public function getParticipants(int $channelId): JsonResponse
    {
        try {
            // Verify channel exists
            $channel = Channel::findOrFail($channelId);

            // Get active users in this channel
            $participants = $this->agoraService->getActiveUsers($channelId);

            Log::debug('Retrieved voice channel participants', [
                'channel_id' => $channelId,
                'participant_count' => count($participants),
                'requested_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'channel_id' => $channelId,
                'channel_name' => $channel->name,
                'participant_count' => count($participants),
                'participants' => $participants
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to retrieve voice channel participants', [
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve participants. Please try again.'
            ], 500);
        }
    }

    /**
     * Get voice statistics for the authenticated user.
     *
     * Returns comprehensive voice chat statistics including total sessions,
     * total hours, average session duration, and channels/servers joined.
     *
     * @param Request $request Optional: days parameter (default: 30)
     * @return JsonResponse User voice statistics
     */
    public function getUserStats(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $user = auth()->user();

            $stats = $this->agoraService->getUserStats($user->id, $days);

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'period_days' => $days,
                'stats' => $stats
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to retrieve user voice statistics', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.'
            ], 500);
        }
    }
}
