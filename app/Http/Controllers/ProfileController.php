<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SteamApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
}