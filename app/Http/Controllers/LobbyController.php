<?php

namespace App\Http\Controllers;

use App\Models\GameLobby;
use App\Services\GameLobbyService;
use App\Http\Requests\CreateLobbyRequest;
use App\Http\Requests\UpdateLobbyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LobbyController extends Controller
{
    protected GameLobbyService $lobbyService;

    public function __construct(GameLobbyService $lobbyService)
    {
        $this->lobbyService = $lobbyService;
        // Note: Auth middleware is applied in routes/api.php
    }

    /**
     * Create a new lobby
     *
     * POST /api/lobbies
     */
    public function store(CreateLobbyRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $lobby = $this->lobbyService->createLobby(
                $user,
                $request->game_id,
                $request->validated()
            );

            // Load relationship for response
            $lobby->load('gamingPreference');

            return response()->json([
                'success' => true,
                'message' => 'Lobby created successfully',
                'lobby' => $lobby,
                'join_link' => $lobby->generateJoinLink(),
                'time_remaining' => $lobby->timeRemaining(),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create lobby', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create lobby: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing lobby
     *
     * PUT /api/lobbies/{lobby}
     */
    public function update(UpdateLobbyRequest $request, GameLobby $lobby): JsonResponse
    {
        try {
            $this->authorize('update', $lobby);

            $updated = $this->lobbyService->updateLobby($lobby, $request->validated());

            if ($updated) {
                $lobby->refresh();
                $lobby->load('gamingPreference');

                return response()->json([
                    'success' => true,
                    'message' => 'Lobby updated successfully',
                    'lobby' => $lobby,
                    'join_link' => $lobby->generateJoinLink(),
                    'time_remaining' => $lobby->timeRemaining(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lobby',
            ], 500);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this lobby',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Failed to update lobby', [
                'lobby_id' => $lobby->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lobby: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete/clear a lobby
     *
     * DELETE /api/lobbies/{lobby}
     */
    public function destroy(GameLobby $lobby): JsonResponse
    {
        try {
            $this->authorize('delete', $lobby);

            $cleared = $this->lobbyService->clearLobby($lobby);

            if ($cleared) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lobby cleared successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear lobby',
            ], 500);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this lobby',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Failed to delete lobby', [
                'lobby_id' => $lobby->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lobby: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all active lobbies for current user
     *
     * GET /api/lobbies/my-lobbies
     */
    public function myLobbies(): JsonResponse
    {
        try {
            $user = Auth::user();
            $lobbies = $this->lobbyService->getUserActiveLobbies($user);

            $lobbyData = $lobbies->map(function ($lobby) {
                return [
                    'id' => $lobby->id,
                    'game_id' => $lobby->game_id,
                    'gaming_preference' => [
                        'game_name' => $lobby->getGameName(),
                    ],
                    'join_method' => $lobby->join_method,
                    // Include all fields needed by frontend
                    'steam_app_id' => $lobby->steam_app_id,
                    'steam_lobby_id' => $lobby->steam_lobby_id,
                    'steam_profile_id' => $lobby->steam_profile_id,
                    'server_ip' => $lobby->server_ip,
                    'server_port' => $lobby->server_port,
                    'lobby_code' => $lobby->lobby_code,
                    'join_command' => $lobby->join_command,
                    'match_name' => $lobby->match_name,
                    'is_active' => $lobby->isActive(),
                    'time_remaining' => $lobby->timeRemaining(),
                    'expires_at' => $lobby->expires_at?->toIso8601String(),
                    'created_at' => $lobby->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'lobbies' => $lobbyData,
                'count' => $lobbies->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch user lobbies', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lobbies: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available join methods for a game
     *
     * GET /api/games/{gameId}/join-methods
     */
    public function getGameJoinMethods(int $gameId): JsonResponse
    {
        try {
            $joinMethods = $this->lobbyService->getGameJoinMethods($gameId);

            // CRITICAL FIX: Use values() to ensure proper JSON array encoding
            // Without values(), Laravel Collection toArray() returns object like {"0": {...}, "1": {...}}
            // With values(), it returns proper array [{...}, {...}]
            $joinMethodsArray = $joinMethods->values()->toArray();

            Log::info('Join methods API response', [
                'game_id' => $gameId,
                'count' => count($joinMethodsArray),
                'methods' => array_column($joinMethodsArray, 'join_method'),
            ]);

            return response()->json([
                'success' => true,
                'join_methods' => $joinMethodsArray,
                'count' => count($joinMethodsArray),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch join methods', [
                'game_id' => $gameId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch join methods: ' . $e->getMessage(),
            ], 500);
        }
    }
}
