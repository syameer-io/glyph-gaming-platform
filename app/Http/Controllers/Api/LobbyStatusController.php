<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LobbyStatusService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * LobbyStatusController handles API requests for lobby status information
 *
 * This controller provides endpoints for:
 * - Bulk lobby status fetching (for member lists, friend lists, team lists)
 * - Single user lobby status
 */
class LobbyStatusController extends Controller
{
    protected LobbyStatusService $lobbyStatusService;

    /**
     * Inject the LobbyStatusService dependency
     */
    public function __construct(LobbyStatusService $lobbyStatusService)
    {
        $this->lobbyStatusService = $lobbyStatusService;
    }

    /**
     * Get active lobbies for multiple users in bulk
     *
     * POST /api/lobbies/bulk-status
     * Body: { "user_ids": [1, 2, 3, ...] }
     *
     * Rate limit: 60 requests per minute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1|max:100',
                'user_ids.*' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userIds = $request->input('user_ids');

            Log::debug('Bulk lobby status request', [
                'requesting_user_id' => auth()->id(),
                'user_count' => count($userIds)
            ]);

            // Fetch active lobbies for all users in one query
            $lobbies = $this->lobbyStatusService->getActiveLobbiesForUsers($userIds);

            return response()->json([
                'success' => true,
                'data' => $lobbies,
                'count' => count($lobbies)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Bulk lobby status request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lobby statuses'
            ], 500);
        }
    }

    /**
     * Get active lobby for a single user
     *
     * GET /api/users/{user}/lobbies
     *
     * Rate limit: 120 requests per minute
     *
     * @param User $user
     * @return JsonResponse
     */
    public function userLobbies(User $user): JsonResponse
    {
        try {
            Log::debug('User lobby status request', [
                'requesting_user_id' => auth()->id(),
                'target_user_id' => $user->id
            ]);

            // Get active lobby for the user (with caching)
            $lobby = $this->lobbyStatusService->getActiveLobbyForUser($user->id);

            // Always return array format for JavaScript compatibility
            if ($lobby) {
                return response()->json([
                    'success' => true,
                    'data' => [$lobby], // Wrap in array for consistent format
                    'has_active_lobby' => true
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [], // Empty array, not null
                    'has_active_lobby' => false
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('User lobby status request failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lobby status'
            ], 500);
        }
    }

    /**
     * Check if user has an active lobby
     *
     * GET /api/users/{user}/has-lobby
     *
     * Lightweight endpoint for quick checks
     *
     * @param User $user
     * @return JsonResponse
     */
    public function hasLobby(User $user): JsonResponse
    {
        try {
            $hasLobby = $this->lobbyStatusService->hasActiveLobby($user->id);

            return response()->json([
                'success' => true,
                'has_active_lobby' => $hasLobby
            ], 200);

        } catch (\Exception $e) {
            Log::error('Has lobby check failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check lobby status'
            ], 500);
        }
    }
}
