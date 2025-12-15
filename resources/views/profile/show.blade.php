@extends('layouts.app')

@section('title', $user->display_name . ' - Profile')

@section('content')
<x-navbar />

<div class="profile-header">
    <div class="container">
        <div class="profile-info">
            <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="profile-avatar">
            <div class="profile-details">
                <h1>{{ $user->display_name }}</h1>
                <p>{{ '@' . $user->username }}</p>
                <div style="margin-top: 16px;">
                    @if($privacyContext['canSeeOnlineStatus'])
                        <span class="status-indicator {{ $user->profile->status === 'online' ? 'status-online' : 'status-offline' }}"></span>
                        <span style="color: white;">{{ ucfirst($user->profile->status) }}</span>
                    @else
                        <span class="status-indicator status-offline"></span>
                        <span style="color: white;">Offline</span>
                    @endif
                    @if($privacyContext['canSeeGamingActivity'] && $user->profile->current_game)
                        <span style="margin-left: 16px; color: #10b981;" data-gaming-status>
                            Playing {{ $user->profile->current_game['name'] }}
                            @if(isset($user->profile->current_game['server_name']))
                                <br><span style="font-size: 12px; color: var(--color-text-muted);">{{ $user->profile->current_game['server_name'] }}</span>
                            @endif
                            @if(isset($user->profile->current_game['map']))
                                <span style="font-size: 12px; color: var(--color-text-muted);"> - {{ $user->profile->current_game['map'] }}</span>
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
                    <div class="profile-actions">
                        <div class="profile-actions-buttons">
                            <a href="{{ route('profile.edit') }}" class="btn-profile-primary">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/>
                                </svg>
                                <span>Edit Profile</span>
                            </a>
                            @if($user->steam_id)
                                <button onclick="refreshSteamData()" class="btn-profile-secondary" id="refresh-steam-btn">
                                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="refresh-icon">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                    </svg>
                                    <span id="refresh-text">Refresh Steam</span>
                                    <span id="refresh-loading" style="display: none;">
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" class="animate-spin">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </button>
                            @endif
                        </div>
                        @if($user->steam_id)
                            {{-- Steam Data Status Indicator --}}
                            <div class="profile-steam-status" id="steam-status">
                                @if($steamRefreshTriggered ?? false)
                                    <div class="steam-refreshing">
                                        <svg class="animate-spin" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Updating Steam data...</span>
                                    </div>
                                @else
                                    <div class="steam-last-updated">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        @php
                                            $lastUpdated = $user->getSteamDataLastUpdated();
                                        @endphp
                                        <span>
                                            @if($lastUpdated)
                                                Updated {{ $lastUpdated->diffForHumans() }}
                                            @else
                                                Never synced
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="profile-steam-note">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <span>Game status updates may take 1-5 minutes. Check Steam privacy settings if games don't appear.</span>
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
                <div class="profile-card">
                    <div class="profile-card-header">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        <h3 class="profile-card-title">About</h3>
                    </div>
                    <p class="{{ $user->profile->bio ? 'profile-bio' : 'profile-bio profile-bio-empty' }}">
                        {{ $user->profile->bio ?: 'This user hasn\'t written a bio yet.' }}
                    </p>
                </div>

                @if($privacyContext['canSeeSteamData'] && $user->steam_id && $user->profile->steam_data)
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                                <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                            </svg>
                            <h3 class="profile-card-title">Gaming Preferences</h3>
                        </div>
                        <p class="profile-card-subtitle" style="margin-bottom: 20px;">Based on Steam activity and playtime data</p>

                        @if($user->gamingPreferences && $user->gamingPreferences->count() > 0)
                            <div style="display: grid; gap: 16px;">
                                @foreach($user->gamingPreferences->sortByDesc('playtime_forever')->take(6) as $preference)
                                    <div class="game-preference-card">
                                        <div class="game-preference-header">
                                            <div class="game-preference-info">
                                                <h4 class="game-preference-name">{{ $preference->game_name }}</h4>
                                                <div class="game-preference-stats">
                                                    <span class="stat-item">
                                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        {{ round($preference->playtime_forever / 60, 1) }}h total
                                                    </span>
                                                    @if($preference->playtime_2weeks > 0)
                                                        <span class="stat-item stat-recent">
                                                            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                                                            </svg>
                                                            {{ round($preference->playtime_2weeks / 60, 1) }}h recent
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="game-preference-badges">
                                                @if($preference->skill_level && $preference->skill_level !== 'unranked')
                                                    <span class="skill-badge skill-{{ $preference->skill_level }}">
                                                        {{ strtoupper($preference->skill_level) }}
                                                    </span>
                                                @elseif($preference->skill_level === 'unranked')
                                                    <span class="skill-badge skill-unranked">UNRANKED</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Gaming activity bar -->
                                        <div class="game-activity-bar">
                                            <div class="activity-bar-header">
                                                <span class="activity-label">Gaming Activity</span>
                                                <span class="activity-timestamp">
                                                    @if($preference->last_played)
                                                        Last played {{ \Carbon\Carbon::parse($preference->last_played)->diffForHumans() }}
                                                    @else
                                                        Recently active
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="activity-progress-track">
                                                @php
                                                    $maxPlaytime = $user->gamingPreferences->max('playtime_forever');
                                                    $activityPercentage = $maxPlaytime > 0 ? ($preference->playtime_forever / $maxPlaytime) * 100 : 0;
                                                @endphp
                                                <div class="activity-progress-fill" style="width: {{ $activityPercentage }}%">
                                                    <div class="activity-glow"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($user->gamingPreferences->count() > 6)
                                    <div class="see-more-link" style="cursor: default;">
                                        <span>And {{ $user->gamingPreferences->count() - 6 }} more games</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div style="text-align: center; padding: 40px; background-color: var(--color-bg-primary); border-radius: 8px; border: 2px dashed var(--color-border-primary);">
                                <p style="color: var(--color-text-muted); margin-bottom: 8px;">No gaming preferences found</p>
                                <p style="color: var(--color-text-secondary); font-size: 14px;">Gaming preferences are automatically generated from Steam activity</p>
                            </div>
                        @endif
                    </div>

                    <div class="profile-card">
                        <div class="profile-card-header">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                                <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm14.5 4.5a.5.5 0 10-1 0 .5.5 0 001 0zm-1.5 2a.5.5 0 100-1 .5.5 0 000 1zm-1.5-.5a.5.5 0 10-1 0 .5.5 0 001 0zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h1a1 1 0 001-1v-1H9.5a.5.5 0 010-1H10V8a1 1 0 00-1-1H8z"/>
                            </svg>
                            <h3 class="profile-card-title">Steam Games</h3>
                        </div>
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
                                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 4px;">
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
                            <p style="color: var(--color-text-muted);">No games to display</p>
                        @endforelse
                    </div>
                @elseif($user->steam_id && !$privacyContext['canSeeSteamData'])
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #52525b;">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <h3 class="profile-card-title">Steam Data</h3>
                        </div>
                        <p style="color: var(--color-text-muted); text-align: center; padding: 30px;">
                            {{ $user->display_name }} has chosen to keep their Steam data private.
                        </p>
                    </div>
                @endif
            </div>

            <div>
                {{-- Lobby Display (display-only - creation at /lobbies) --}}
                @if(auth()->check() && $privacyContext['canSeeLobbies'])
                    <div style="margin-bottom: 24px;">
                        <x-lobby-display :user="$user" :is-own-profile="$privacyContext['isOwnProfile']" />
                    </div>
                @endif

                <div class="profile-card">
                    <div class="profile-card-header">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                        </svg>
                        <h3 class="profile-card-title">Stats</h3>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item-card">
                            <div class="stat-icon stat-icon-calendar">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-label">Member Since</span>
                                <span class="stat-value">{{ $user->created_at->format('F Y') }}</span>
                            </div>
                        </div>
                        @if($user->steam_id && $privacyContext['canSeeSteamData'])
                            <div class="stat-item-card">
                                <div class="stat-icon stat-icon-time">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <span class="stat-label">Total Playtime</span>
                                    <span class="stat-value">{{ round($user->profile->total_playtime / 60, 1) }} hours</span>
                                </div>
                            </div>
                        @endif
                        <div class="stat-item-card">
                            <div class="stat-icon stat-icon-server">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 11-2 0 1 1 0 012 0zM2 13a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2zm14 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <span class="stat-label">Servers</span>
                                <span class="stat-value">{{ $user->servers->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($privacyContext['canSeeSteamFriends'] && $user->steam_id && isset($user->profile->steam_data['friends']) && $user->profile->steam_data['friends']['count'] > 0)
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <h3 class="profile-card-title">Steam Friends</h3>
                            <span style="margin-left: auto; font-size: 13px; color: var(--color-text-muted);">({{ $user->profile->steam_data['friends']['count'] }})</span>
                        </div>
                        @foreach(array_slice($user->profile->steam_data['friends']['friends'] ?? [], 0, 5) as $friend)
                            <div class="friend-item">
                                <div class="friend-avatar-wrapper">
                                    <img src="{{ $friend['avatar'] ?? '' }}" alt="{{ $friend['personaname'] ?? 'Friend' }}" class="friend-avatar">
                                    <span class="friend-status {{ ($friend['personastate'] ?? 0) > 0 ? 'status-online' : 'status-offline' }}"></span>
                                </div>
                                <div class="friend-info">
                                    <span class="friend-name">{{ $friend['personaname'] ?? 'Unknown' }}</span>
                                    <span class="friend-activity">
                                        @if(isset($friend['gameextrainfo']))
                                            <span class="friend-playing">
                                                <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/>
                                                </svg>
                                                {{ $friend['gameextrainfo'] }}
                                            </span>
                                        @else
                                            {{ ucfirst($friend['personastate_text'] ?? 'offline') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                        @if(count($user->profile->steam_data['friends']['friends'] ?? []) > 5)
                            <div class="see-more-link" style="cursor: default;">
                                <span>And {{ count($user->profile->steam_data['friends']['friends']) - 5 }} more friends</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if($privacyContext['canSeeServers'])
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="color: #667eea;">
                                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 11-2 0 1 1 0 012 0zM2 13a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2zm14 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                            </svg>
                            <h3 class="profile-card-title">Servers</h3>
                        </div>
                        @forelse($user->servers->take(5) as $server)
                            <div class="server-item">
                                <div class="server-icon">
                                    {{ strtoupper(substr($server->name, 0, 1)) }}
                                </div>
                                <div class="server-info">
                                    <span class="server-name">{{ $server->name }}</span>
                                    <span class="server-members">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                        {{ $server->members->count() }} members
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p style="color: var(--color-text-muted); text-align: center; padding: 20px;">Not in any servers yet</p>
                        @endforelse
                    </div>
                @endif
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
let profileChannelSubscribed = false;

function setupProfileChannel() {
    if (profileChannelSubscribed) {
        console.log('[Profile] Channel already subscribed, skipping...');
        return;
    }

    if (!window.Echo) {
        console.warn('[Profile] Echo not available yet');
        return;
    }

    console.log('[Profile] Setting up Echo listener for user gaming status');
    profileChannelSubscribed = true;

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

// Try to set up channel immediately if Echo is ready
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    const pusherConnection = window.Echo.connector.pusher.connection;

    if (pusherConnection.state === 'connected') {
        setupProfileChannel();
    } else {
        pusherConnection.bind('connected', () => {
            setupProfileChannel();
        });
    }
} else {
    // Echo not initialized yet, listen for the custom event
    window.addEventListener('echo:connected', () => {
        setupProfileChannel();
    });
}

function updateGamingStatus(gameName, richPresence) {
    const statusElement = document.querySelector('[data-gaming-status]');
    if (!statusElement) return;

    if (gameName) {
        let statusText = `Playing ${gameName}`;
        if (richPresence && richPresence.server_name) {
            statusText += `<br><span style="font-size: 12px; color: var(--color-text-muted);">${richPresence.server_name}</span>`;
        }
        if (richPresence && richPresence.map) {
            statusText += `<span style="font-size: 12px; color: var(--color-text-muted);"> - ${richPresence.map}</span>`;
        }
        statusElement.innerHTML = statusText;
        statusElement.style.display = 'inline';
    } else {
        statusElement.style.display = 'none';
    }
}

// Note: CS2 Lobby Management now handled by lobby-manager component

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
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Poll for Steam refresh completion when auto-refresh was triggered
@if($steamRefreshTriggered ?? false)
(function() {
    let pollCount = 0;
    const maxPolls = 30; // 30 seconds max
    const initialLastUpdated = @json($user->getSteamDataLastUpdated()?->toIso8601String());

    const pollInterval = setInterval(async () => {
        pollCount++;

        if (pollCount >= maxPolls) {
            clearInterval(pollInterval);
            document.getElementById('steam-status').innerHTML = `
                <div class="steam-last-updated">
                    <span style="color: #f59e0b;">Refresh may still be in progress. Reload page to check.</span>
                </div>
            `;
            return;
        }

        try {
            const response = await fetch('{{ route("api.steam.status") }}', {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            // Check if data was updated (compare timestamps)
            if (data.last_updated && data.last_updated !== initialLastUpdated) {
                clearInterval(pollInterval);
                showToast('Steam data refreshed successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            }
        } catch (e) {
            console.error('Steam status poll error:', e);
        }
    }, 1000);
})();
@endif
</script>
@endsection