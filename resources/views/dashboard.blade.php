@extends('layouts.app')

@section('title', 'Dashboard - Glyph')

@section('content')
<x-navbar />

<main x-data="dashboardController()">
    <div class="dashboard-container">
        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom: 24px;">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" style="margin-bottom: 24px;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Hero Section -->
        <section class="dashboard-hero">
            <div class="dashboard-hero-bg">
                <div class="hero-mesh-gradient"></div>
                <div class="hero-glow-orb hero-glow-1"></div>
                <div class="hero-glow-orb hero-glow-2"></div>
            </div>
            <div class="dashboard-hero-content">
                <span class="hero-greeting" x-text="greeting">Good evening</span>
                <h1 class="hero-username">{{ $user->display_name }}</h1>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="stat-value">{{ $user->servers ? $user->servers->count() : 0 }}</span>
                        <span class="stat-label">Servers</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-value">{{ $user->teams ? $user->teams->count() : 0 }}</span>
                        <span class="stat-label">Teams</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-value">{{ $onlineFriends->count() }}</span>
                        <span class="stat-label">Friends Online</span>
                    </div>
                    @if($user->steam_id)
                    <div class="hero-stat">
                        <span class="stat-value">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" style="display: inline; vertical-align: middle;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="stat-label">Steam Linked</span>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="dashboard-actions">
            <div class="action-group">
                <h4 class="action-group-label">Create</h4>
                <div class="action-grid">
                    <a href="{{ route('teams.create') }}" class="action-card" data-stagger="0">
                        <div class="action-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        </div>
                        <div class="action-content">
                            <span class="action-title">Create Team</span>
                            <span class="action-desc">Start your squad</span>
                        </div>
                    </a>
                    <a href="{{ route('server.create') }}" class="action-card" data-stagger="1">
                        <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <svg viewBox="0 0 24 24"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg>
                        </div>
                        <div class="action-content">
                            <span class="action-title">Create Server</span>
                            <span class="action-desc">Build a community</span>
                        </div>
                    </a>
                    <a href="{{ route('server.join') }}" class="action-card" data-stagger="2">
                        <div class="action-icon" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
                            <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        </div>
                        <div class="action-content">
                            <span class="action-title">Join Server</span>
                            <span class="action-desc">Enter invite code</span>
                        </div>
                    </a>
                    @if(!$user->steam_id)
                    <a href="{{ route('steam.link') }}" class="action-card" data-stagger="3">
                        <div class="action-icon action-icon--steam" style="background: linear-gradient(135deg, #1b2838 0%, #2a475e 100%);">
                            <svg viewBox="0 0 256 259" class="steam-logo">
                                <path fill="white" d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0z"/>
                            </svg>
                        </div>
                        <div class="action-content">
                            <span class="action-title">Link Steam</span>
                            <span class="action-desc">Unlock all features</span>
                        </div>
                    </a>
                    @else
                    <a href="{{ route('profile.show', $user->username) }}" class="action-card" data-stagger="3">
                        <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div class="action-content">
                            <span class="action-title">View Profile</span>
                            <span class="action-desc">Your gaming identity</span>
                        </div>
                    </a>
                    @endif
                </div>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="dashboard-main">
            <div class="dashboard-content">
                <!-- Recommended Servers Widget -->
                @if($user->steam_id && isset($recommendations) && $recommendations->isNotEmpty())
                <div class="dashboard-widget" data-stagger="0">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <span class="widget-title-icon">
                                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            </span>
                            Recommended Servers
                        </h3>
                        <a href="{{ route('recommendations.index') }}" class="widget-link">View all</a>
                    </div>
                    <p class="widget-subtitle">Based on your Steam gaming activity</p>

                    @foreach($recommendations as $recommendation)
                        @php
                            $score = $recommendation['score'];
                            $scoreClass = $score >= 70 ? 'score-high' : ($score >= 50 ? 'score-medium' : 'score-low');
                        @endphp
                        <div class="server-card">
                            <div class="server-card-header">
                                <div class="server-icon-wrapper">
                                    @if($recommendation['server']->icon_url)
                                        <img src="{{ $recommendation['server']->icon_url }}" alt="{{ $recommendation['server']->name }}" class="server-icon">
                                    @else
                                        <span class="server-icon-placeholder">{{ substr($recommendation['server']->name, 0, 2) }}</span>
                                    @endif
                                </div>
                                <div class="server-info">
                                    <h4 class="server-name">
                                        <a href="{{ route('server.show', $recommendation['server']) }}">{{ $recommendation['server']->name }}</a>
                                    </h4>
                                    <span class="server-meta">{{ $recommendation['server']->members->count() }} members</span>
                                </div>
                                <div class="server-match-ring">
                                    <svg viewBox="0 0 36 36" class="match-ring-svg animated">
                                        <circle class="ring-bg" cx="18" cy="18" r="16"/>
                                        <circle class="ring-fill {{ $scoreClass }}" cx="18" cy="18" r="16"
                                                style="stroke-dasharray: {{ $score }}, 100"/>
                                    </svg>
                                    <span class="match-value">{{ number_format($score, 0) }}%</span>
                                </div>
                            </div>

                            @if($recommendation['server']->description)
                                <p class="server-description">{{ Str::limit($recommendation['server']->description, 100) }}</p>
                            @endif

                            @if(!empty($recommendation['reasons']))
                                <div class="server-tags">
                                    @foreach(array_slice($recommendation['reasons'], 0, 3) as $reason)
                                        <span class="server-reason">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($recommendation['server']->tags && $recommendation['server']->tags->count() > 0)
                                <div class="server-tags">
                                    @foreach($recommendation['server']->tags->take(4) as $tag)
                                        <span class="server-tag">{{ $tag->tag_value }}</span>
                                    @endforeach
                                    @if($recommendation['server']->tags->count() > 4)
                                        <span style="font-size: 11px; color: var(--color-text-muted);">+{{ $recommendation['server']->tags->count() - 4 }} more</span>
                                    @endif
                                </div>
                            @endif

                            <div class="server-actions">
                                <a href="{{ route('server.show', $recommendation['server']) }}" class="btn-server-view">View Server</a>
                                @if(!$recommendation['server']->members->contains(auth()->user()))
                                    <form method="POST" action="{{ route('server.join.direct', $recommendation['server']) }}" style="display: inline;" class="server-join-form">
                                        @csrf
                                        <button type="submit" class="btn-server-join">Join Server</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="widget-footer">
                        <a href="{{ route('servers.discover') }}" class="widget-link">Discover all servers</a>
                    </div>
                </div>
                @endif

                <!-- My Teams Widget -->
                @if(isset($user->teams) && $user->teams && $user->teams->count() > 0)
                <div class="dashboard-widget" data-stagger="1">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <span class="widget-title-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </span>
                            My Teams
                        </h3>
                        <a href="{{ route('teams.index') }}" class="widget-link">View all</a>
                    </div>
                    <p class="widget-subtitle">Your active team memberships</p>

                    @foreach($user->teams->take(3) as $team)
                        <div class="team-card">
                            <div class="team-card-header">
                                <div class="team-info">
                                    <h4 class="team-name">
                                        <a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a>
                                    </h4>
                                    <span class="team-game">{{ $team->game_name ?? 'Unknown Game' }}</span>
                                </div>
                                <div class="team-size">
                                    <span class="team-size-value">{{ $team->activeMembers ? $team->activeMembers->count() : 0 }}/{{ $team->max_size ?? $team->max_members ?? 5 }}</span>
                                    <span class="team-size-label">members</span>
                                </div>
                            </div>

                            <div class="team-members-stack">
                                @if($team->activeMembers)
                                    @foreach($team->activeMembers->take(4) as $member)
                                        <img src="{{ $member->user->profile->avatar_url }}"
                                             alt="{{ $member->user->display_name }}"
                                             class="team-member-avatar"
                                             title="{{ $member->user->display_name }}">
                                    @endforeach
                                    @if($team->activeMembers->count() > 4)
                                        <div class="team-overflow">+{{ $team->activeMembers->count() - 4 }}</div>
                                    @endif
                                    @if($team->activeMembers->where('user_id', $user->id)->where('role', 'leader')->count() > 0)
                                        <span class="team-leader-badge">Leader</span>
                                    @endif
                                @endif
                            </div>

                            <div class="team-actions">
                                <a href="{{ route('teams.show', $team) }}" class="btn-server-view">Manage Team</a>
                                @if(($team->team_data['recruitment_status'] ?? 'closed') === 'open' && ($team->activeMembers ? $team->activeMembers->count() : 0) < ($team->max_size ?? 5))
                                    <span class="team-recruiting-badge">Recruiting</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($user->teams->count() > 3)
                        <div class="widget-footer">
                            <a href="{{ route('teams.index') }}" class="widget-link">View all {{ $user->teams->count() }} teams</a>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Active Matchmaking Widget -->
                @if(isset($user->activeMatchmakingRequests) && $user->activeMatchmakingRequests && $user->activeMatchmakingRequests->count() > 0)
                <div class="dashboard-widget" data-stagger="2">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <span class="widget-title-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                            Active Matchmaking
                        </h3>
                        <a href="{{ route('matchmaking.index') }}" class="widget-link">Manage all</a>
                    </div>
                    <p class="widget-subtitle">Your current team search requests</p>

                    @foreach($user->activeMatchmakingRequests->take(2) as $request)
                        <div class="matchmaking-card">
                            <div class="matchmaking-card-header">
                                <div class="matchmaking-info">
                                    <h4>{{ $request->game_name ?? 'Unknown Game' }}</h4>
                                    <p class="matchmaking-criteria">
                                        {{ ucfirst($request->skill_level ?? 'any') }} &bull;
                                        @if($request->preferred_regions && count($request->preferred_regions) > 0)
                                            {{ ucfirst(str_replace('_', ' ', $request->preferred_regions[0])) }}
                                        @else
                                            Any Region
                                        @endif
                                        &bull;
                                        @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                            {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                        @else
                                            Any Role
                                        @endif
                                    </p>
                                </div>
                                <div class="matchmaking-status">
                                    <span class="matchmaking-pulse"></span>
                                    Searching
                                </div>
                            </div>

                            <p class="matchmaking-time">Created {{ $request->created_at->diffForHumans() }}</p>

                            <div class="matchmaking-actions">
                                <a href="{{ route('matchmaking.index') }}" class="btn-matchmaking-view">View Matches</a>
                                <button onclick="cancelMatchmakingRequest({{ $request->id }})" class="btn-matchmaking-cancel">Cancel</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif

                <!-- Server Goals Widget -->
                @if($user->servers && $user->servers->isNotEmpty())
                    @php
                        $activeGoals = collect();
                        foreach($user->servers as $server) {
                            if($server->goals && $server->goals->where('status', 'active')->count() > 0) {
                                $activeGoals = $activeGoals->merge($server->goals->where('status', 'active'));
                            }
                        }
                    @endphp

                    @if($activeGoals->count() > 0)
                    <div class="dashboard-widget" data-stagger="3">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <span class="widget-title-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                    <svg viewBox="0 0 24 24"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>
                                </span>
                                Community Goals
                            </h3>
                        </div>
                        <p class="widget-subtitle">Active goals from your servers</p>

                        <!-- SVG Gradient Definition -->
                        <svg width="0" height="0" style="position: absolute;">
                            <defs>
                                <linearGradient id="goalGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#667eea"/>
                                    <stop offset="100%" style="stop-color:#764ba2"/>
                                </linearGradient>
                            </defs>
                        </svg>

                        @foreach($activeGoals->take(2) as $goal)
                            @php
                                $percentage = $goal->target_value > 0 ? min(($goal->current_progress ?? 0) / $goal->target_value * 100, 100) : 0;
                            @endphp
                            <div class="goal-card">
                                <div class="goal-header">
                                    <div class="goal-ring" style="--progress: {{ $percentage }}">
                                        <svg viewBox="0 0 100 100">
                                            <circle class="goal-ring-bg" cx="50" cy="50" r="45"/>
                                            <circle class="goal-ring-fill" cx="50" cy="50" r="45"/>
                                        </svg>
                                        <span class="goal-ring-value">{{ round($percentage) }}%</span>
                                    </div>
                                    <div class="goal-info">
                                        <h4 class="goal-title">{{ $goal->title }}</h4>
                                        <span class="goal-server">{{ $goal->server->name ?? 'Server' }}</span>
                                        <p class="goal-progress-text">{{ $goal->current_progress ?? 0 }} / {{ $goal->target_value }}</p>
                                    </div>
                                </div>
                                <div class="goal-footer">
                                    <span class="goal-participants">{{ $goal->participants->count() ?? 0 }} participants</span>
                                    <a href="{{ route('server.show', $goal->server) }}#goals" class="goal-link">View Goal</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                @endif

                <!-- Recent Activity Widget -->
                <div class="dashboard-widget" data-stagger="4">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <span class="widget-title-icon" style="background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </span>
                            Recent Activity
                        </h3>
                    </div>

                    @if(count($recentActivity) > 0)
                        <div class="activity-timeline">
                            @foreach($recentActivity as $activity)
                                <div class="activity-item" data-type="{{ $activity['type'] }}">
                                    <div class="activity-line"></div>
                                    <div class="activity-dot"></div>
                                    <div class="activity-content">
                                        <p class="activity-text">
                                            @if($activity['type'] === 'message')
                                                <strong>{{ $activity['user'] }}</strong> posted in
                                                <span class="activity-highlight message">#{{ $activity['channel'] }}</span>
                                                <span class="activity-server">in {{ $activity['server'] }}</span>
                                            @elseif($activity['type'] === 'join')
                                                <strong>{{ $activity['user'] }}</strong> joined
                                                <span class="activity-highlight join">{{ $activity['server'] }}</span>
                                            @elseif($activity['type'] === 'team_join')
                                                <strong>{{ $activity['user'] }}</strong> joined team
                                                <span class="activity-highlight team_join">{{ $activity['server'] }}</span>
                                                @if($activity['channel'])
                                                    <span class="activity-server">{{ $activity['channel'] }}</span>
                                                @endif
                                            @elseif($activity['type'] === 'friend_accept')
                                                <strong>{{ $activity['user'] }}</strong>
                                                <span class="activity-highlight friend_accept">accepted your friend request</span>
                                            @endif
                                        </p>
                                        <span class="activity-time">{{ $activity['time'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="activity-empty">
                            <p>No recent activity to show</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <!-- Online Friends -->
                <div class="sidebar-section" data-stagger="0">
                    <h3 class="sidebar-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                        Online Friends
                    </h3>

                    @if($onlineFriends->count() > 0)
                        <div class="friends-list">
                            @foreach($onlineFriends as $friend)
                                <a href="{{ route('profile.show', $friend->username) }}" class="friend-card">
                                    <div class="friend-avatar-wrapper">
                                        <img src="{{ $friend->profile->avatar_url }}" alt="{{ $friend->display_name }}" class="friend-avatar">
                                        <span class="friend-status-dot"></span>
                                    </div>
                                    <div class="friend-info">
                                        <span class="friend-name">{{ $friend->display_name }}</span>
                                        @if($friend->profile->current_game)
                                            <span class="friend-activity">Playing {{ $friend->profile->current_game }}</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="friends-empty">
                            <div class="friends-empty-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="19" y1="8" x2="19" y2="14"></line>
                                    <line x1="22" y1="11" x2="16" y2="11"></line>
                                </svg>
                            </div>
                            <p>No friends online</p>
                            <a href="{{ route('friends.search') }}">Find friends</a>
                        </div>
                    @endif
                </div>

                <!-- Your Servers -->
                @if($user->servers && $user->servers->isNotEmpty())
                <div class="sidebar-section" data-stagger="1">
                    <h3 class="sidebar-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                        Your Servers
                    </h3>
                    <div class="servers-list">
                        @foreach($user->servers as $server)
                            <a href="{{ route('server.show', $server) }}" class="sidebar-server-link">
                                @if($server->icon_url)
                                    <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" class="sidebar-server-icon">
                                @else
                                    <div class="sidebar-server-icon-placeholder">{{ substr($server->name, 0, 2) }}</div>
                                @endif
                                <div class="sidebar-server-info">
                                    <span class="sidebar-server-name">{{ $server->name }}</span>
                                    <span class="sidebar-server-members">{{ $server->members->count() }} members</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </aside>
        </div>
    </div>
</main>

<script>
// Alpine.js Dashboard Controller
window.dashboardController = function() {
    return {
        greeting: '',
        init() {
            // Set greeting based on time of day
            const hour = new Date().getHours();
            if (hour < 12) {
                this.greeting = 'Good morning';
            } else if (hour < 18) {
                this.greeting = 'Good afternoon';
            } else {
                this.greeting = 'Good evening';
            }
        }
    };
};

// Cancel matchmaking request
function cancelMatchmakingRequest(requestId) {
    if (confirm('Cancel this matchmaking request?')) {
        fetch(`{{ url('/matchmaking/requests') }}/${requestId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error canceling request');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error canceling request');
        });
    }
}

// Handle direct join with AJAX for better UX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.server-join-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalText = button.textContent;

            // Show loading state
            button.disabled = true;
            button.textContent = 'Joining...';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    button.textContent = 'Joined!';
                    button.style.background = '#059669';

                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 800);
                } else {
                    // Show error and restore button
                    alert(data.message || 'Failed to join server');
                    button.disabled = false;
                    button.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while joining the server');
                button.disabled = false;
                button.textContent = originalText;
            });
        });
    });
});
</script>

{{-- Steam Reminder Modal --}}
@if($showSteamReminder ?? false)
    <x-steam-reminder-modal :show="true" />
@endif
@endsection
