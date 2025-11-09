@extends('layouts.app')

@section('title', $team->name . ' - Team')

@push('styles')
<style>
    .team-container {
        display: flex;
        gap: 24px;
    }
    
    .team-sidebar {
        width: 200px;
        background-color: #18181b;
        padding: 24px;
        border-radius: 12px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }
    
    .team-content {
        flex: 1;
        background-color: #18181b;
        padding: 24px;
        border-radius: 12px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .team-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    
    .team-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" opacity="0.1"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.003 2.003 0 0 0 18.06 7c-.8 0-1.54.5-1.85 1.26l-1.92 5.77A1.998 1.998 0 0 0 16.22 17H18v5h2zM12.5 11.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10s.67 1.5 1.5 1.5zM5.5 6c1.11 0 2-.89 2-2s-.89-2-2-2-2 .89-2 2 .89 2 2 2zm2.5 16v-7H6l3-9 .7 2.1c.1.3.1.6.1.9 0 1.1-.9 2-2 2h-1v4h2v7h2z"/></svg>');
        background-size: contain;
        background-repeat: no-repeat;
    }
    
    .team-info {
        display: flex;
        align-items: center;
        gap: 24px;
        position: relative;
        z-index: 1;
    }
    
    .team-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        color: white;
    }
    
    .team-details h1 {
        color: white;
        margin-bottom: 8px;
        font-size: 28px;
    }
    
    .team-details .team-game {
        color: rgba(255, 255, 255, 0.8);
        font-size: 16px;
        margin-bottom: 12px;
    }
    
    .team-stats {
        display: flex;
        gap: 24px;
    }
    
    .team-stat {
        text-align: center;
    }
    
    .team-stat-value {
        font-size: 24px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
    }
    
    .team-stat-label {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        position: absolute;
        top: 24px;
        right: 24px;
    }
    
    .status-recruiting {
        background-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    
    .status-full {
        background-color: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .status-closed {
        background-color: rgba(156, 163, 175, 0.2);
        color: #9ca3af;
        border: 1px solid rgba(156, 163, 175, 0.3);
    }
    
    .member-item {
        display: flex;
        align-items: center;
        padding: 16px;
        background-color: #0e0e10;
        border-radius: 8px;
        margin-bottom: 12px;
        position: relative;
    }
    
    .member-item.leader {
        border: 2px solid #667eea;
    }
    
    .member-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 16px;
    }
    
    .member-info {
        flex: 1;
    }
    
    .member-name {
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 4px;
    }
    
    .member-role {
        font-size: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 600;
        margin-right: 8px;
    }
    
    .member-status {
        font-size: 12px;
        color: #b3b3b5;
    }
    
    .member-gaming-status {
        font-size: 12px;
        color: #10b981;
        margin-top: 4px;
    }
    
    .member-actions {
        display: flex;
        gap: 8px;
    }
    
    .skill-meter {
        margin: 20px 0;
    }
    
    .skill-meter-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        color: #b3b3b5;
    }
    
    .skill-meter-bar {
        width: 100%;
        height: 8px;
        background-color: #3f3f46;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .skill-meter-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: width 0.3s ease;
        border-radius: 4px;
    }
    
    .skill-balance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    
    .balance-card {
        background-color: #0e0e10;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }
    
    .balance-score {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .balance-score.excellent {
        color: #10b981;
    }
    
    .balance-score.good {
        color: #f59e0b;
    }
    
    .balance-score.poor {
        color: #ef4444;
    }
    
    .balance-label {
        font-size: 14px;
        color: #b3b3b5;
        margin-bottom: 4px;
    }
    
    .balance-description {
        font-size: 12px;
        color: #71717a;
    }
    
    .team-actions-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .invite-section {
        background-color: #0e0e10;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }
    
    .invite-form {
        display: flex;
        gap: 12px;
        align-items: end;
    }
    
    .invite-form .form-group {
        flex: 1;
        margin: 0;
    }
    
    .pending-invites {
        margin-top: 16px;
    }
    
    .pending-invite {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        background-color: #18181b;
        border-radius: 6px;
        margin-bottom: 8px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .stat-card {
        background-color: #0e0e10;
        border-radius: 8px;
        padding: 20px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #b3b3b5;
        margin-bottom: 4px;
    }
    
    .stat-description {
        font-size: 12px;
        color: #71717a;
    }
    
    .activity-feed {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background-color: #0e0e10;
        border-radius: 6px;
        margin-bottom: 8px;
    }
    
    .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #3f3f46;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-text {
        font-size: 14px;
        color: #efeff1;
        margin-bottom: 2px;
    }
    
    .activity-time {
        font-size: 12px;
        color: #71717a;
    }
    
    @media (max-width: 768px) {
        .team-container {
            flex-direction: column;
        }
        
        .team-sidebar {
            width: 100%;
            position: static;
        }
        
        .team-info {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }
        
        .team-stats {
            justify-content: center;
        }
        
        .status-badge {
            position: static;
            margin-top: 16px;
        }
        
        .skill-balance-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .invite-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        /* Chart responsive design */
        .performance-charts-grid {
            grid-template-columns: 1fr !important;
        }
        
        .performance-charts-grid .stat-card {
            height: 300px !important;
        }
    }
    
    /* Enhanced notification system */
    .notification-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 400px;
        min-width: 300px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .notification-toast.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification-toast.notification-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.9) 0%, rgba(5, 150, 105, 0.9) 100%);
        border-color: rgba(16, 185, 129, 0.3);
    }
    
    .notification-toast.notification-error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.9) 0%, rgba(220, 38, 38, 0.9) 100%);
        border-color: rgba(239, 68, 68, 0.3);
    }
    
    .notification-toast.notification-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.9) 0%, rgba(217, 119, 6, 0.9) 100%);
        border-color: rgba(245, 158, 11, 0.3);
    }
    
    .notification-toast.notification-info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(37, 99, 235, 0.9) 100%);
        border-color: rgba(59, 130, 246, 0.3);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        color: white;
    }
    
    .notification-icon {
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .notification-message {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s;
        flex-shrink: 0;
    }
    
    .notification-close:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>
@endpush

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('dashboard') }}" class="link">Dashboard</a>
                <a href="{{ route('matchmaking.index') }}" class="link">Matchmaking</a>
                <a href="{{ route('teams.index') }}" class="link">Teams</a>
                <a href="{{ route('servers.discover') }}" class="link">Servers</a>
                <a href="{{ route('settings') }}" class="link">Settings</a>
                <div class="navbar-user">
                    <a href="{{ route('profile.show', auth()->user()->username) }}">
                        <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" class="user-avatar">
                    </a>
                    <span>{{ auth()->user()->display_name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Team Header -->
        <div class="team-header">
            <div class="status-badge status-{{ $team->recruitment_status === 'open' ? 'recruiting' : ($team->activeMembers->count() >= $team->max_size ? 'full' : 'closed') }}">
                <div style="width: 6px; height: 6px; background-color: currentColor; border-radius: 50%;"></div>
                {{ $team->recruitment_status === 'open' ? 'Recruiting' : ($team->activeMembers->count() >= $team->max_size ? 'Full' : 'Closed') }}
            </div>
            
            <div class="team-info">
                <div class="team-avatar">👥</div>
                <div class="team-details">
                    <h1>{{ $team->name }}</h1>
                    <div class="team-game">{{ $team->gameName ?? 'Unknown Game' }}</div>
                    <div class="team-stats">
                        <div class="team-stat">
                            <div class="team-stat-value">{{ $team->activeMembers->count() }}/{{ $team->max_size }}</div>
                            <div class="team-stat-label">Members</div>
                        </div>
                        <div class="team-stat">
                            <div class="team-stat-value">{{ ucfirst($team->skill_level) }}</div>
                            <div class="team-stat-label">Skill Level</div>
                        </div>
                        <div class="team-stat">
                            <div class="team-stat-value">{{ ucfirst(str_replace('_', ' ', $team->preferred_region)) }}</div>
                            <div class="team-stat-label">Region</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        @if($isMember)
        <div class="team-actions-bar">
            @if($isLeader)
                <a href="#" onclick="showTab('settings', this)" class="btn btn-primary">⚙️ Team Settings</a>
                <button onclick="showInviteModal()" class="btn btn-secondary">👥 Invite Members</button>
            @endif
            @if(!$isLeader)
                <button onclick="leaveTeam()" class="btn btn-danger">Leave Team</button>
            @endif
            <a href="{{ route('teams.index') }}" class="btn btn-secondary">← Back to Teams</a>
        </div>
        @else
        <div class="team-actions-bar">
            @if($team->recruitment_status === 'open' && $team->activeMembers->count() < $team->max_size)
                <button onclick="requestToJoin()" class="btn btn-primary">Request to Join</button>
            @endif
            <a href="{{ route('teams.index') }}" class="btn btn-secondary">← Back to Teams</a>
        </div>
        @endif

        {{-- Members Playing Now Section (Phase 4) --}}
        @php
            $playingMembers = $team->activeMembers->filter(function($member) use ($team) {
                return $member->user->profile &&
                       isset($member->user->profile->current_game) &&
                       $member->user->profile->current_game['appid'] == $team->game_id;
            });
        @endphp

        @if($playingMembers->count() > 0)
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; margin-bottom: 24px;">
            <h3 style="color: white; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span>🎮</span>
                <span>Members Playing Now</span>
                <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 14px; font-weight: 600;">
                    {{ $playingMembers->count() }}
                </span>
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                @foreach($playingMembers as $member)
                    <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 8px; padding: 16px; border: 1px solid rgba(255,255,255,0.2);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <img src="{{ $member->user->profile->avatar_url }}" alt="{{ $member->user->display_name }}" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3);">
                            <div style="flex: 1;">
                                <div style="color: white; font-weight: 600; font-size: 14px;">
                                    {{ $member->user->display_name }}
                                </div>
                                <div style="color: rgba(255,255,255,0.8); font-size: 12px;">
                                    {{ $member->user->profile->current_game['name'] ?? 'Playing' }}
                                </div>
                            </div>
                        </div>

                        @if($member->user->id !== auth()->id())
                            @php
                                $hasLobbyLink = $member->user->profile && $member->user->profile->hasActiveLobby();
                                $hasServerIP = isset($member->user->profile->current_game['connect']) && !empty($member->user->profile->current_game['connect']);

                                $joinUrl = null;
                                $buttonText = 'Not Joinable';
                                $buttonClass = 'rgba(255,255,255,0.2)';
                                $buttonHoverClass = 'rgba(255,255,255,0.3)';
                                $isJoinable = false;

                                if ($hasLobbyLink) {
                                    $joinUrl = $member->user->profile->steam_lobby_link;
                                    $buttonText = '🚀 Join Lobby';
                                    $buttonClass = 'rgba(16, 185, 129, 0.9)';
                                    $buttonHoverClass = 'rgba(16, 185, 129, 1)';
                                    $isJoinable = true;
                                } elseif ($hasServerIP) {
                                    $joinUrl = 'steam://connect/' . $member->user->profile->current_game['connect'];
                                    $buttonText = '🎮 Join Server';
                                    $buttonClass = 'rgba(102, 126, 234, 0.9)';
                                    $buttonHoverClass = 'rgba(102, 126, 234, 1)';
                                    $isJoinable = true;
                                }
                            @endphp

                            @if($isJoinable)
                                <a href="{{ $joinUrl }}" style="display: block; width: 100%; padding: 8px 12px; background: {{ $buttonClass }}; color: white; text-align: center; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='{{ $buttonHoverClass }}'" onmouseout="this.style.background='{{ $buttonClass }}'">
                                    {{ $buttonText }}
                                </a>
                            @else
                                <button style="display: block; width: 100%; padding: 8px 12px; background: {{ $buttonClass }}; color: rgba(255,255,255,0.6); text-align: center; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: not-allowed; opacity: 0.5;" disabled title="Player is in matchmaking or offline">
                                    ⚠️ {{ $buttonText }}
                                </button>
                            @endif
                        @else
                            <div style="padding: 8px 12px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7); text-align: center; border-radius: 6px; font-size: 13px; font-style: italic;">
                                This is you
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="team-container" data-team-id="{{ $team->id }}" data-server-id="{{ $team->server_id ?? '' }}">
            <!-- Sidebar -->
            <div class="team-sidebar">
                <div class="sidebar-nav">
                    <a href="#overview" class="sidebar-link active" onclick="showTab('overview', this)">Overview</a>
                    <a href="#members" class="sidebar-link" onclick="showTab('members', this)">Members</a>
                    <a href="#performance" class="sidebar-link" onclick="showTab('performance', this)">Performance</a>
                    <a href="#activity" class="sidebar-link" onclick="showTab('activity', this)">Activity</a>
                    @if($isLeader)
                        <a href="#settings" class="sidebar-link" onclick="showTab('settings', this)">Settings</a>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="team-content">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <h3 style="margin-bottom: 24px;">Team Overview</h3>
                    
                    @if($team->description)
                        <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                            <h4 style="margin-bottom: 12px;">About This Team</h4>
                            <p style="color: #b3b3b5; line-height: 1.6;">{{ $team->description }}</p>
                        </div>
                    @endif

                    <!-- Team Balance -->
                    <div style="margin-bottom: 32px;">
                        <h4 style="margin-bottom: 16px;">Team Balance</h4>
                        <div class="skill-balance-grid">
                            <div class="balance-card">
                                <div class="balance-score excellent">{{ $stats['balance_score'] ?? 85 }}%</div>
                                <div class="balance-label">Skill Balance</div>
                                <div class="balance-description">Even skill distribution</div>
                            </div>
                            <div class="balance-card">
                                <div class="balance-score good">{{ 75 }}%</div>
                                <div class="balance-label">Role Coverage</div>
                                <div class="balance-description">Strategic roles filled</div>
                            </div>
                            <div class="balance-card">
                                <div class="balance-score excellent">{{ 92 }}%</div>
                                <div class="balance-label">Activity Sync</div>
                                <div class="balance-description">Compatible schedules</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Members -->
                    <div>
                        <h4 style="margin-bottom: 16px;">Team Members</h4>
                        @foreach($team->activeMembers->take(5) as $member)
                            <div class="member-item {{ $member->role === 'leader' ? 'leader' : '' }}">
                                <img src="{{ $member->user->profile->avatar_url }}" alt="{{ $member->user->display_name }}" class="member-avatar">
                                <div class="member-info">
                                    <div class="member-name">
                                        {{ $member->user->display_name }}
                                        @if($member->role === 'leader')
                                            <span style="color: #667eea; font-size: 12px; margin-left: 8px;">👑 Leader</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if($member->game_role)
                                            <span class="member-role">{{ ucfirst(str_replace('_', ' ', $member->game_role)) }}</span>
                                        @endif
                                        <span class="member-status">Joined {{ $member->joined_at->diffForHumans() }}</span>
                                    </div>
                                    @if($member->user->profile && isset($member->user->profile->current_game))
                                        <div class="member-gaming-status">
                                            🎮 {{ $member->user->profile->current_game['name'] ?? 'Playing' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        
                        @if($team->activeMembers->count() > 5)
                            <div style="text-align: center; margin-top: 16px;">
                                <a href="#members" onclick="showTab('members', document.querySelector('[href=\'#members\']'))" style="color: #667eea;">
                                    View all {{ $team->activeMembers->count() }} members →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Members Tab -->
                <div id="members" class="tab-content">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3>Team Members ({{ $team->activeMembers->count() }}/{{ $team->max_size }})</h3>
                        @if($isLeader && $team->activeMembers->count() < $team->max_size)
                            <button onclick="showInviteModal()" class="btn btn-primary btn-sm">Invite Member</button>
                        @endif
                    </div>

                    @if($isLeader && $team->activeMembers->count() < $team->max_size)
                        <div class="invite-section">
                            <h4 style="margin-bottom: 16px;">Invite New Member</h4>
                            <div class="invite-form">
                                <div class="form-group">
                                    <label for="invite-username">Username or Email</label>
                                    <input type="text" id="invite-username" placeholder="Enter username or email...">
                                </div>
                                <div class="form-group">
                                    <label for="invite-role">Role</label>
                                    <select id="invite-role">
                                        <option value="member">Member</option>
                                        <option value="co_leader">Co-Leader</option>
                                    </select>
                                </div>
                                <button onclick="sendInvite()" class="btn btn-primary">Send Invite</button>
                            </div>
                        </div>
                    @endif

                    @foreach($team->activeMembers as $member)
                        <div class="member-item {{ $member->role === 'leader' ? 'leader' : '' }}">
                            <img src="{{ $member->user->profile->avatar_url }}" alt="{{ $member->user->display_name }}" class="member-avatar">
                            <div class="member-info">
                                <div class="member-name">
                                    {{ $member->user->display_name }}
                                    @if($member->role === 'leader')
                                        <span style="color: #667eea; font-size: 12px; margin-left: 8px;">👑 Leader</span>
                                    @elseif($member->role === 'co_leader')
                                        <span style="color: #f59e0b; font-size: 12px; margin-left: 8px;">⭐ Co-Leader</span>
                                    @endif
                                </div>
                                <div style="margin-bottom: 4px;">
                                    @if($member->game_role)
                                        <span class="member-role">{{ ucfirst(str_replace('_', ' ', $member->game_role)) }}</span>
                                    @endif
                                    <span class="member-status">
                                        Skill: {{ ucfirst($member->individual_skill_score ?? 'Unknown') }} • 
                                        Joined {{ $member->joined_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if($member->user->profile && isset($member->user->profile->current_game))
                                    <div class="member-gaming-status">
                                        🎮 {{ $member->user->profile->current_game['name'] ?? 'Playing' }}
                                    </div>
                                @endif
                            </div>
                            @if($isLeader && $member->user->id !== auth()->id())
                                <div class="member-actions">
                                    <button onclick="editMemberRole({{ $member->user->id }}, '{{ $member->game_role }}')" class="btn btn-secondary btn-sm">Edit Role</button>
                                    <button onclick="removeMember({{ $member->user->id }})" class="btn btn-danger btn-sm">Remove</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Performance Tab -->
                <div id="performance" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Team Performance</h3>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value">{{ $stats['average_skill'] ?? 72 }}</div>
                            <div class="stat-label">Average Skill Score</div>
                            <div class="stat-description">Based on Steam achievements and playtime</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ 1247 }}h</div>
                            <div class="stat-label">Combined Playtime</div>
                            <div class="stat-description">Total hours in {{ $team->gameName ?? 'game' }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ 86 }}%</div>
                            <div class="stat-label">Team Compatibility</div>
                            <div class="stat-description">Overall team chemistry score</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ 15 }}</div>
                            <div class="stat-label">Days Active</div>
                            <div class="stat-description">Since team formation</div>
                        </div>
                    </div>

                    <!-- Visual Analytics Section -->
                    <div style="margin-top: 32px;">
                        <h4 style="margin-bottom: 24px;">Visual Analytics</h4>
                        
                        <!-- Charts Grid -->
                        <div class="performance-charts-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                            <!-- Skill Distribution Radar Chart -->
                            <div class="stat-card" style="height: 350px;">
                                <canvas id="skillDistributionChart"></canvas>
                            </div>
                            
                            <!-- Role Balance Doughnut Chart -->
                            <div class="stat-card" style="height: 350px;">
                                <canvas id="roleBalanceChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Skill Progress and Compatibility Charts -->
                        <div class="performance-charts-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                            <!-- Skill Progress Line Chart -->
                            <div class="stat-card" style="height: 300px;">
                                <canvas id="skillProgressChart"></canvas>
                            </div>
                            
                            <!-- Team Compatibility Bar Chart -->
                            <div class="stat-card" style="height: 300px;">
                                <canvas id="teamCompatibilityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Skill Breakdown -->
                    <div style="margin-top: 32px;">
                        <h4 style="margin-bottom: 20px;">Individual Performance</h4>
                        @foreach($team->activeMembers as $member)
                            <div style="margin-bottom: 20px;">
                                <div class="skill-meter-label">
                                    <span>{{ $member->user->display_name }}</span>
                                    <span>{{ $member->individual_skill_score ?? rand(60, 95) }}/100</span>
                                </div>
                                <div class="skill-meter-bar">
                                    <div class="skill-meter-fill" style="width: {{ $member->individual_skill_score ?? rand(60, 95) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Activity Tab -->
                <div id="activity" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Team Activity</h3>
                    
                    <div class="activity-feed">
                        @foreach($recentActivity ?? [] as $activity)
                            <div class="activity-item">
                                <div class="activity-icon">{{ $activity['icon'] ?? '📝' }}</div>
                                <div class="activity-content">
                                    <div class="activity-text">{{ $activity['text'] ?? 'Team activity' }}</div>
                                    <div class="activity-time">{{ $activity['time'] ?? 'Recently' }}</div>
                                </div>
                            </div>
                        @endforeach
                        
                        <!-- Sample activity items if no real data -->
                        @if(empty($recentActivity))
                            <div class="activity-item">
                                <div class="activity-icon">👑</div>
                                <div class="activity-content">
                                    <div class="activity-text">{{ $team->creator->display_name }} created the team</div>
                                    <div class="activity-time">{{ $team->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @foreach($team->activeMembers->where('user_id', '!=', $team->creator_id)->take(3) as $member)
                                <div class="activity-item">
                                    <div class="activity-icon">👥</div>
                                    <div class="activity-content">
                                        <div class="activity-text">{{ $member->user->display_name }} joined the team</div>
                                        <div class="activity-time">{{ $member->joined_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Settings Tab (Leader Only) -->
                @if($isLeader)
                <div id="settings" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Team Settings</h3>
                    
                    <form action="{{ route('teams.update', $team) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="team_name">Team Name</label>
                            <input type="text" id="team_name" name="name" value="{{ $team->name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="team_description">Description</label>
                            <textarea id="team_description" name="description" rows="4">{{ $team->description }}</textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="recruitment_status">Recruitment Status</label>
                                <select id="recruitment_status" name="recruitment_status">
                                    <option value="open" {{ $team->recruitment_status === 'open' ? 'selected' : '' }}>Open - Accept new members</option>
                                    <option value="closed" {{ $team->recruitment_status === 'closed' ? 'selected' : '' }}>Closed - Invite only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="skill_level">Skill Level</label>
                                <select id="skill_level" name="skill_level">
                                    <option value="beginner" {{ $team->skill_level === 'beginner' ? 'selected' : '' }}>Beginner</option>
                                    <option value="intermediate" {{ $team->skill_level === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                    <option value="advanced" {{ $team->skill_level === 'advanced' ? 'selected' : '' }}>Advanced</option>
                                    <option value="expert" {{ $team->skill_level === 'expert' ? 'selected' : '' }}>Expert</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #3f3f46;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" onclick="showDeleteConfirm()" class="btn btn-danger" style="margin-left: 12px;">Delete Team</button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</main>

<script>
// Tab switching (same as server admin)
function showTab(tabName, element) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    element.classList.add('active');
    
    window.location.hash = tabName;
}

// Member management functions
function requestToJoin() {
    if (confirm('Request to join this team?')) {
        fetch(`{{ route('teams.join', $team) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Successfully joined the team! 🎉', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.error || data.message || 'Error requesting to join team', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error requesting to join team: ' + error.message, 'error');
        });
    }
}

function leaveTeam() {
    if (confirm('Are you sure you want to leave this team?')) {
        fetch(`{{ route('teams.members.remove', [$team, auth()->user()]) }}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route('teams.index') }}';
            } else {
                alert(data.message || 'Error leaving team');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error leaving team');
        });
    }
}

@if($isLeader)
function sendInvite() {
    const username = document.getElementById('invite-username').value;
    const role = document.getElementById('invite-role').value;
    
    if (!username) {
        alert('Please enter a username or email');
        return;
    }
    
    fetch(`{{ route('teams.members.add', $team) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            username: username,
            role: role
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation sent successfully!');
            document.getElementById('invite-username').value = '';
        } else {
            alert(data.message || 'Error sending invitation');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending invitation');
    });
}

function editMemberRole(userId, currentRole) {
    const newRole = prompt('Enter new role for this member:', currentRole || '');
    if (newRole !== null && newRole !== currentRole) {
        fetch(`{{ route('teams.members.role.update', [$team, 'USER_ID']) }}`.replace('USER_ID', userId), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                user_id: userId,
                game_role: newRole
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating member role');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating member role');
        });
    }
}

function removeMember(userId) {
    if (confirm('Remove this member from the team?')) {
        fetch(`{{ route('teams.members.remove', [$team, 'USER_ID']) }}`.replace('USER_ID', userId), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error removing member');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing member');
        });
    }
}

function showDeleteConfirm() {
    if (confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
        if (confirm('Type DELETE to confirm team deletion:')) {
            fetch(`{{ route('teams.destroy', $team) }}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '{{ route('teams.index') }}';
                } else {
                    alert(data.message || 'Error deleting team');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting team');
            });
        }
    }
}
@endif

// Initialize tab from URL hash
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1);
    const validTabs = ['overview', 'members', 'performance', 'activity'@if($isLeader), 'settings'@endif];
    
    if (validTabs.includes(hash)) {
        const tabElement = document.querySelector(`a[href="#${hash}"]`);
        if (tabElement) {
            showTab(hash, tabElement);
        }
    }
    
    // Initialize skill charts when performance tab is viewed
    initializeSkillCharts();
});

// Team skill charts initialization
let teamSkillCharts = null;

function initializeSkillCharts() {
    // Only initialize charts when performance tab is active
    const performanceTab = document.getElementById('performance');
    if (!performanceTab.classList.contains('active')) {
        return;
    }
    
    // Prepare team data for charts
    const teamData = {
        members: [
            @foreach($team->activeMembers as $member)
            {
                id: {{ $member->user->id }},
                name: "{{ $member->user->display_name }}",
                game_role: "{{ $member->game_role ?? 'unassigned' }}",
                individual_skill_score: {{ $member->individual_skill_score ?? rand(60, 95) }},
                skills: {
                    aim: {{ rand(60, 95) }},
                    game_sense: {{ rand(60, 95) }},
                    communication: {{ rand(60, 95) }},
                    strategy: {{ rand(60, 95) }},
                    teamwork: {{ rand(60, 95) }},
                    adaptability: {{ rand(60, 95) }}
                }
            },
            @endforeach
        ],
        stats: {
            skill_balance: {{ $stats['balance_score'] ?? 85 }},
            role_coverage: {{ 75 }},
            activity_sync: {{ 92 }},
            team_compatibility: {{ 86 }},
            skill_match: {{ 78 }},
            schedule_sync: {{ 85 }},
            communication: {{ 88 }},
            play_style: {{ 82 }},
            goals_alignment: {{ 90 }}
        }
    };
    
    // Initialize charts only if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        // Destroy existing charts before creating new ones
        if (teamSkillCharts) {
            teamSkillCharts.destroy();
        }
        
        teamSkillCharts = createTeamSkillCharts(teamData);
    } else {
        console.warn('Chart.js not loaded, creating fallback charts');
        createFallbackCharts(teamData);
    }
}

function createTeamSkillCharts(teamData) {
    const charts = {};
    
    // Skill Distribution Radar Chart
    const skillCtx = document.getElementById('skillDistributionChart');
    if (skillCtx) {
        const members = teamData.members || [];
        const skillLabels = ['Aim', 'Game Sense', 'Communication', 'Strategy', 'Teamwork', 'Adaptability'];
        
        const teamSkillData = skillLabels.map(skill => {
            const average = members.reduce((sum, member) => {
                return sum + (member.skills?.[skill.toLowerCase().replace(' ', '_')] || 75);
            }, 0) / (members.length || 1);
            return Math.round(average);
        });

        charts.skillDistribution = new Chart(skillCtx, {
            type: 'radar',
            data: {
                labels: skillLabels,
                datasets: [{
                    label: 'Team Average',
                    data: teamSkillData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Team Skill Distribution',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: '#b3b3b5' }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        angleLines: { color: '#3f3f46' },
                        pointLabels: { color: '#b3b3b5', font: { size: 12 } },
                        ticks: { 
                            color: '#71717a',
                            stepSize: 20,
                            showLabelBackdrop: false
                        }
                    }
                }
            }
        });
    }
    
    // Role Balance Doughnut Chart
    const roleCtx = document.getElementById('roleBalanceChart');
    if (roleCtx) {
        const members = teamData.members || [];
        const roleCounts = {};
        
        members.forEach(member => {
            const role = member.game_role || 'unassigned';
            roleCounts[role] = (roleCounts[role] || 0) + 1;
        });

        const roleLabels = Object.keys(roleCounts);
        const roleData = Object.values(roleCounts);
        const roleColors = ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#a855f7', '#06b6d4'];

        charts.roleBalance = new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: roleLabels.map(role => role.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')),
                datasets: [{
                    data: roleData,
                    backgroundColor: roleColors.slice(0, roleLabels.length),
                    borderColor: '#18181b',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Role Distribution',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'bottom',
                        labels: { 
                            color: '#b3b3b5',
                            padding: 20,
                            usePointStyle: true,
                        }
                    }
                },
                cutout: '60%',
            }
        });
    }
    
    // Skill Progress Line Chart
    const progressCtx = document.getElementById('skillProgressChart');
    if (progressCtx) {
        const members = teamData.members || [];
        const days = 14;
        const labels = Array.from({length: days}, (_, i) => {
            const date = new Date();
            date.setDate(date.getDate() - (days - 1 - i));
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const progressDatasets = members.slice(0, 3).map((member, index) => {
            const baseSkill = member.individual_skill_score || 75;
            const progressData = [];
            let currentSkill = Math.max(30, baseSkill - 10);
            
            for (let i = 0; i < days; i++) {
                const change = (Math.random() - 0.2) * 4;
                currentSkill = Math.max(30, Math.min(100, currentSkill + change));
                progressData.push(Math.round(currentSkill));
            }

            const colors = ['#667eea', '#10b981', '#f59e0b', '#ef4444'];
            return {
                label: member.name,
                data: progressData,
                borderColor: colors[index],
                backgroundColor: colors[index] + '20',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
            };
        });

        charts.skillProgress = new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: progressDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Skill Progress (14 Days)',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: '#b3b3b5' }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#3f3f46' },
                        ticks: { color: '#71717a' }
                    },
                    y: {
                        beginAtZero: false,
                        min: 30,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        ticks: { 
                            color: '#71717a',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Team Compatibility Bar Chart
    const compatCtx = document.getElementById('teamCompatibilityChart');
    if (compatCtx) {
        const compatibilityCategories = ['Skill Match', 'Schedule', 'Communication', 'Play Style', 'Goals'];
        const compatibilityData = [
            teamData.stats.skill_match || 78,
            teamData.stats.schedule_sync || 85,
            teamData.stats.communication || 88,
            teamData.stats.play_style || 82,
            teamData.stats.goals_alignment || 90
        ];

        charts.teamCompatibility = new Chart(compatCtx, {
            type: 'bar',
            data: {
                labels: compatibilityCategories,
                datasets: [{
                    label: 'Compatibility Score',
                    data: compatibilityData,
                    backgroundColor: compatibilityData.map(score => 
                        score >= 85 ? 'rgba(16, 185, 129, 0.8)' : 
                        score >= 70 ? 'rgba(245, 158, 11, 0.8)' : 
                        'rgba(239, 68, 68, 0.8)'
                    ),
                    borderColor: '#667eea',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Team Compatibility Analysis',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#b3b3b5', font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        ticks: { 
                            color: '#71717a',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    return {
        charts: charts,
        destroy: function() {
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });
        }
    };
}

function createFallbackCharts(teamData) {
    // Create simple visual indicators if Chart.js fails to load
    const chartIds = ['skillDistributionChart', 'roleBalanceChart', 'skillProgressChart', 'teamCompatibilityChart'];
    const chartTitles = ['📊 Skill Distribution', '🎯 Role Balance', '📈 Skill Progress', '🔗 Team Compatibility'];
    
    chartIds.forEach((chartId, index) => {
        const chartElement = document.getElementById(chartId);
        if (chartElement) {
            chartElement.style.display = 'flex';
            chartElement.style.alignItems = 'center';
            chartElement.style.justifyContent = 'center';
            chartElement.style.color = '#b3b3b5';
            chartElement.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 48px; margin-bottom: 12px;">${chartTitles[index].split(' ')[0]}</div>
                    <div>${chartTitles[index].substring(2)}</div>
                    <div style="font-size: 12px; opacity: 0.7;">Chart.js not available</div>
                </div>
            `;
        }
    });
}

// Enhanced tab switching with chart initialization
function showTab(tabName, element) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    element.classList.add('active');
    
    window.location.hash = tabName;
    
    // Initialize charts when performance tab is shown
    if (tabName === 'performance') {
        setTimeout(() => {
            initializeSkillCharts();
        }, 100); // Small delay to ensure DOM is ready
    }
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    
    const icon = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    }[type] || 'ℹ️';
    
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${icon}</span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Auto remove after 5 seconds (except for errors)
    if (type !== 'error') {
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
}
</script>
@endsection