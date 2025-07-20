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
        ]);

        // In a real application, you'd store these in a separate settings table
        // For now, we'll store them in the session
        session([
            'privacy_settings' => [
                'show_steam_data' => $request->boolean('show_steam_data'),
                'show_online_status' => $request->boolean('show_online_status'),
            ]
        ]);

        return back()->with('success', 'Privacy settings updated successfully!');
    }
}