<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserStatus;
use App\Events\UserStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * UserStatusController handles API requests for user status management
 *
 * Phase 2: Member List Enhancement
 *
 * This controller provides endpoints for:
 * - Updating user status (online, idle, dnd, offline)
 * - Setting custom status text/emoji with optional expiry
 * - Clearing custom status
 * - Getting user's current status
 */
class UserStatusController extends Controller
{
    /**
     * Update user's status (online, idle, dnd, offline)
     *
     * POST /api/status
     * Body: { "status": "online" | "idle" | "dnd" | "offline" }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:online,idle,dnd,offline',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status value',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $status = $request->input('status');

            Log::info('User status update request', [
                'user_id' => $user->id,
                'new_status' => $status
            ]);

            // Update or create the user status
            $userStatus = UserStatus::setStatus($user->id, $status);

            // Broadcast the status update to relevant channels
            $this->broadcastStatusUpdate($user, $userStatus);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'status' => $userStatus->status,
                    'status_label' => $userStatus->getDisplayStatus(),
                    'status_color' => $userStatus->getStatusColor(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('User status update failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Set custom status text/emoji with optional expiry
     *
     * POST /api/status/custom
     * Body: {
     *     "text": "Playing with friends",
     *     "emoji": "game_emoji",
     *     "expires_in": 3600 // seconds, optional
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setCustomStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'text' => 'nullable|string|max:128',
                'emoji' => 'nullable|string|max:32',
                'expires_in' => 'nullable|integer|min:0|max:604800', // Max 7 days in seconds
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid custom status data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // At least text or emoji must be provided
            if (empty($request->input('text')) && empty($request->input('emoji'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either text or emoji must be provided'
                ], 422);
            }

            $user = auth()->user();
            $text = $request->input('text');
            $emoji = $request->input('emoji');
            $expiresIn = $request->input('expires_in');

            $expiresAt = null;
            if ($expiresIn && $expiresIn > 0) {
                $expiresAt = Carbon::now()->addSeconds($expiresIn);
            }

            Log::info('Custom status update request', [
                'user_id' => $user->id,
                'text' => $text,
                'emoji' => $emoji,
                'expires_at' => $expiresAt?->toIso8601String()
            ]);

            // Update or create the custom status
            $userStatus = UserStatus::setCustomStatus($user->id, $text, $emoji, $expiresAt);

            // Broadcast the status update
            $this->broadcastStatusUpdate($user, $userStatus);

            return response()->json([
                'success' => true,
                'message' => 'Custom status set successfully',
                'data' => [
                    'custom_text' => $userStatus->custom_text,
                    'custom_emoji' => $userStatus->custom_emoji,
                    'expires_at' => $userStatus->expires_at?->toIso8601String(),
                    'full_status' => $userStatus->getFullCustomStatus(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Custom status update failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set custom status'
            ], 500);
        }
    }

    /**
     * Clear custom status
     *
     * DELETE /api/status/custom
     *
     * @return JsonResponse
     */
    public function clearCustomStatus(): JsonResponse
    {
        try {
            $user = auth()->user();

            Log::info('Clear custom status request', [
                'user_id' => $user->id
            ]);

            $cleared = UserStatus::clearCustomStatus($user->id);

            if (!$cleared) {
                return response()->json([
                    'success' => false,
                    'message' => 'No custom status to clear'
                ], 404);
            }

            // Get updated status and broadcast
            $userStatus = UserStatus::getOrCreate($user->id);
            $this->broadcastStatusUpdate($user, $userStatus);

            return response()->json([
                'success' => true,
                'message' => 'Custom status cleared successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Clear custom status failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear custom status'
            ], 500);
        }
    }

    /**
     * Get user's current status
     *
     * GET /api/users/{user}/status
     *
     * @param User $user
     * @return JsonResponse
     */
    public function getStatus(User $user): JsonResponse
    {
        try {
            Log::debug('Get user status request', [
                'requesting_user_id' => auth()->id(),
                'target_user_id' => $user->id
            ]);

            $viewer = auth()->user();

            // Check privacy settings
            $canSeeStatus = $user->profile->shouldShowOnlineStatus($viewer);
            $canSeeActivity = $user->profile->shouldShowGamingActivity($viewer);

            // If status is hidden, return offline response
            if (!$canSeeStatus) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'offline',
                        'status_label' => 'Offline',
                        'status_color' => UserStatus::STATUS_COLORS['offline'],
                        'custom_text' => null,
                        'custom_emoji' => null,
                        'full_custom_status' => null,
                        'has_custom_status' => false,
                        'activity' => null,
                        'privacy_hidden' => true,
                    ]
                ], 200);
            }

            $userStatus = $user->userStatus;

            if (!$userStatus) {
                // Return default offline status
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'offline',
                        'status_label' => 'Offline',
                        'status_color' => UserStatus::STATUS_COLORS['offline'],
                        'custom_text' => null,
                        'custom_emoji' => null,
                        'full_custom_status' => null,
                        'has_custom_status' => false,
                        'activity' => $canSeeActivity ? $user->getDisplayActivity() : null,
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $userStatus->status,
                    'status_label' => $userStatus->getDisplayStatus(),
                    'status_color' => $userStatus->getStatusColor(),
                    'custom_text' => $canSeeActivity ? $userStatus->custom_text : null,
                    'custom_emoji' => $canSeeActivity ? $userStatus->custom_emoji : null,
                    'full_custom_status' => $canSeeActivity ? $userStatus->getFullCustomStatus() : null,
                    'has_custom_status' => $canSeeActivity ? $userStatus->hasCustomStatus() : false,
                    'expires_at' => $userStatus->expires_at?->toIso8601String(),
                    'activity' => $canSeeActivity ? $user->getDisplayActivity() : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get user status failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user status'
            ], 500);
        }
    }

    /**
     * Get multiple users' statuses in bulk
     *
     * POST /api/status/bulk
     * Body: { "user_ids": [1, 2, 3, ...] }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        try {
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
            $viewer = auth()->user();

            // Fetch all statuses and users with profiles in optimized queries
            $statuses = UserStatus::whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');

            $users = User::with('profile')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            // Build response with fallback for users without status records
            $result = [];
            foreach ($userIds as $userId) {
                $status = $statuses->get($userId);
                $user = $users->get($userId);

                // Check privacy settings
                $canSeeStatus = $user?->profile?->shouldShowOnlineStatus($viewer) ?? true;
                $canSeeActivity = $user?->profile?->shouldShowGamingActivity($viewer) ?? true;

                if (!$canSeeStatus) {
                    // Return hidden status
                    $result[$userId] = [
                        'status' => 'offline',
                        'status_color' => UserStatus::STATUS_COLORS['offline'],
                        'custom_text' => null,
                        'custom_emoji' => null,
                        'has_custom_status' => false,
                        'privacy_hidden' => true,
                    ];
                } else {
                    $result[$userId] = [
                        'status' => $status?->status ?? 'offline',
                        'status_color' => $status?->getStatusColor() ?? UserStatus::STATUS_COLORS['offline'],
                        'custom_text' => $canSeeActivity ? $status?->custom_text : null,
                        'custom_emoji' => $canSeeActivity ? $status?->custom_emoji : null,
                        'has_custom_status' => $canSeeActivity ? ($status?->hasCustomStatus() ?? false) : false,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Bulk status fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statuses'
            ], 500);
        }
    }

    /**
     * Broadcast status update to relevant channels
     *
     * @param User $user
     * @param UserStatus $userStatus
     * @return void
     */
    private function broadcastStatusUpdate(User $user, UserStatus $userStatus): void
    {
        try {
            // Broadcast to all servers the user is a member of
            $serverIds = $user->servers()->pluck('servers.id');

            foreach ($serverIds as $serverId) {
                broadcast(new UserStatusUpdated(
                    $user,
                    $userStatus,
                    $serverId
                ))->toOthers();
            }

            Log::debug('Status update broadcasted', [
                'user_id' => $user->id,
                'server_count' => $serverIds->count()
            ]);

        } catch (\Exception $e) {
            // Log but don't fail the request if broadcasting fails
            Log::warning('Failed to broadcast status update', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }
}
