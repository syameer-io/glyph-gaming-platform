<?php

namespace App\Services;

use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;
use App\Models\VoiceSession;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Agora Service
 *
 * Centralized service for Agora.io WebRTC token generation and voice session management.
 * Handles token generation, session tracking, and active user management for voice channels.
 *
 * This service integrates with Agora.io RTC SDK to provide secure voice chat functionality
 * for gaming communities. All voice sessions are tracked in the database for analytics and
 * session duration reporting.
 */
class AgoraService
{
    /**
     * Generate Agora RTC token for voice channel access.
     *
     * Creates a secure RTC token using Agora's token builder that allows a user to join
     * a specific voice channel with publisher privileges (can speak). Tokens are time-limited
     * and expire based on the configured token expiry setting.
     *
     * @param int $channelId The voice channel ID
     * @param int $userId The user ID who will join the channel
     * @param string $role The role type: 'publisher' (can speak) or 'subscriber' (listen only)
     * @return array Token data containing: token, channel_name, uid, expires_at
     * @throws Exception If credentials are missing or token generation fails
     */
    public function generateToken(int $channelId, int $userId, string $role = 'publisher'): array
    {
        try {
            // Retrieve Agora credentials from config
            $appId = config('services.agora.app_id');
            $appCertificate = config('services.agora.app_certificate');
            $tokenExpiry = config('services.agora.token_expiry', 3600);

            // Validate credentials are configured
            if (empty($appId) || empty($appCertificate)) {
                Log::error('Agora credentials missing in configuration', [
                    'app_id_exists' => !empty($appId),
                    'certificate_exists' => !empty($appCertificate)
                ]);
                throw new Exception('Agora credentials are not properly configured. Please check your .env file.');
            }

            // Generate channel name using consistent format
            $channelName = "voice-channel-{$channelId}";

            // Calculate token expiration timestamp
            $expiryTimestamp = time() + $tokenExpiry;

            // Determine role privilege (publisher can speak, subscriber can only listen)
            $rolePrivilege = ($role === 'subscriber')
                ? RtcTokenBuilder::RoleSubscriber
                : RtcTokenBuilder::RolePublisher;

            // Generate the RTC token
            $token = RtcTokenBuilder::buildTokenWithUid(
                $appId,
                $appCertificate,
                $channelName,
                $userId,
                $rolePrivilege,
                $expiryTimestamp
            );

            Log::info('Agora token generated successfully', [
                'channel_id' => $channelId,
                'user_id' => $userId,
                'channel_name' => $channelName,
                'role' => $role,
                'expires_at' => Carbon::createFromTimestamp($expiryTimestamp)->toDateTimeString()
            ]);

            return [
                'token' => $token,
                'channel_name' => $channelName,
                'uid' => $userId,
                'expires_at' => $expiryTimestamp,
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate Agora token', [
                'channel_id' => $channelId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Failed to generate voice channel token: ' . $e->getMessage());
        }
    }

    /**
     * Track user joining a voice channel.
     *
     * Creates a new voice session record when a user joins a voice channel.
     * If the user already has an active session in this channel, returns the existing
     * session instead of creating a duplicate.
     *
     * @param int $userId The user ID joining the channel
     * @param int $channelId The voice channel ID being joined
     * @param int $serverId The server ID where the channel belongs
     * @return VoiceSession The created or existing voice session
     */
    public function trackJoin(int $userId, int $channelId, int $serverId): VoiceSession
    {
        // Check if user already has an active session in this channel
        $existingSession = VoiceSession::getActiveUserSession($userId, $channelId);

        if ($existingSession) {
            Log::info('User already has active voice session in channel', [
                'user_id' => $userId,
                'channel_id' => $channelId,
                'session_id' => $existingSession->id,
                'joined_at' => $existingSession->joined_at->toDateTimeString()
            ]);

            // Load relationships for consistent return format
            return $existingSession->load(['user', 'channel', 'server']);
        }

        // Create new voice session
        $session = VoiceSession::create([
            'user_id' => $userId,
            'channel_id' => $channelId,
            'server_id' => $serverId,
            'agora_channel_name' => "voice-channel-{$channelId}",
            'joined_at' => Carbon::now(),
            'is_muted' => false,
        ]);

        // Load relationships
        $session->load(['user', 'channel', 'server']);

        Log::info('Voice session created', [
            'session_id' => $session->id,
            'user_id' => $userId,
            'channel_id' => $channelId,
            'server_id' => $serverId,
            'joined_at' => $session->joined_at->toDateTimeString()
        ]);

        return $session;
    }

    /**
     * Track user leaving a voice channel.
     *
     * Ends the user's active voice session in the specified channel by setting
     * the left_at timestamp and calculating the total session duration.
     *
     * @param int $userId The user ID leaving the channel
     * @param int $channelId The voice channel ID being left
     * @return VoiceSession|null The ended session, or null if no active session found
     */
    public function trackLeave(int $userId, int $channelId): ?VoiceSession
    {
        // Find user's active session in this channel
        $session = VoiceSession::getActiveUserSession($userId, $channelId);

        if (!$session) {
            Log::warning('No active voice session found to end', [
                'user_id' => $userId,
                'channel_id' => $channelId
            ]);
            return null;
        }

        // End the session (sets left_at and calculates duration)
        $session->endSession();

        // Load relationships
        $session->load(['user', 'channel', 'server']);

        Log::info('Voice session ended', [
            'session_id' => $session->id,
            'user_id' => $userId,
            'channel_id' => $channelId,
            'duration_seconds' => $session->session_duration,
            'formatted_duration' => $session->formatted_duration,
            'joined_at' => $session->joined_at->toDateTimeString(),
            'left_at' => $session->left_at->toDateTimeString()
        ]);

        return $session;
    }

    /**
     * Get all active users in a voice channel.
     *
     * Retrieves all users currently in an active voice session for the specified channel.
     * Returns a simplified array of user data suitable for frontend display.
     *
     * @param int $channelId The voice channel ID
     * @return array Array of active user data with: id, username, avatar_url, is_muted, joined_at
     */
    public function getActiveUsers(int $channelId): array
    {
        // Get all active sessions for this channel with user relationship
        $activeSessions = VoiceSession::getChannelActiveSessions($channelId);

        // Transform to array of user data
        $users = $activeSessions->map(function ($session) {
            return [
                'id' => $session->user->id,
                'username' => $session->user->username,
                'display_name' => $session->user->display_name,
                'avatar_url' => $session->user->profile->avatar_url ?? null,
                'is_muted' => $session->is_muted,
                'joined_at' => $session->joined_at->toIso8601String(),
            ];
        })->toArray();

        Log::debug('Retrieved active users for voice channel', [
            'channel_id' => $channelId,
            'active_user_count' => count($users)
        ]);

        return $users;
    }

    /**
     * Update mute status for a user's voice session.
     *
     * Updates the is_muted flag for the user's active session in the specified channel.
     * This tracks the mute state server-side for analytics and UI synchronization.
     * Note: Actual audio muting is handled client-side via Agora SDK.
     *
     * @param int $userId The user ID to update mute status
     * @param int $channelId The voice channel ID
     * @param bool $isMuted The new mute status (true = muted, false = unmuted)
     * @return VoiceSession|null The updated session, or null if no active session found
     */
    public function updateMuteStatus(int $userId, int $channelId, bool $isMuted): ?VoiceSession
    {
        // Find user's active session in this channel
        $session = VoiceSession::getActiveUserSession($userId, $channelId);

        if (!$session) {
            Log::warning('No active voice session found to update mute status', [
                'user_id' => $userId,
                'channel_id' => $channelId,
                'requested_mute_status' => $isMuted
            ]);
            return null;
        }

        // Update mute status
        $session->is_muted = $isMuted;
        $session->save();

        // Load relationships
        $session->load(['user', 'channel', 'server']);

        Log::info('Voice session mute status updated', [
            'session_id' => $session->id,
            'user_id' => $userId,
            'channel_id' => $channelId,
            'is_muted' => $isMuted
        ]);

        return $session;
    }

    /**
     * Get voice session statistics for a user.
     *
     * Retrieves comprehensive voice chat statistics for a specific user across all servers
     * and channels within a specified time period.
     *
     * @param int $userId The user ID
     * @param int $days The number of days to look back (default: 30)
     * @return array Statistics including total sessions, hours, average duration, channels, servers
     */
    public function getUserStats(int $userId, int $days = 30): array
    {
        return VoiceSession::getUserStats($userId, $days);
    }

    /**
     * Get voice channel activity statistics.
     *
     * Retrieves activity statistics for a specific voice channel including total sessions,
     * unique users, total duration, and peak usage times.
     *
     * @param int $channelId The channel ID
     * @param int $days The number of days to look back (default: 30)
     * @return array Channel activity statistics
     */
    public function getChannelStats(int $channelId, int $days = 30): array
    {
        $sessions = VoiceSession::forChannel($channelId)
            ->whereNotNull('left_at')
            ->where('joined_at', '>=', now()->subDays($days))
            ->with('user')
            ->get();

        $totalDuration = $sessions->sum('session_duration');
        $totalSessions = $sessions->count();
        $uniqueUsers = $sessions->pluck('user_id')->unique()->count();
        $totalHours = round($totalDuration / 3600, 1);
        $averageMinutes = $totalSessions > 0 ? round($totalDuration / $totalSessions / 60) : 0;

        return [
            'total_sessions' => $totalSessions,
            'unique_users' => $uniqueUsers,
            'total_hours' => $totalHours,
            'total_minutes' => round($totalDuration / 60),
            'average_session_minutes' => $averageMinutes,
            'most_active_user' => $sessions->groupBy('user_id')
                ->sortByDesc(fn($userSessions) => $userSessions->count())
                ->first()
                ->first()
                ->user ?? null,
        ];
    }

    /**
     * Clean up stale voice sessions.
     *
     * Finds and ends any voice sessions that are still marked as active but have been
     * running for an unreasonably long time (default: 24 hours). This handles cases
     * where users disconnected without properly leaving the channel.
     *
     * @param int $maxHours Maximum hours a session can be active before being considered stale
     * @return int Number of sessions cleaned up
     */
    public function cleanupStaleSessions(int $maxHours = 24): int
    {
        $staleThreshold = Carbon::now()->subHours($maxHours);

        $staleSessions = VoiceSession::active()
            ->where('joined_at', '<', $staleThreshold)
            ->get();

        $count = 0;
        foreach ($staleSessions as $session) {
            $session->endSession();
            $count++;
        }

        if ($count > 0) {
            Log::info('Cleaned up stale voice sessions', [
                'sessions_cleaned' => $count,
                'threshold_hours' => $maxHours,
                'threshold_time' => $staleThreshold->toDateTimeString()
            ]);
        }

        return $count;
    }
}
