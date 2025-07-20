<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Friend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $friends = $user->friends()
            ->wherePivot('status', 'accepted')
            ->with('profile')
            ->get();

        $pendingRequests = $user->friendRequests()
            ->with('profile')
            ->get();

        $sentRequests = $user->friends()
            ->wherePivot('status', 'pending')
            ->with('profile')
            ->get();

        return view('friends.index', compact('friends', 'pendingRequests', 'sentRequests'));
    }

    public function search()
    {
        return view('friends.search');
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:20',
        ]);

        $query = $request->input('query');
        $currentUser = Auth::user();

        $users = User::where('id', '!=', $currentUser->id)
            ->where(function($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('display_name', 'like', "%{$query}%");
            })
            ->with('profile')
            ->limit(10)
            ->get();

        return view('friends.search', compact('users', 'query'));
    }

    public function sendRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $currentUser = Auth::user();
        $targetUser = User::find($request->user_id);

        if ($currentUser->id === $targetUser->id) {
            return back()->with('error', 'You cannot send a friend request to yourself.');
        }

        $existingFriendship = $currentUser->friends()
            ->wherePivot('friend_id', $targetUser->id)
            ->first();

        if ($existingFriendship) {
            return back()->with('error', 'Friend request already exists.');
        }

        $currentUser->friends()->attach($targetUser->id, ['status' => 'pending']);

        return back()->with('success', 'Friend request sent!');
    }

    public function acceptRequest($userId)
    {
        $currentUser = Auth::user();
        $sender = User::findOrFail($userId);

        $friendship = $sender->friends()
            ->wherePivot('friend_id', $currentUser->id)
            ->wherePivot('status', 'pending')
            ->first();

        if (!$friendship) {
            return back()->with('error', 'Friend request not found.');
        }

        // Update the existing request
        $sender->friends()->updateExistingPivot($currentUser->id, ['status' => 'accepted']);

        // Create the reverse friendship
        $currentUser->friends()->attach($sender->id, ['status' => 'accepted']);

        return back()->with('success', 'Friend request accepted!');
    }

    public function declineRequest($userId)
    {
        $currentUser = Auth::user();
        $sender = User::findOrFail($userId);

        $sender->friends()->detach($currentUser->id);

        return back()->with('success', 'Friend request declined.');
    }

    public function removeFriend($userId)
    {
        $currentUser = Auth::user();
        $friend = User::findOrFail($userId);

        $currentUser->friends()->detach($friend->id);
        $friend->friends()->detach($currentUser->id);

        return back()->with('success', 'Friend removed.');
    }
}