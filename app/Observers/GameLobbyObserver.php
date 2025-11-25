<?php

namespace App\Observers;

use App\Models\GameLobby;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GameLobbyObserver handles cache invalidation for lobby-related caches
 *
 * This ensures that when lobbies are created, updated, or deleted,
 * the cached lobby status for users is immediately invalidated to prevent
 * displaying stale data in the UI.
 */
class GameLobbyObserver
{
    /**
     * Handle the GameLobby "created" event.
     * Invalidate user's active lobby cache when new lobby is created
     */
    public function created(GameLobby $gameLobby): void
    {
        $this->invalidateUserCache($gameLobby->user_id);

        Log::info('Lobby created, cache invalidated', [
            'lobby_id' => $gameLobby->id,
            'user_id' => $gameLobby->user_id,
            'game_id' => $gameLobby->game_id,
        ]);
    }

    /**
     * Handle the GameLobby "updated" event.
     * Invalidate user's active lobby cache when lobby status changes
     */
    public function updated(GameLobby $gameLobby): void
    {
        $this->invalidateUserCache($gameLobby->user_id);

        // Log important status changes
        if ($gameLobby->wasChanged('is_active')) {
            Log::info('Lobby status changed, cache invalidated', [
                'lobby_id' => $gameLobby->id,
                'user_id' => $gameLobby->user_id,
                'is_active' => $gameLobby->is_active,
            ]);
        }
    }

    /**
     * Handle the GameLobby "deleted" event.
     * Invalidate user's active lobby cache when lobby is deleted
     */
    public function deleted(GameLobby $gameLobby): void
    {
        $this->invalidateUserCache($gameLobby->user_id);

        Log::info('Lobby deleted, cache invalidated', [
            'lobby_id' => $gameLobby->id,
            'user_id' => $gameLobby->user_id,
        ]);
    }

    /**
     * Handle the GameLobby "restored" event.
     * Invalidate user's active lobby cache when soft-deleted lobby is restored
     */
    public function restored(GameLobby $gameLobby): void
    {
        $this->invalidateUserCache($gameLobby->user_id);

        Log::info('Lobby restored, cache invalidated', [
            'lobby_id' => $gameLobby->id,
            'user_id' => $gameLobby->user_id,
        ]);
    }

    /**
     * Handle the GameLobby "force deleted" event.
     * Invalidate user's active lobby cache when lobby is permanently deleted
     */
    public function forceDeleted(GameLobby $gameLobby): void
    {
        $this->invalidateUserCache($gameLobby->user_id);

        Log::info('Lobby force deleted, cache invalidated', [
            'lobby_id' => $gameLobby->id,
            'user_id' => $gameLobby->user_id,
        ]);
    }

    /**
     * Invalidate the active lobby status cache for a specific user
     *
     * @param int $userId The user ID whose cache should be invalidated
     */
    protected function invalidateUserCache(int $userId): void
    {
        $cacheKey = "user.{$userId}.active_lobby_status";
        Cache::forget($cacheKey);
    }
}
