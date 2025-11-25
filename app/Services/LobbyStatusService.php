<?php

namespace App\Services;

use App\Models\GameLobby;
use App\Models\User;
use App\Events\LobbyExpired;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * LobbyStatusService provides bulk lobby status operations
 *
 * This service is responsible for:
 * - Bulk fetching active lobbies for multiple users in a single query (prevents N+1)
 * - Formatting lobby data for consistent frontend display
 * - Managing cache invalidation for multiple users
 * - Providing performant access to lobby status across the application
 */
class LobbyStatusService
{
    /**
     * Get active lobbies for multiple users in a single optimized query
     *
     * This method prevents N+1 queries by loading all lobbies at once and
     * then grouping them by user. Much more efficient than loading lobbies
     * for each user individually.
     *
     * @param array $userIds Array of user IDs to fetch lobbies for
     * @return array Associative array with user_id as key and lobby data as value
     */
    public function getActiveLobbiesForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        // Validate and sanitize user IDs (prevent injection)
        $userIds = array_filter(array_map('intval', $userIds));

        if (count($userIds) > 100) {
            Log::warning('Bulk lobby fetch exceeded limit', [
                'requested' => count($userIds),
                'limit' => 100
            ]);
            $userIds = array_slice($userIds, 0, 100);
        }

        Log::debug('Fetching active lobbies for users', [
            'user_count' => count($userIds),
            'user_ids' => $userIds
        ]);

        // Single query to fetch all active lobbies for all users
        $lobbies = GameLobby::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('gamingPreference:id,game_appid,game_name')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group lobbies by user_id and take only the most recent for each user
        $lobbyMap = [];
        foreach ($lobbies as $lobby) {
            // Only store the first (most recent) lobby for each user
            if (!isset($lobbyMap[$lobby->user_id])) {
                $lobbyMap[$lobby->user_id] = $this->formatLobbyForDisplay($lobby);
            }
        }

        Log::debug('Active lobbies fetched', [
            'users_with_lobbies' => count($lobbyMap),
            'total_lobbies_fetched' => $lobbies->count()
        ]);

        return $lobbyMap;
    }

    /**
     * Format a lobby for consistent frontend display
     *
     * @param GameLobby $lobby The lobby to format
     * @return array Formatted lobby data
     */
    public function formatLobbyForDisplay(GameLobby $lobby): array
    {
        try {
            // Load gaming preference if not already loaded
            if (!$lobby->relationLoaded('gamingPreference')) {
                $lobby->load('gamingPreference:id,game_appid,game_name');
            }

            // Generate game icon URL (using fallback)
            $gameIcon = asset('images/default-game.png');

            // If we have the game_appid, we could potentially construct a Steam icon URL
            // For now, use a simple default fallback
            if ($lobby->game_id) {
                // Future: Could integrate with Steam API to get proper icon
                // Format: https://media.steampowered.com/steamcommunity/public/images/apps/{appid}/{icon_hash}.jpg
                $gameIcon = asset('images/default-game.png');
            }

            return [
                'id' => $lobby->id,
                'user_id' => $lobby->user_id,
                'game_id' => $lobby->game_id,
                'game_name' => $lobby->gamingPreference->game_name ?? 'Unknown Game',
                'game_icon' => $gameIcon,
                'join_method' => $lobby->join_method,
                'join_link' => $lobby->generateJoinLink(),
                'display_format' => $lobby->getDisplayFormat(),
                'time_remaining_minutes' => $lobby->timeRemaining(),
                'is_expiring_soon' => $lobby->timeRemaining() && $lobby->timeRemaining() < 5,
                'is_expired' => false,
                'is_persistent' => $lobby->expires_at === null,
                'is_active' => $lobby->is_active,
                'created_at' => $lobby->created_at->toIso8601String(),
                'expires_at' => $lobby->expires_at?->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to format lobby for display', [
                'lobby_id' => $lobby->id,
                'error' => $e->getMessage()
            ]);

            // Return minimal safe data on error
            return [
                'id' => $lobby->id,
                'user_id' => $lobby->user_id,
                'error' => 'Failed to format lobby data'
            ];
        }
    }

    /**
     * Invalidate cached lobby status for multiple users
     *
     * Useful when bulk operations affect multiple users' lobby statuses
     *
     * @param array $userIds Array of user IDs whose caches should be invalidated
     * @return int Number of caches invalidated
     */
    public function invalidateUserCaches(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $userIds = array_filter(array_map('intval', $userIds));
        $invalidated = 0;

        foreach ($userIds as $userId) {
            $cacheKey = "user.{$userId}.active_lobby_status";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                $invalidated++;
            }
        }

        Log::debug('User lobby caches invalidated', [
            'user_count' => count($userIds),
            'caches_invalidated' => $invalidated
        ]);

        return $invalidated;
    }

    /**
     * Get active lobby for a single user (with caching)
     *
     * @param int $userId The user ID
     * @return array|null Formatted lobby data or null if no active lobby
     */
    public function getActiveLobbyForUser(int $userId): ?array
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        return $user->getActiveLobbyStatus();
    }

    /**
     * Check if user has an active lobby
     *
     * @param int $userId The user ID
     * @return bool True if user has active lobby, false otherwise
     */
    public function hasActiveLobby(int $userId): bool
    {
        $cacheKey = "user.{$userId}.has_active_lobby";

        return Cache::remember($cacheKey, now()->addMinutes(2), function() use ($userId) {
            return GameLobby::where('user_id', $userId)
                ->where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();
        });
    }

    /**
     * Expire lobbies that have passed their expiration time
     * This should be called by a scheduled job
     *
     * @return int Number of lobbies expired
     */
    public function expireOldLobbies(): int
    {
        $expiredLobbies = GameLobby::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $expiredCount = 0;
        $userIds = [];

        foreach ($expiredLobbies as $lobby) {
            if ($lobby->markAsExpired()) {
                $expiredCount++;
                $userIds[] = $lobby->user_id;

                // Dispatch LobbyExpired event for real-time updates
                event(new LobbyExpired($lobby->id, $lobby->user_id));
            }
        }

        // Invalidate caches for affected users
        if (!empty($userIds)) {
            $this->invalidateUserCaches(array_unique($userIds));
        }

        if ($expiredCount > 0) {
            Log::info('Expired old lobbies', [
                'count' => $expiredCount,
                'affected_users' => count(array_unique($userIds))
            ]);
        }

        return $expiredCount;
    }
}
