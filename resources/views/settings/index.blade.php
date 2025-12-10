@extends('layouts.app')

@section('title', 'Settings - Glyph')

@section('content')
<x-navbar />

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
                    <a href="#appearance" class="sidebar-link" onclick="showSettingsTab('appearance', this)">Appearance</a>
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
                            <p style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 16px;">Leave blank to keep current password</p>

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
                        <p style="color: var(--color-text-secondary); margin-bottom: 24px;">Edit your public profile information</p>
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div id="privacy" class="settings-content" style="display: none;">
                    <div class="card">
                        <h3 class="card-header">Privacy Settings</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 24px;">Control what information others can see on your profile</p>
                        <form method="POST" action="{{ route('settings.privacy') }}">
                            @csrf
                            @method('PUT')

                            {{-- Profile Visibility Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text-secondary);">Profile Visibility</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="profile_visible_to_friends_only" value="1"
                                            {{ ($user->profile->profile_visible_to_friends_only ?? false) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Profile Visible to Friends Only</div>
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Only friends can view your full profile. Others will see limited information.</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Status & Activity Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text-secondary);">Status & Activity</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_online_status" value="1"
                                            {{ ($user->profile->show_online_status ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Online Status</div>
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Let others see when you're online or offline</div>
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
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Display which game you're currently playing</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Steam Data Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text-secondary);">Steam Data</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_steam_data" value="1"
                                            {{ ($user->profile->show_steam_data ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Steam Data on Profile</div>
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Display your games, playtime, and achievements</div>
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
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Display your Steam friends list on your profile</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Community Section --}}
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text-secondary);">Community</h4>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                        <input type="checkbox" name="show_servers" value="1"
                                            {{ ($user->profile->show_servers ?? true) ? 'checked' : '' }}
                                            style="width: auto; margin-top: 4px;">
                                        <div>
                                            <div style="font-weight: 600;">Show Server Memberships</div>
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Display which servers you're a member of</div>
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
                                            <div style="font-size: 14px; color: var(--color-text-muted);">Only friends can see and join your game lobbies</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                        </form>
                    </div>
                </div>

                <!-- Appearance Settings -->
                <div id="appearance" class="settings-content" style="display: none;">
                    <div class="card">
                        <h3 class="card-header">Appearance Settings</h3>
                        <p style="color: var(--color-text-muted, #71717a); margin-bottom: 24px;">Customize the look and feel of Glyph</p>

                        <div style="margin-bottom: 32px;">
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text-secondary, #a1a1aa);">Theme</h4>
                            <p style="font-size: 14px; color: var(--color-text-muted, #71717a); margin-bottom: 20px;">Choose how Glyph looks to you. Select a theme that's easy on your eyes.</p>

                            <div class="theme-options" style="display: flex; gap: 24px; flex-wrap: wrap;">
                                <!-- Dark Theme Option -->
                                <label class="theme-option" style="cursor: pointer; position: relative;">
                                    <input type="radio" name="theme" value="dark"
                                           {{ ($user->profile->theme ?? 'dark') === 'dark' ? 'checked' : '' }}
                                           onchange="handleThemeChange(this.value)"
                                           style="position: absolute; opacity: 0; pointer-events: none;">
                                    <div class="theme-preview theme-preview-dark"
                                         style="width: 180px; height: 120px; border-radius: 12px; border: 3px solid transparent;
                                                background: #0e0e10; position: relative; overflow: hidden;
                                                transition: border-color 0.2s, transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                                        <!-- Mini preview of dark theme -->
                                        <div style="height: 24px; background: #18181b; border-bottom: 1px solid #3f3f46; display: flex; align-items: center; padding: 0 8px;">
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; margin-right: 4px;"></div>
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; margin-right: 4px;"></div>
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></div>
                                        </div>
                                        <div style="display: flex; height: 96px;">
                                            <div style="width: 45px; background: #1e1e22; border-right: 1px solid #27272a; padding: 8px 4px;">
                                                <div style="height: 6px; width: 100%; background: #3f3f46; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 80%; background: #667eea; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 100%; background: #3f3f46; border-radius: 3px;"></div>
                                            </div>
                                            <div style="flex: 1; padding: 8px;">
                                                <div style="height: 8px; width: 70%; background: #3f3f46; border-radius: 4px; margin-bottom: 8px;"></div>
                                                <div style="height: 6px; width: 90%; background: #27272a; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 60%; background: #27272a; border-radius: 3px; margin-bottom: 12px;"></div>
                                                <div style="height: 20px; width: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; margin-top: 12px;">
                                        <div style="font-weight: 600; font-size: 15px;">Dark</div>
                                        <div style="font-size: 12px; color: var(--color-text-muted, #71717a);">Easy on the eyes</div>
                                    </div>
                                </label>

                                <!-- Light Theme Option -->
                                <label class="theme-option" style="cursor: pointer; position: relative;">
                                    <input type="radio" name="theme" value="light"
                                           {{ ($user->profile->theme ?? 'dark') === 'light' ? 'checked' : '' }}
                                           onchange="handleThemeChange(this.value)"
                                           style="position: absolute; opacity: 0; pointer-events: none;">
                                    <div class="theme-preview theme-preview-light"
                                         style="width: 180px; height: 120px; border-radius: 12px; border: 3px solid transparent;
                                                background: #f5f5f5; position: relative; overflow: hidden;
                                                transition: border-color 0.2s, transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                        <!-- Mini preview of light theme -->
                                        <div style="height: 24px; background: #ffffff; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; padding: 0 8px;">
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; margin-right: 4px;"></div>
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; margin-right: 4px;"></div>
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></div>
                                        </div>
                                        <div style="display: flex; height: 96px;">
                                            <div style="width: 45px; background: #f0f0f0; border-right: 1px solid #e0e0e0; padding: 8px 4px;">
                                                <div style="height: 6px; width: 100%; background: #d4d4d4; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 80%; background: #667eea; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 100%; background: #d4d4d4; border-radius: 3px;"></div>
                                            </div>
                                            <div style="flex: 1; padding: 8px; background: #ffffff;">
                                                <div style="height: 8px; width: 70%; background: #e0e0e0; border-radius: 4px; margin-bottom: 8px;"></div>
                                                <div style="height: 6px; width: 90%; background: #eeeeee; border-radius: 3px; margin-bottom: 6px;"></div>
                                                <div style="height: 6px; width: 60%; background: #eeeeee; border-radius: 3px; margin-bottom: 12px;"></div>
                                                <div style="height: 20px; width: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; margin-top: 12px;">
                                        <div style="font-weight: 600; font-size: 15px;">Light</div>
                                        <div style="font-size: 12px; color: var(--color-text-muted, #71717a);">Clean and bright</div>
                                    </div>
                                </label>
                            </div>
                        </div>
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

// Theme handling
function handleThemeChange(theme) {
    // Update visual selection
    updateThemePreviewBorders(theme);

    // Apply theme instantly using ThemeSwitcher if available
    if (window.ThemeSwitcher) {
        window.ThemeSwitcher.setTheme(theme);
    } else {
        // Fallback: Apply theme directly
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('glyph-theme', theme);

        // Save to server
        saveThemeToServer(theme);
    }
}

function updateThemePreviewBorders(selectedTheme) {
    // Remove border from all previews
    document.querySelectorAll('.theme-preview').forEach(preview => {
        preview.style.borderColor = 'transparent';
        preview.style.transform = 'scale(1)';
    });

    // Add border to selected preview
    const selectedInput = document.querySelector(`input[name="theme"][value="${selectedTheme}"]`);
    if (selectedInput) {
        const preview = selectedInput.closest('.theme-option').querySelector('.theme-preview');
        if (preview) {
            preview.style.borderColor = '#667eea';
            preview.style.transform = 'scale(1.02)';
        }
    }
}

function saveThemeToServer(theme) {
    fetch('{{ route("settings.appearance") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ theme: theme })
    }).catch(error => {
        console.error('Error saving theme:', error);
    });
}

// Initialize theme preview borders on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedTheme = document.querySelector('input[name="theme"]:checked');
    if (checkedTheme) {
        updateThemePreviewBorders(checkedTheme.value);
    }
});
</script>
@endsection