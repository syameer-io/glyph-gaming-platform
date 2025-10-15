@extends('layouts.app')

@section('title', $user->display_name . ' - Profile')

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

<div class="profile-header">
    <div class="container">
        <div class="profile-info">
            <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="profile-avatar">
            <div class="profile-details">
                <h1>{{ $user->display_name }}</h1>
                <p>{{ '@' . $user->username }}</p>
                <div style="margin-top: 16px;">
                    <span class="status-indicator {{ $user->profile->status === 'online' ? 'status-online' : 'status-offline' }}"></span>
                    <span style="color: white;">{{ ucfirst($user->profile->status) }}</span>
                    @if($user->profile->current_game)
                        <span style="margin-left: 16px; color: #10b981;" data-gaming-status>
                            Playing {{ $user->profile->current_game['name'] }}
                            @if(isset($user->profile->current_game['server_name']))
                                <br><span style="font-size: 12px; color: #71717a;">{{ $user->profile->current_game['server_name'] }}</span>
                            @endif
                            @if(isset($user->profile->current_game['map']))
                                <span style="font-size: 12px; color: #71717a;"> - {{ $user->profile->current_game['map'] }}</span>
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div style="margin-left: auto;">
                @if(auth()->check() && auth()->id() !== $user->id)
                    @if($isFriend)
                        <form method="POST" action="{{ route('friends.remove', $user->id) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Remove Friend</button>
                        </form>
                    @elseif($friendRequestPending)
                        <button class="btn btn-secondary" disabled>Request Pending</button>
                    @elseif($friendRequestReceived)
                        <form method="POST" action="{{ route('friends.accept', $user->id) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Accept Request</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('friends.request') }}" style="display: inline;">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary">Add Friend</button>
                        </form>
                    @endif
                @elseif(auth()->check() && auth()->id() === $user->id)
                    <div style="display: flex; gap: 12px;">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                        @if($user->steam_id)
                            <button onclick="refreshSteamData()" class="btn btn-secondary" id="refresh-steam-btn">
                                <span id="refresh-text">Refresh Steam Data</span>
                                <span id="refresh-loading" style="display: none;">Refreshing...</span>
                            </button>
                            <div style="font-size: 12px; color: #71717a; margin-top: 8px; max-width: 300px;">
                                Note: Game status updates may take 1-5 minutes after launching. Check Steam privacy settings if games don't appear.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<main>
    <div class="container">
        <div class="grid grid-cols-2" style="grid-template-columns: 2fr 1fr;">
            <div>
                <div class="card" style="margin-bottom: 24px;">
                    <h3 class="card-header">About</h3>
                    <p style="color: #b3b3b5;">{{ $user->profile->bio ?: 'This user hasn\'t written a bio yet.' }}</p>
                </div>

                @if($user->steam_id && $user->profile->steam_data)
                    <div class="card" style="margin-bottom: 24px;">
                        <h3 class="card-header">🎮 Gaming Preferences</h3>
                        <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Based on Steam activity and playtime data</p>
                        
                        @if($user->gamingPreferences && $user->gamingPreferences->count() > 0)
                            <div style="display: grid; gap: 16px;">
                                @foreach($user->gamingPreferences->sortByDesc('playtime_forever')->take(6) as $preference)
                                    <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; border-left: 4px solid #667eea;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0; color: #efeff1; font-weight: 600; font-size: 16px;">{{ $preference->game_name }}</h4>
                                                <div style="display: flex; gap: 16px; margin-top: 4px; font-size: 14px; color: #b3b3b5;">
                                                    <span>{{ round($preference->playtime_forever / 60, 1) }} hours total</span>
                                                    @if($preference->playtime_2weeks > 0)
                                                        <span style="color: #10b981;">{{ round($preference->playtime_2weeks / 60, 1) }} hours recent</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                @if($preference->skill_level)
                                                    <div style="background-color: #3f3f46; color: #efeff1; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                                                        {{ ucfirst($preference->skill_level) }}
                                                    </div>
                                                @endif
                                                <div style="font-size: 12px; color: #71717a;">
                                                    {{ ucfirst($preference->preference_level) }} priority
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Gaming activity bar -->
                                        <div style="margin-top: 12px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                <span style="font-size: 12px; color: #b3b3b5;">Gaming Activity</span>
                                                <span style="font-size: 12px; color: #71717a;">
                                                    @if($preference->last_played)
                                                        Last played {{ \Carbon\Carbon::parse($preference->last_played)->diffForHumans() }}
                                                    @else
                                                        Recently active
                                                    @endif
                                                </span>
                                            </div>
                                            <div style="width: 100%; height: 6px; background-color: #3f3f46; border-radius: 3px; overflow: hidden;">
                                                @php
                                                    $maxPlaytime = $user->gamingPreferences->max('playtime_forever');
                                                    $activityPercentage = $maxPlaytime > 0 ? ($preference->playtime_forever / $maxPlaytime) * 100 : 0;
                                                @endphp
                                                <div style="height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: {{ $activityPercentage }}%; transition: width 0.3s ease;"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                @if($user->gamingPreferences->count() > 6)
                                    <div style="text-align: center; margin-top: 8px;">
                                        <span style="color: #71717a; font-size: 14px;">And {{ $user->gamingPreferences->count() - 6 }} more games</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div style="text-align: center; padding: 40px; background-color: #0e0e10; border-radius: 8px; border: 2px dashed #3f3f46;">
                                <p style="color: #71717a; margin-bottom: 8px;">No gaming preferences found</p>
                                <p style="color: #b3b3b5; font-size: 14px;">Gaming preferences are automatically generated from Steam activity</p>
                            </div>
                        @endif
                    </div>

                    <div class="card">
                        <h3 class="card-header">Steam Games</h3>
                        @forelse($user->profile->steam_games as $game)
                            <div class="game-card">
                                <div class="game-icon">
                                    @if(isset($game['img_icon_url']))
                                        <img src="https://media.steampowered.com/steamcommunity/public/images/apps/{{ $game['appid'] }}/{{ $game['img_icon_url'] }}.jpg" alt="{{ $game['name'] }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                    @else
                                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="game-info">
                                    <div class="game-name">{{ $game['name'] }}</div>
                                    <div class="game-playtime">{{ round($game['playtime_forever'] / 60, 1) }} hours played</div>
                                    @if(isset($user->profile->steam_data['achievements'][$game['appid']]))
                                        @php
                                            $achievements = $user->profile->steam_data['achievements'][$game['appid']];
                                        @endphp
                                        <div style="margin-top: 8px;">
                                            <div style="font-size: 12px; color: #b3b3b5; margin-bottom: 4px;">
                                                Achievements: {{ $achievements['unlocked'] }}/{{ $achievements['total'] }} ({{ $achievements['percentage'] }}%)
                                            </div>
                                            <div class="achievement-progress">
                                                <div class="achievement-progress-bar" style="width: {{ $achievements['percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p style="color: #71717a;">No games to display</p>
                        @endforelse
                    </div>
                @endif
            </div>

            <div>
                {{-- CS2 Lobby Management (Phase 4) --}}
                @if(auth()->check())
                    @if(auth()->id() === $user->id)
                        {{-- Own profile: Lobby management --}}
                        <div class="card" style="margin-bottom: 24px;">
                            <h3 class="card-header">🎮 CS2 Lobby</h3>

                            <div id="lobby-management">
                                @if($user->profile && $user->profile->hasActiveLobby())
                                    {{-- Active lobby display --}}
                                    <div id="active-lobby-display" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                            <div style="width: 8px; height: 8px; background: #fff; border-radius: 50%; animation: pulse 2s infinite;"></div>
                                            <span style="color: white; font-weight: 600;">Lobby Active</span>
                                        </div>
                                        <div style="font-size: 12px; color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                                            Expires in <span id="lobby-timer">{{ $user->profile->lobbyTimeRemaining() }}</span> minutes
                                        </div>
                                        <button onclick="clearLobby()" class="btn btn-danger btn-sm" style="width: 100%;">
                                            Clear Lobby
                                        </button>
                                    </div>
                                @else
                                    {{-- Lobby link input form --}}
                                    <div id="lobby-input-form">
                                        <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 12px;">
                                            Share your CS2 lobby link so friends can join your game.
                                        </p>

                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <input
                                                type="text"
                                                id="lobby-link-input"
                                                placeholder="steam://joinlobby/730/..."
                                                style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                                            >
                                        </div>

                                        <button onclick="saveLobbyLink()" class="btn btn-primary btn-sm" style="width: 100%; margin-bottom: 8px;">
                                            Save Lobby Link
                                        </button>

                                        {{-- How to get lobby link instructions --}}
                                        <details style="margin-top: 12px;">
                                            <summary style="color: #667eea; cursor: pointer; font-size: 13px; user-select: none;">
                                                How to get your lobby link?
                                            </summary>
                                            <div style="margin-top: 8px; padding: 12px; background-color: #0e0e10; border-radius: 6px; font-size: 12px; color: #b3b3b5; line-height: 1.6;">
                                                <ol style="margin: 0; padding-left: 20px;">
                                                    <li>Open CS2 and create a lobby</li>
                                                    <li>Press <code style="background: #3f3f46; padding: 2px 6px; border-radius: 3px;">Shift+Tab</code> for Steam Overlay</li>
                                                    <li>Click "View Players" → "Friends" → Right-click your name</li>
                                                    <li>Select "Copy Lobby Link" or "Invite to Lobby"</li>
                                                    <li>Paste the link here (starts with steam://joinlobby/730/)</li>
                                                </ol>
                                            </div>
                                        </details>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif($user->profile && $user->profile->hasActiveLobby())
                        {{-- Other user's profile: Join lobby button --}}
                        <div class="card" style="margin-bottom: 24px;">
                            <h3 class="card-header">🎮 CS2 Lobby</h3>
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
                                    <span style="color: white; font-weight: 600;">Lobby Available</span>
                                </div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                                    {{ $user->display_name }} has an active CS2 lobby
                                </div>
                                <a href="{{ $user->profile->steam_lobby_link }}" class="btn btn-success" style="width: 100%; text-align: center; text-decoration: none;">
                                    Join {{ $user->display_name }}'s Lobby
                                </a>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="card" style="margin-bottom: 24px;">
                    <h3 class="card-header">Stats</h3>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <div style="font-size: 14px; color: #71717a;">Member Since</div>
                            <div style="font-size: 18px; font-weight: 600;">{{ $user->created_at->format('F Y') }}</div>
                        </div>
                        @if($user->steam_id)
                            <div>
                                <div style="font-size: 14px; color: #71717a;">Total Playtime</div>
                                <div style="font-size: 18px; font-weight: 600;">{{ round($user->profile->total_playtime / 60, 1) }} hours</div>
                            </div>
                        @endif
                        <div>
                            <div style="font-size: 14px; color: #71717a;">Servers</div>
                            <div style="font-size: 18px; font-weight: 600;">{{ $user->servers->count() }}</div>
                        </div>
                    </div>
                </div>

                @if($user->steam_id && isset($user->profile->steam_data['friends']) && $user->profile->steam_data['friends']['count'] > 0)
                    <div class="card" style="margin-bottom: 24px;">
                        <h3 class="card-header">🎮 Steam Friends ({{ $user->profile->steam_data['friends']['count'] }})</h3>
                        @foreach(array_slice($user->profile->steam_data['friends']['friends'] ?? [], 0, 5) as $friend)
                            <div style="display: flex; align-items: center; padding: 8px; background-color: #0e0e10; border-radius: 6px; margin-bottom: 8px;">
                                <img src="{{ $friend['avatar'] ?? '' }}" alt="{{ $friend['personaname'] ?? 'Friend' }}" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 12px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 14px;">{{ $friend['personaname'] ?? 'Unknown' }}</div>
                                    <div style="font-size: 12px; color: #71717a;">
                                        @if(isset($friend['gameextrainfo']))
                                            Playing {{ $friend['gameextrainfo'] }}
                                        @else
                                            {{ ucfirst($friend['personastate_text'] ?? 'offline') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if(count($user->profile->steam_data['friends']['friends'] ?? []) > 5)
                            <div style="text-align: center; color: #71717a; font-size: 12px; margin-top: 8px;">
                                And {{ count($user->profile->steam_data['friends']['friends']) - 5 }} more friends
                            </div>
                        @endif
                    </div>
                @endif

                <div class="card">
                    <h3 class="card-header">Servers</h3>
                    @forelse($user->servers->take(5) as $server)
                        <div style="padding: 12px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 8px;">
                            <div style="font-weight: 600;">{{ $server->name }}</div>
                            <div style="font-size: 12px; color: #71717a;">{{ $server->members->count() }} members</div>
                        </div>
                    @empty
                        <p style="color: #71717a;">Not in any servers yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</main>

<script>
async function refreshSteamData() {
    const btn = document.getElementById('refresh-steam-btn');
    const refreshText = document.getElementById('refresh-text');
    const refreshLoading = document.getElementById('refresh-loading');
    
    btn.disabled = true;
    refreshText.style.display = 'none';
    refreshLoading.style.display = 'inline';
    
    try {
        const response = await fetch('{{ route("profile.steam.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 16px; border-radius: 8px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            successDiv.textContent = 'Steam data refreshed successfully!';
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.remove();
                location.reload(); // Reload to show updated data
            }, 2000);
        } else {
            throw new Error(data.error || 'Failed to refresh data');
        }
    } catch (error) {
        console.error('Error refreshing Steam data:', error);
        
        // Show error message
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #dc2626; color: white; padding: 12px 16px; border-radius: 8px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
        errorDiv.textContent = 'Failed to refresh Steam data. Please try again.';
        document.body.appendChild(errorDiv);
        
        setTimeout(() => errorDiv.remove(), 3000);
    } finally {
        btn.disabled = false;
        refreshText.style.display = 'inline';
        refreshLoading.style.display = 'none';
    }
}

// Add real-time gaming status updates (Phase 2)
if (window.Echo) {
    // Listen for gaming status changes for this user
    window.Echo.private('user.{{ $user->id }}')
        .listen('UserStartedPlaying', (e) => {
            updateGamingStatus(e.game_name, e.rich_presence);
        })
        .listen('UserStoppedPlaying', (e) => {
            updateGamingStatus(null, null);
        })
        .listen('UserChangedGame', (e) => {
            updateGamingStatus(e.game_name, e.rich_presence);
        })
        .listen('UserGameStatusChanged', (e) => {
            updateGamingStatus(e.game_name, e.rich_presence);
        });
}

function updateGamingStatus(gameName, richPresence) {
    const statusElement = document.querySelector('[data-gaming-status]');
    if (!statusElement) return;

    if (gameName) {
        let statusText = `Playing ${gameName}`;
        if (richPresence && richPresence.server_name) {
            statusText += `<br><span style="font-size: 12px; color: #71717a;">${richPresence.server_name}</span>`;
        }
        if (richPresence && richPresence.map) {
            statusText += `<span style="font-size: 12px; color: #71717a;"> - ${richPresence.map}</span>`;
        }
        statusElement.innerHTML = statusText;
        statusElement.style.display = 'inline';
    } else {
        statusElement.style.display = 'none';
    }
}

// CS2 Lobby Management Functions (Phase 4)
@if(auth()->check() && auth()->id() === $user->id)
function saveLobbyLink() {
    const input = document.getElementById('lobby-link-input');
    const lobbyLink = input.value.trim();

    // Validate lobby link format
    if (!lobbyLink) {
        showToast('Please enter a lobby link', 'error');
        return;
    }

    if (!lobbyLink.startsWith('steam://joinlobby/730/')) {
        showToast('Invalid CS2 lobby link format. Must start with steam://joinlobby/730/', 'error');
        return;
    }

    // Disable button and show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    fetch('{{ route("profile.lobby-link.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ lobby_link: lobbyLink })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lobby link saved successfully!', 'success');

            // Update UI to show active lobby
            const lobbyManagement = document.getElementById('lobby-management');
            lobbyManagement.innerHTML = `
                <div id="active-lobby-display" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <div style="width: 8px; height: 8px; background: #fff; border-radius: 50%; animation: pulse 2s infinite;"></div>
                        <span style="color: white; font-weight: 600;">Lobby Active</span>
                    </div>
                    <div style="font-size: 12px; color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                        Expires in <span id="lobby-timer">30</span> minutes
                    </div>
                    <button onclick="clearLobby()" class="btn btn-danger btn-sm" style="width: 100%;">
                        Clear Lobby
                    </button>
                </div>
            `;

            // Start countdown timer
            startLobbyTimer(30);
        } else {
            showToast(data.error || 'Failed to save lobby link', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving lobby link:', error);
        showToast('Failed to save lobby link. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

function clearLobby() {
    if (!confirm('Clear your active lobby link?')) {
        return;
    }

    fetch('{{ route("profile.lobby-link.clear") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Lobby link cleared', 'success');

            // Update UI to show input form
            const lobbyManagement = document.getElementById('lobby-management');
            lobbyManagement.innerHTML = `
                <div id="lobby-input-form">
                    <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 12px;">
                        Share your CS2 lobby link so friends can join your game.
                    </p>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <input
                            type="text"
                            id="lobby-link-input"
                            placeholder="steam://joinlobby/730/..."
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                    </div>

                    <button onclick="saveLobbyLink()" class="btn btn-primary btn-sm" style="width: 100%; margin-bottom: 8px;">
                        Save Lobby Link
                    </button>

                    <details style="margin-top: 12px;">
                        <summary style="color: #667eea; cursor: pointer; font-size: 13px; user-select: none;">
                            How to get your lobby link?
                        </summary>
                        <div style="margin-top: 8px; padding: 12px; background-color: #0e0e10; border-radius: 6px; font-size: 12px; color: #b3b3b5; line-height: 1.6;">
                            <ol style="margin: 0; padding-left: 20px;">
                                <li>Open CS2 and create a lobby</li>
                                <li>Press <code style="background: #3f3f46; padding: 2px 6px; border-radius: 3px;">Shift+Tab</code> for Steam Overlay</li>
                                <li>Click "View Players" → "Friends" → Right-click your name</li>
                                <li>Select "Copy Lobby Link" or "Invite to Lobby"</li>
                                <li>Paste the link here (starts with steam://joinlobby/730/)</li>
                            </ol>
                        </div>
                    </details>
                </div>
            `;
        } else {
            showToast(data.error || 'Failed to clear lobby link', 'error');
        }
    })
    .catch(error => {
        console.error('Error clearing lobby link:', error);
        showToast('Failed to clear lobby link. Please try again.', 'error');
    });
}

// Lobby countdown timer
let lobbyTimerInterval = null;

function startLobbyTimer(initialMinutes) {
    // Clear any existing timer
    if (lobbyTimerInterval) {
        clearInterval(lobbyTimerInterval);
    }

    let remainingMinutes = initialMinutes;

    lobbyTimerInterval = setInterval(() => {
        remainingMinutes--;

        const timerElement = document.getElementById('lobby-timer');
        if (timerElement) {
            timerElement.textContent = remainingMinutes;
        }

        // Auto-clear when expired
        if (remainingMinutes <= 0) {
            clearInterval(lobbyTimerInterval);
            showToast('Your lobby link has expired', 'warning');

            // Update UI to show input form
            const lobbyManagement = document.getElementById('lobby-management');
            if (lobbyManagement) {
                lobbyManagement.innerHTML = `
                    <div id="lobby-input-form">
                        <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 12px;">
                            Share your CS2 lobby link so friends can join your game.
                        </p>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <input
                                type="text"
                                id="lobby-link-input"
                                placeholder="steam://joinlobby/730/..."
                                style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                            >
                        </div>

                        <button onclick="saveLobbyLink()" class="btn btn-primary btn-sm" style="width: 100%; margin-bottom: 8px;">
                            Save Lobby Link
                        </button>
                    </div>
                `;
            }
        }
    }, 60000); // Update every minute
}

// Initialize timer if lobby is active
@if($user->profile && $user->profile->hasActiveLobby())
document.addEventListener('DOMContentLoaded', function() {
    startLobbyTimer({{ $user->profile->lobbyTimeRemaining() }});
});
@endif
@endif

// Simple toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#667eea'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;
document.head.appendChild(style);
</script>
@endsection