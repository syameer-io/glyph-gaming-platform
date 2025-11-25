<?php

namespace App\Services;

use App\Models\GameLobby;
use App\Models\GameJoinConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class GameLobbyService
{
    /**
     * Create a new lobby for user and game
     *
     * UPDATED: No longer requires user to have gaming preferences for the game.
     * Users can create lobbies for any game with enabled join configurations.
     *
     * @param User $user The user creating the lobby
     * @param int $gameIdOrAppId The Steam App ID (e.g., 730, 570, 230410)
     * @param array $data Lobby data (varies by join method)
     * @return GameLobby The created lobby
     * @throws ValidationException If lobby data is invalid or duplicate exists
     */
    public function createLobby(User $user, int $gameIdOrAppId, array $data): GameLobby
    {
        // CRITICAL ARCHITECTURE NOTE:
        // The game_id throughout the system directly uses Steam App IDs
        // There is no separate "games" table - we use Steam App IDs as the identifier
        // game_lobbies.game_id = Steam App ID (730, 570, 230410, etc.)
        //
        // FLEXIBLE GAME SUPPORT:
        // Users can now create lobbies for ANY game with enabled join configurations,
        // not just games in their gaming preferences. This allows users to organize
        // gaming sessions even for games they don't own or haven't played yet.
        $gameId = $gameIdOrAppId;

        Log::debug('Creating lobby for user', [
            'user_id' => $user->id,
            'steam_app_id' => $gameId,
            'join_method' => $data['join_method'] ?? 'unknown',
        ]);

        // Check for existing active lobby for this user and game
        $existingLobby = GameLobby::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->where('is_active', true)
            ->first();

        if ($existingLobby) {
            // Delete existing lobby before creating new one
            // (Hard delete to avoid unique constraint violation on user+game+is_active)
            Log::debug('Deleting existing lobby', [
                'existing_lobby_id' => $existingLobby->id,
            ]);
            $existingLobby->delete();
        }

        // Get join configuration to determine expiration
        $configuration = GameJoinConfiguration::where('game_id', $gameId)
            ->where('join_method', $data['join_method'])
            ->where('is_enabled', true)
            ->first();

        // Prepare lobby data
        $lobbyData = array_merge([
            'user_id' => $user->id,
            'game_id' => $gameId,
        ], $data);

        // Set expiration timestamp
        if ($configuration && $configuration->expiration_minutes !== null) {
            $lobbyData['expires_at'] = now()->addMinutes($configuration->expiration_minutes);
        } else {
            $lobbyData['expires_at'] = null; // Persistent lobby
        }

        // Parse steam lobby link if provided
        if (isset($data['steam_lobby_link']) && $data['join_method'] === 'steam_lobby') {
            $this->parseSteamLobbyLink($data['steam_lobby_link'], $lobbyData);
        }

        // Create the lobby
        $lobby = GameLobby::create($lobbyData);

        Log::info('Game lobby created', [
            'user_id' => $user->id,
            'game_id' => $gameId,
            'join_method' => $data['join_method'],
            'lobby_id' => $lobby->id,
        ]);

        return $lobby;
    }

    /**
     * Update an existing lobby
     *
     * @param GameLobby $lobby The lobby to update
     * @param array $data Updated lobby data
     * @return bool Success status
     */
    public function updateLobby(GameLobby $lobby, array $data): bool
    {
        // Parse steam lobby link if provided
        if (isset($data['steam_lobby_link']) && $lobby->join_method === 'steam_lobby') {
            $this->parseSteamLobbyLink($data['steam_lobby_link'], $data);
        }

        // Don't allow changing join_method or game_id
        unset($data['join_method'], $data['game_id'], $data['user_id']);

        $updated = $lobby->update($data);

        if ($updated) {
            Log::info('Game lobby updated', [
                'lobby_id' => $lobby->id,
                'user_id' => $lobby->user_id,
            ]);
        }

        return $updated;
    }

    /**
     * Clear/deactivate a lobby
     *
     * CRITICAL FIX: Hard delete instead of soft delete (is_active=0)
     * Reason: MySQL unique constraint on (user_id, game_id, is_active) doesn't support
     * partial indexes, so multiple inactive lobbies with same user+game would violate constraint.
     * Since there's no business need to keep inactive lobbies, we delete them completely.
     *
     * @param GameLobby $lobby The lobby to clear
     * @return bool Success status
     */
    public function clearLobby(GameLobby $lobby): bool
    {
        try {
            $lobbyId = $lobby->id;
            $userId = $lobby->user_id;

            $deleted = $lobby->delete();

            if ($deleted) {
                Log::info('Game lobby cleared', [
                    'lobby_id' => $lobbyId,
                    'user_id' => $userId,
                ]);
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to delete lobby', [
                'lobby_id' => $lobby->id,
                'user_id' => $lobby->user_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all active lobbies for a user
     *
     * UPDATED: Now eager loads both gamingPreference (if user owns game)
     * and gameJoinConfiguration (always available) for flexible game info
     *
     * @param User $user The user
     * @return Collection Collection of active GameLobby instances
     */
    public function getUserActiveLobbies(User $user): Collection
    {
        return GameLobby::where('user_id', $user->id)
            ->active()
            ->notExpired()
            ->with(['gamingPreference', 'gameJoinConfiguration'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active lobby for user and specific game
     *
     * @param User $user The user
     * @param int $gameId The game ID
     * @return GameLobby|null The lobby or null if not found
     */
    public function getUserGameLobby(User $user, int $gameId): ?GameLobby
    {
        return GameLobby::where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->active()
            ->notExpired()
            ->first();
    }

    /**
     * Generate join link based on lobby join method
     *
     * @param GameLobby $lobby The lobby
     * @return string|null The join link/code or null
     */
    public function generateJoinLink(GameLobby $lobby): ?string
    {
        return $lobby->generateJoinLink();
    }

    /**
     * Validate lobby data based on join method
     *
     * @param string $joinMethod The join method
     * @param array $data The data to validate
     * @return array Validation errors (empty if valid)
     */
    public function validateLobbyData(string $joinMethod, array $data): array
    {
        $errors = [];

        switch ($joinMethod) {
            case 'steam_lobby':
                if (empty($data['steam_lobby_link'])) {
                    $errors['steam_lobby_link'] = 'Steam lobby link is required';
                } elseif (!preg_match('/^steam:\/\/joinlobby\/\d+\/\d+\/\d+$/', $data['steam_lobby_link'])) {
                    $errors['steam_lobby_link'] = 'Invalid Steam lobby link format';
                }
                break;

            case 'steam_connect':
                if (empty($data['server_ip'])) {
                    $errors['server_ip'] = 'Server IP is required';
                }
                if (empty($data['server_port'])) {
                    $errors['server_port'] = 'Server port is required';
                } elseif (!is_numeric($data['server_port']) || $data['server_port'] < 1 || $data['server_port'] > 65535) {
                    $errors['server_port'] = 'Invalid port number (1-65535)';
                }
                break;

            case 'lobby_code':
                if (empty($data['lobby_code'])) {
                    $errors['lobby_code'] = 'Lobby code is required';
                }
                break;

            case 'join_command':
                if (empty($data['join_command'])) {
                    $errors['join_command'] = 'Join command is required';
                }
                break;

            case 'private_match':
                if (empty($data['match_name'])) {
                    $errors['match_name'] = 'Match name is required';
                }
                break;

            case 'server_address':
                if (empty($data['server_ip'])) {
                    $errors['server_ip'] = 'Server address is required';
                }
                if (empty($data['server_port'])) {
                    $errors['server_port'] = 'Server port is required';
                } elseif (!is_numeric($data['server_port']) || $data['server_port'] < 1 || $data['server_port'] > 65535) {
                    $errors['server_port'] = 'Invalid port number (1-65535)';
                }
                break;
        }

        return $errors;
    }

    /**
     * Auto-expire old lobbies (scheduled task)
     *
     * @return int Number of lobbies expired
     */
    public function expireOldLobbies(): int
    {
        $expiredCount = GameLobby::active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        if ($expiredCount > 0) {
            Log::info("Auto-expired {$expiredCount} game lobbies");
        }

        return $expiredCount;
    }

    /**
     * Get join instructions for a lobby
     *
     * @param GameLobby $lobby The lobby
     * @return array Join instructions with method-specific details
     */
    public function getJoinInstructions(GameLobby $lobby): array
    {
        return $lobby->getJoinInstructions();
    }

    /**
     * Get available join methods for a game by Steam App ID
     *
     * @param int $gameAppId The Steam App ID (e.g., 730 for CS2, 570 for Dota 2)
     * @return Collection Collection of GameJoinConfiguration instances
     */
    public function getGameJoinMethods(int $gameAppId): Collection
    {
        // CRITICAL ARCHITECTURE NOTE:
        // The game_join_configurations.game_id column directly stores Steam App IDs (not a separate database ID)
        // There is no separate "games" table - we use Steam App IDs directly throughout the system
        // game_join_configurations.game_id = Steam App ID (730, 570, 230410, etc.)

        Log::debug('Fetching join methods for game', [
            'steam_app_id' => $gameAppId,
        ]);

        // Query game_join_configurations directly using Steam App ID
        $configurations = GameJoinConfiguration::where('game_id', $gameAppId)
            ->where('is_enabled', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($configurations->isEmpty()) {
            Log::warning('No join configurations found for Steam App ID', [
                'steam_app_id' => $gameAppId,
            ]);
        } else {
            Log::debug('Found join configurations', [
                'steam_app_id' => $gameAppId,
                'count' => $configurations->count(),
                'methods' => $configurations->pluck('join_method')->toArray(),
            ]);
        }

        return $configurations;
    }

    /**
     * Parse Steam lobby link and extract components
     *
     * @param string $lobbyLink The Steam lobby link
     * @param array &$data Reference to data array to populate
     * @return void
     */
    private function parseSteamLobbyLink(string $lobbyLink, array &$data): void
    {
        // Format: steam://joinlobby/[appid]/[lobbyid]/[profileid]
        if (preg_match('/^steam:\/\/joinlobby\/(\d+)\/(\d+)\/(\d+)$/', $lobbyLink, $matches)) {
            $data['steam_app_id'] = (int) $matches[1];
            $data['steam_lobby_id'] = $matches[2];
            $data['steam_profile_id'] = $matches[3];
        }
    }

    /**
     * Clean up very old inactive lobbies (scheduled task)
     *
     * @param int $daysOld Number of days to consider as old
     * @return int Number of lobbies deleted
     */
    public function cleanupOldLobbies(int $daysOld = 7): int
    {
        $deletedCount = GameLobby::where('is_active', false)
            ->where('updated_at', '<', now()->subDays($daysOld))
            ->delete();

        if ($deletedCount > 0) {
            Log::info("Cleaned up {$deletedCount} old inactive lobbies");
        }

        return $deletedCount;
    }
}
