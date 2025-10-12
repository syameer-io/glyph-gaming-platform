<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Services\SteamApiService;
use App\Events\UserLobbyUpdated;
use App\Events\UserLobbyCleared;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $user->load(['profile', 'servers']);

        $currentUser = Auth::user();
        $isFriend = false;
        $friendRequestPending = false;
        $friendRequestReceived = false;

        if ($currentUser && $currentUser->id !== $user->id) {
            $friendship = $currentUser->friends()
                ->wherePivot('friend_id', $user->id)
                ->first();

            if ($friendship) {
                if ($friendship->pivot->status === 'accepted') {
                    $isFriend = true;
                } elseif ($friendship->pivot->status === 'pending') {
                    $friendRequestPending = true;
                }
            }

            $receivedRequest = $user->friends()
                ->wherePivot('friend_id', $currentUser->id)
                ->wherePivot('status', 'pending')
                ->exists();

            if ($receivedRequest) {
                $friendRequestReceived = true;
            }
        }

        return view('profile.show', compact('user', 'isFriend', 'friendRequestPending', 'friendRequestReceived'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'display_name' => 'required|string|min:3|max:30',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user->update([
            'display_name' => $request->display_name,
        ]);

        $profileData = [
            'bio' => $request->bio,
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $profileData['avatar_url'] = Storage::url($path);
        }

        $user->profile->update($profileData);

        return redirect()->route('profile.show', $user->username)
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Force refresh user's Steam data (Phase 2)
     */
    public function refreshSteamData(Request $request, SteamApiService $steamApiService)
    {
        $user = Auth::user();

        if (!$user->steam_id) {
            return response()->json(['error' => 'No Steam account linked'], 400);
        }

        $success = $steamApiService->forceRefreshUserData($user);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Steam data refreshed successfully',
                'steam_data' => $user->fresh()->profile->steam_data
            ]);
        } else {
            return response()->json(['error' => 'Failed to refresh Steam data'], 500);
        }
    }

    /**
     * Update user's Steam CS2 lobby link (Phase 4)
     *
     * Validates and stores a Steam CS2 lobby link for the authenticated user.
     * The lobby link allows friends and team members to join their CS2 game.
     * Lobby links expire after 30 minutes (handled by Profile model).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateLobbyLink(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;

            // Validate lobby link format using Laravel validation
            $validated = $request->validate([
                'lobby_link' => [
                    'required',
                    'string',
                    'max:512',
                    'regex:/^steam:\/\/joinlobby\/730\/\d+(\/\d+)?$/', // CS2 lobby format
                ],
            ], [
                'lobby_link.required' => 'Please provide a lobby link.',
                'lobby_link.regex' => 'Invalid Steam lobby link format. Link must be from CS2.',
            ]);

            $lobbyLink = trim($validated['lobby_link']);

            // Additional security validation to prevent injection attacks
            if (!$this->validateSteamUrl($lobbyLink)) {
                Log::warning('Potentially malicious lobby link blocked', [
                    'user_id' => $user->id,
                    'lobby_link' => $lobbyLink,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid lobby link format. Please check and try again.',
                ], 422);
            }

            // Use Profile model's built-in validation and setter
            // This provides double validation for security
            if (!Profile::isValidSteamLobbyLink($lobbyLink)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Steam CS2 lobby link format.',
                ], 422);
            }

            // Set lobby link with automatic timestamp
            $profile->setLobbyLink($lobbyLink);

            // Get user's current team for notification context
            $team = $user->teams()->whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'active');
            })->first();

            // Broadcast real-time notification to user's friends and team members
            broadcast(new UserLobbyUpdated(
                userId: $user->id,
                displayName: $user->display_name,
                lobbyLink: $lobbyLink,
                team: $team ? [
                    'id' => $team->id,
                    'name' => $team->name,
                ] : null
            ));

            Log::info('User created CS2 lobby', [
                'user_id' => $user->id,
                'display_name' => $user->display_name,
                'has_team' => !is_null($team),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lobby link updated successfully!',
                'data' => [
                    'lobby_link' => $lobbyLink,
                    'created_at' => $profile->steam_lobby_link_updated_at->toISOString(),
                    'expires_at' => $profile->steam_lobby_link_updated_at
                        ->addMinutes(Profile::LOBBY_EXPIRATION_MINUTES)
                        ->toISOString(),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Laravel validation errors - return as-is
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            // Profile model validation error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update lobby link', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lobby link. Please try again.',
            ], 500);
        }
    }

    /**
     * Clear user's Steam CS2 lobby link (Phase 4)
     *
     * Removes the lobby link when user closes their lobby or wants to stop sharing.
     * Broadcasts notification to friends and team members that lobby is no longer available.
     *
     * @return JsonResponse
     */
    public function clearLobbyLink(): JsonResponse
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;

            // Check if there's an active lobby to clear
            if (!$profile->hasActiveLobby()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active lobby to clear.',
                ], 400);
            }

            // Clear the lobby link and timestamp
            $profile->clearLobby();

            // Broadcast real-time notification
            broadcast(new UserLobbyCleared(
                userId: $user->id,
                displayName: $user->display_name
            ));

            Log::info('User cleared CS2 lobby', [
                'user_id' => $user->id,
                'display_name' => $user->display_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lobby link cleared successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to clear lobby link', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear lobby link. Please try again.',
            ], 500);
        }
    }

    /**
     * Validate Steam URL for security threats (Phase 4)
     *
     * Performs strict validation to prevent injection attacks through Steam URLs.
     * Checks for dangerous characters and ensures the URL matches expected protocols.
     *
     * This is a defense-in-depth measure in addition to regex validation.
     *
     * @param string $url The URL to validate
     * @return bool True if URL is safe, false if potentially malicious
     */
    private function validateSteamUrl(string $url): bool
    {
        // Check for dangerous characters that could indicate injection attacks
        $dangerousChars = [';', '&&', '||', '`', '$', '(', ')', '<', '>', '|'];

        foreach ($dangerousChars as $char) {
            if (str_contains($url, $char)) {
                return false;
            }
        }

        // Ensure URL starts with expected Steam protocols only
        $allowedProtocols = ['steam://connect', 'steam://joinlobby'];
        $hasValidProtocol = false;

        foreach ($allowedProtocols as $protocol) {
            if (str_starts_with($url, $protocol)) {
                $hasValidProtocol = true;
                break;
            }
        }

        if (!$hasValidProtocol) {
            return false;
        }

        return true;
    }
}