@extends('layouts.app')

@section('title', 'Settings - Glyph')

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container">
        <h1 style="margin-bottom: 32px;">Settings</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-2" style="grid-template-columns: 250px 1fr;">
            <div class="sidebar">
                <div class="sidebar-nav">
                    <a href="#account" class="sidebar-link active" onclick="showSettingsTab('account', this)">Account</a>
                    <a href="#profile" class="sidebar-link" onclick="showSettingsTab('profile', this)">Profile</a>
                    <a href="#privacy" class="sidebar-link" onclick="showSettingsTab('privacy', this)">Privacy</a>
                </div>
            </div>

            <div>
                <!-- Account Settings -->
                <div id="account" class="settings-content">
                    <div class="card">
                        <h3 class="card-header">Account Settings</h3>
                        <form method="POST" action="{{ route('settings.account') }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ $user->email }}" required>
                            </div>

                            <h4 style="margin-top: 32px; margin-bottom: 16px;">Change Password</h4>
                            <p style="color: #71717a; font-size: 14px; margin-bottom: 16px;">Leave blank to keep current password</p>

                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>

                            <div class="form-group">
                                <label for="new_password_confirmation">Confirm New Password</label>
                                <input type="password" id="new_password_confirmation" name="new_password_confirmation">
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Profile Settings -->
                <div id="profile" class="settings-content" style="display: none;">
                    <div class="card">
                        <h3 class="card-header">Profile Settings</h3>
                        <p style="color: #b3b3b5; margin-bottom: 24px;">Edit your public profile information</p>
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div id="privacy" class="settings-content" style="display: none;">
                    <div class="card">
                        <h3 class="card-header">Privacy Settings</h3>
                        <p style="color: #71717a; margin-bottom: 24px;">Control what information others can see on your profile</p>
                        <form method="POST" action="{{ route('settings.privacy') }}">
                            @csrf
                            @method('PUT')

                            {{-- Profile Visibility Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #a1a1aa;">Profile Visibility</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="profile_visible_to_friends_only" value="1"
                                            {{ ($user->profile->profile_visible_to_friends_only ?? false) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Profile Visible to Friends Only</div>
                                            <div style="font-size: 14px; color: #71717a;">Only friends can view your full profile. Others will see limited information.</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Status & Activity Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #a1a1aa;">Status & Activity</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_online_status" value="1"
                                            {{ ($user->profile->show_online_status ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Online Status</div>
                                            <div style="font-size: 14px; color: #71717a;">Let others see when you're online or offline</div>
                                        </div>
                                    </label>
                                </div>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_gaming_activity" value="1"
                                            {{ ($user->profile->show_gaming_activity ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Gaming Activity</div>
                                            <div style="font-size: 14px; color: #71717a;">Display which game you're currently playing</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Steam Data Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #a1a1aa;">Steam Data</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_steam_data" value="1"
                                            {{ ($user->profile->show_steam_data ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Steam Data on Profile</div>
                                            <div style="font-size: 14px; color: #71717a;">Display your games, playtime, and achievements</div>
                                        </div>
                                    </label>
                                </div>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_steam_friends" value="1"
                                            {{ ($user->profile->show_steam_friends ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Steam Friends</div>
                                            <div style="font-size: 14px; color: #71717a;">Display your Steam friends list on your profile</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Community Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #a1a1aa;">Community</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_servers" value="1"
                                            {{ ($user->profile->show_servers ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Server Memberships</div>
                                            <div style="font-size: 14px; color: #71717a;">Display which servers you're a member of</div>
                                        </div>
                                    </label>
                                </div>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_lobbies_to_friends_only" value="1"
                                            {{ ($user->profile->show_lobbies_to_friends_only ?? false) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Lobbies Visible to Friends Only</div>
                                            <div style="font-size: 14px; color: #71717a;">Only friends can see and join your game lobbies</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function showSettingsTab(tabName, element) {
    // Hide all content
    document.querySelectorAll('.settings-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Remove active class from all links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected content
    document.getElementById(tabName).style.display = 'block';
    
    // Add active class to clicked link
    element.classList.add('active');
}
</script>
@endsection