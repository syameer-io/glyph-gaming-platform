<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load('profile');
        return view('settings.index', compact('user'));
    }

    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->password = Hash::make($request->new_password);
        }

        $user->email = $request->email;
        $user->save();

        return back()->with('success', 'Account settings updated successfully!');
    }

    public function updatePrivacy(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'show_steam_data' => 'boolean',
            'show_online_status' => 'boolean',
            'show_gaming_activity' => 'boolean',
            'show_steam_friends' => 'boolean',
            'show_servers' => 'boolean',
            'show_lobbies_to_friends_only' => 'boolean',
            'profile_visible_to_friends_only' => 'boolean',
        ]);

        // Persist privacy settings to the database
        $user->profile->update([
            'show_steam_data' => $request->boolean('show_steam_data'),
            'show_online_status' => $request->boolean('show_online_status'),
            'show_gaming_activity' => $request->boolean('show_gaming_activity'),
            'show_steam_friends' => $request->boolean('show_steam_friends'),
            'show_servers' => $request->boolean('show_servers'),
            'show_lobbies_to_friends_only' => $request->boolean('show_lobbies_to_friends_only'),
            'profile_visible_to_friends_only' => $request->boolean('profile_visible_to_friends_only'),
        ]);

        return back()->with('success', 'Privacy settings updated successfully!');
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:dark,light',
        ]);

        $user = Auth::user();
        $user->profile->update([
            'theme' => $request->theme,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'theme' => $request->theme,
            ]);
        }

        return back()->with('success', 'Appearance settings updated successfully!');
    }
}