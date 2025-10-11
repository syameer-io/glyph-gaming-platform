@extends('layouts.app')

@section('title', 'Matchmaking - Glyph')

@push('styles')
<style>
    .matchmaking-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    .matchmaking-sidebar {
        background-color: #18181b;
        border-radius: 12px;
        padding: 24px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }
    
    .matchmaking-content {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    
    .filter-section {
        margin-bottom: 24px;
    }
    
    .filter-section h4 {
        color: #efeff1;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .filter-group {
        margin-bottom: 16px;
    }
    
    .filter-group label {
        display: block;
        font-size: 14px;
        color: #b3b3b5;
        margin-bottom: 6px;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 8px 12px;
        background-color: #0e0e10;
        border: 1px solid #3f3f46;
        border-radius: 6px;
        color: #efeff1;
        font-size: 14px;
    }
    
    .status-card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #3f3f46;
    }
    
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
        min-width: fit-content;
    }
    
    .status-active {
        background-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }
    
    .status-searching {
        background-color: rgba(102, 126, 234, 0.2);
        color: #667eea;
    }
    
    .status-in-team {
        background-color: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }
    
    .live-indicator {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        background-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .live-indicator::before {
        content: '';
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        margin-right: 8px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .team-card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #3f3f46;
        transition: all 0.2s;
    }
    
    .team-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .team-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }
    
    .team-name {
        font-size: 18px;
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 4px;
    }
    
    .team-game {
        font-size: 14px;
        color: #b3b3b5;
    }
    
    .compatibility-score {
        text-align: right;
    }
    
    .score-value {
        font-size: 24px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .score-label {
        font-size: 12px;
        color: #b3b3b5;
        text-transform: uppercase;
    }
    
    .team-members {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
    }
    
    .member-slot {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        flex: 1;
        padding: 12px;
        background-color: #0e0e10;
        border-radius: 8px;
        border: 1px solid #3f3f46;
    }
    
    .member-slot.filled {
        border-color: #10b981;
    }
    
    .member-slot.empty {
        border: 2px dashed #3f3f46;
    }
    
    .member-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .member-placeholder {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #3f3f46;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #71717a;
        font-size: 18px;
    }
    
    .member-name {
        font-size: 12px;
        color: #b3b3b5;
        text-align: center;
    }
    
    .member-role {
        font-size: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .team-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }
    
    .team-tag {
        font-size: 11px;
        background-color: #3f3f46;
        color: #b3b3b5;
        padding: 4px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    
    .team-actions {
        display: flex;
        gap: 8px;
    }
    
    .card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #3f3f46;
        position: relative;
        overflow: hidden;
        width: 100%;
        box-sizing: border-box;
    }
    
    .card-header {
        color: #efeff1;
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .request-card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #3f3f46;
        position: relative;
        width: 100%;
        box-sizing: border-box;
    }
    
    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    
    .request-status {
        flex-shrink: 0;
        margin-left: 16px;
    }
    
    .request-game {
        font-size: 16px;
        font-weight: 600;
        color: #efeff1;
        flex: 1;
        min-width: 0;
    }
    
    .request-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }
    
    .request-detail {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .request-detail-label {
        font-size: 12px;
        color: #71717a;
        text-transform: uppercase;
    }
    
    .request-detail-value {
        font-size: 14px;
        color: #efeff1;
        font-weight: 500;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .quick-action {
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        text-decoration: none;
        color: #efeff1;
        transition: all 0.2s;
    }
    
    .quick-action:hover {
        border-color: #667eea;
        transform: translateY(-1px);
    }
    
    .quick-action-icon {
        font-size: 24px;
        margin-bottom: 8px;
        display: block;
    }
    
    .quick-action-title {
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .quick-action-desc {
        font-size: 12px;
        color: #b3b3b5;
    }
    
    @media (max-width: 768px) {
        .matchmaking-container {
            grid-template-columns: 1fr;
        }
        
        .matchmaking-sidebar {
            position: static;
        }
        
        .team-members {
            flex-wrap: wrap;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
        
        .request-details {
            grid-template-columns: 1fr;
        }
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
                <a href="{{ route('matchmaking.index') }}" class="link" style="color: #667eea;">Matchmaking</a>
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

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <h1>🎮 Team Matchmaking</h1>
            <div style="display: flex; gap: 12px; align-items: center;">
                @if(auth()->user()->activeMatchmakingRequests()->exists())
                    <span class="status-indicator status-searching">
                        <span style="width: 8px; height: 8px; background-color: #667eea; border-radius: 50%; display: inline-block;"></span>
                        Searching for teams
                    </span>
                @endif
                <a href="#" class="btn btn-primary" onclick="showCreateRequestModal()">Create Request</a>
            </div>
        </div>

        <div class="matchmaking-container">
            <!-- Sidebar Filters -->
            <div class="matchmaking-sidebar">
                <div class="filter-section">
                    <h4>Quick Actions</h4>
                    <div class="quick-actions">
                        <a href="#" class="quick-action" onclick="showCreateRequestModal()">
                            <span class="quick-action-icon">🔍</span>
                            <div class="quick-action-title">Find Team</div>
                            <div class="quick-action-desc">Search for existing teams</div>
                        </a>
                        <a href="#" class="quick-action" onclick="showCreateTeamModal()">
                            <span class="quick-action-icon">👥</span>
                            <div class="quick-action-title">Create Team</div>
                            <div class="quick-action-desc">Form a new team</div>
                        </a>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Filters</h4>
                    <div class="filter-group">
                        <label for="game-filter">Game</label>
                        <select id="game-filter" onchange="filterTeams()">
                            <option value="">All Games</option>
                            <option value="730">Counter-Strike 2</option>
                            <option value="570">Dota 2</option>
                            <option value="230410">Warframe</option>
                            <option value="1172470">Apex Legends</option>
                            <option value="252490">Rust</option>
                            <option value="578080">PUBG</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="skill-filter">Skill Level</label>
                        <select id="skill-filter" onchange="filterTeams()">
                            <option value="">Any Skill</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="region-filter">Region</label>
                        <select id="region-filter" onchange="filterTeams()">
                            <option value="">Any Region</option>
                            <option value="na_east">NA East</option>
                            <option value="na_west">NA West</option>
                            <option value="eu_west">EU West</option>
                            <option value="eu_east">EU East</option>
                            <option value="asia">Asia</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="activity-filter">Activity Time</label>
                        <select id="activity-filter" onchange="filterTeams()">
                            <option value="">Any Time</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="evening">Evening</option>
                            <option value="late_night">Late Night</option>
                        </select>
                    </div>
                </div>

                @if(auth()->user()->activeMatchmakingRequests()->exists())
                <div class="filter-section">
                    <h4>Your Status</h4>
                    <div class="status-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #efeff1; font-weight: 600;">Active Request</span>
                            <span class="status-indicator status-active">Live</span>
                        </div>
                        @foreach(auth()->user()->activeMatchmakingRequests as $request)
                            <div style="margin-bottom: 12px;">
                                <div style="color: #b3b3b5; font-size: 14px;">{{ $request->game_name ?? 'Unknown Game' }}</div>
                                <div style="color: #efeff1; font-size: 12px;">
                                    {{ ucfirst($request->skill_level ?? 'any') }}
                                    @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                        • {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                    @endif
                                </div>
                                <div style="margin-top: 8px;">
                                    <button onclick="cancelRequest({{ $request->id }})" class="btn btn-danger btn-sm" style="font-size: 12px; padding: 4px 8px;">Cancel Request</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Main Content -->
            <div class="matchmaking-content">
                @if($teams->isEmpty() && $matchmakingRequests->isEmpty())
                <!-- Empty State -->
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 16px;">🎮</div>
                    <h3 style="margin-bottom: 12px; color: #efeff1;">No Active Teams</h3>
                    <p style="color: #b3b3b5; margin-bottom: 24px;">
                        Start your gaming journey by creating a matchmaking request or forming a new team.
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button onclick="showCreateRequestModal()" class="btn btn-primary">Find Teammates</button>
                        <a href="{{ route('teams.create') }}" class="btn btn-secondary">Create Team</a>
                    </div>
                </div>
                @else
                    <!-- Active Matchmaking Requests -->
                    @if($matchmakingRequests->isNotEmpty())
                    <div class="card">
                        <h3 class="card-header">🔍 Looking for Teams</h3>
                        <div style="display: grid; gap: 16px;">
                            @foreach($matchmakingRequests as $request)
                                <div class="request-card">
                                    <div class="request-header">
                                        <div class="request-game">{{ $request->game_name ?? 'Unknown Game' }}</div>
                                        <div class="request-status">
                                            <span class="status-indicator status-searching">Searching</span>
                                        </div>
                                    </div>
                                    <div class="request-details">
                                        <div class="request-detail">
                                            <span class="request-detail-label">Skill Level</span>
                                            <span class="request-detail-value">{{ ucfirst($request->skill_level ?? 'any') }}</span>
                                        </div>
                                        <div class="request-detail">
                                            <span class="request-detail-label">Type</span>
                                            <span class="request-detail-value">{{ ucfirst($request->request_type ? str_replace('_', ' ', $request->request_type) : 'find team') }}</span>
                                        </div>
                                        <div class="request-detail">
                                            <span class="request-detail-label">Role</span>
                                            <span class="request-detail-value">
                                                @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                                    {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                                @else
                                                    Any Role
                                                @endif
                                            </span>
                                        </div>
                                        <div class="request-detail">
                                            <span class="request-detail-label">Created</span>
                                            <span class="request-detail-value">{{ $request->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="findTeams({{ $request->id }})" class="btn btn-primary btn-sm">Find Teams</button>
                                        <button onclick="cancelRequest({{ $request->id }})" class="btn btn-secondary btn-sm">Cancel</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Live Team Recommendations -->
                    <div class="card" id="live-recommendations">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="card-header" style="margin: 0;">⚡ Live Recommendations</h3>
                            <div class="live-indicator">
                                <span>Live</span>
                            </div>
                        </div>
                        <div id="live-recommendations-content">
                            @php
                                // Collect all recommendations across all requests
                                $allRecommendations = [];
                                foreach($recommendations as $requestId => $requestRecs) {
                                    foreach($requestRecs as $rec) {
                                        $rec['request_id'] = $requestId;
                                        $allRecommendations[] = $rec;
                                    }
                                }
                                // Sort by compatibility score (highest first)
                                usort($allRecommendations, function($a, $b) {
                                    return ($b['compatibility_score'] ?? 0) <=> ($a['compatibility_score'] ?? 0);
                                });
                                // Take top 3 overall recommendations
                                $topRecommendations = array_slice($allRecommendations, 0, 3);
                            @endphp

                            @if(empty($topRecommendations))
                                <div style="text-align: center; padding: 40px; color: #b3b3b5;">
                                    <div style="font-size: 24px; margin-bottom: 12px;">🎯</div>
                                    <p>No active matchmaking requests</p>
                                    <p style="font-size: 14px; margin-top: 8px;">Create a matchmaking request to get personalized recommendations</p>
                                    <button onclick="showCreateRequestModal()" class="btn btn-primary" style="margin-top: 16px;">
                                        Find Teammates
                                    </button>
                                </div>
                            @else
                                <div style="display: grid; gap: 16px;">
                                    @foreach($topRecommendations as $recommendation)
                                        @php
                                            $team = $recommendation['team'];
                                            $compatScore = $recommendation['compatibility_score'];
                                            $matchReasons = $recommendation['match_reasons'] ?? [];
                                            $roleNeeds = $recommendation['role_needs'] ?? [];
                                            $breakdown = $recommendation['breakdown'] ?? [];

                                            // Determine compatibility color
                                            if ($compatScore >= 80) {
                                                $compatColor = '#10b981'; // green
                                            } elseif ($compatScore >= 60) {
                                                $compatColor = '#f59e0b'; // yellow
                                            } elseif ($compatScore >= 40) {
                                                $compatColor = '#f97316'; // orange
                                            } else {
                                                $compatColor = '#ef4444'; // red
                                            }
                                        @endphp

                                        <div class="team-card" style="position: relative; overflow: hidden; background: linear-gradient(135deg, #18181b 0%, #27272a 100%);">
                                            <!-- Compatibility indicator bar -->
                                            <div style="position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: {{ $compatColor }};"></div>

                                            <div class="team-header">
                                                <div style="flex: 1;">
                                                    <div class="team-name">{{ $team->name }}</div>
                                                    <div class="team-game">{{ $team->game_name ?? 'Unknown Game' }}</div>
                                                    <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
                                                        <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                                            {{ ucfirst($team->skill_level ?? 'casual') }}
                                                        </span>
                                                        @if(!empty($roleNeeds))
                                                            <span style="background-color: #3f3f46; color: #b3b3b5; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                                                Needs: {{ implode(', ', array_slice($roleNeeds, 0, 2)) }}{{ count($roleNeeds) > 2 ? '...' : '' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="compatibility-score">
                                                    <div style="font-size: 32px; font-weight: 700; color: {{ $compatColor }}; line-height: 1;">
                                                        {{ round($compatScore) }}%
                                                    </div>
                                                    <div class="score-label">Match</div>
                                                </div>
                                            </div>

                                            <!-- Team Members Preview -->
                                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                                <div style="display: flex; margin-right: 8px;">
                                                    @foreach($team->activeMembers->take(3) as $member)
                                                        <img src="{{ $member->user->profile->avatar_url ?? '/images/default-avatar.png' }}"
                                                             alt="{{ $member->user->display_name }}"
                                                             style="width: 28px; height: 28px; border-radius: 50%; margin-left: -4px; border: 2px solid #18181b;"
                                                             title="{{ $member->user->display_name }}">
                                                    @endforeach
                                                </div>
                                                <span style="color: #b3b3b5; font-size: 13px;">
                                                    {{ $team->current_size ?? 0 }}/{{ $team->max_size ?? 5 }} members
                                                </span>
                                            </div>

                                            <!-- Compatibility Breakdown (Compact) -->
                                            @if(!empty($breakdown))
                                                <div style="background-color: #0e0e10; border-radius: 6px; padding: 10px; margin-bottom: 12px;">
                                                    <div style="font-size: 10px; color: #71717a; margin-bottom: 6px; font-weight: 600;">COMPATIBILITY BREAKDOWN</div>
                                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                                                        @if(isset($breakdown['skill']))
                                                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                                                <span style="color: #b3b3b5;">Skill</span>
                                                                <span style="color: {{ $breakdown['skill'] >= 75 ? '#10b981' : ($breakdown['skill'] >= 50 ? '#f59e0b' : '#ef4444') }}; font-weight: 600;">
                                                                    {{ round($breakdown['skill']) }}%
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($breakdown['role']))
                                                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                                                <span style="color: #b3b3b5;">Role Fit</span>
                                                                <span style="color: {{ $breakdown['role'] >= 75 ? '#10b981' : ($breakdown['role'] >= 50 ? '#f59e0b' : '#ef4444') }}; font-weight: 600;">
                                                                    {{ round($breakdown['role']) }}%
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($breakdown['region']))
                                                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                                                <span style="color: #b3b3b5;">Region</span>
                                                                <span style="color: {{ $breakdown['region'] >= 75 ? '#10b981' : ($breakdown['region'] >= 50 ? '#f59e0b' : '#ef4444') }}; font-weight: 600;">
                                                                    {{ round($breakdown['region']) }}%
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($breakdown['activity']))
                                                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                                                <span style="color: #b3b3b5;">Activity</span>
                                                                <span style="color: {{ $breakdown['activity'] >= 75 ? '#10b981' : ($breakdown['activity'] >= 50 ? '#f59e0b' : '#ef4444') }}; font-weight: 600;">
                                                                    {{ round($breakdown['activity']) }}%
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Match Reasons -->
                                            @if(!empty($matchReasons))
                                                <div style="margin-bottom: 12px; padding: 10px; border-left: 3px solid {{ $compatColor }}; background-color: rgba(102, 126, 234, 0.05);">
                                                    <div style="font-size: 10px; color: #71717a; margin-bottom: 4px; font-weight: 600;">WHY IT'S A GOOD MATCH</div>
                                                    <div style="font-size: 12px; color: #d4d4d8; line-height: 1.5;">
                                                        {{ implode(' • ', array_slice($matchReasons, 0, 2)) }}
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Why This Team? Expandable Section -->
                                            <div style="margin-bottom: 12px;">
                                                <button
                                                    onclick="toggleWhyThisTeam({{ $team->id }})"
                                                    style="width: 100%; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; padding: 10px 12px; color: #efeff1; font-size: 12px; font-weight: 600; text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;"
                                                    onmouseover="this.style.borderColor='#667eea'; this.style.backgroundColor='#18181b';"
                                                    onmouseout="this.style.borderColor='#3f3f46'; this.style.backgroundColor='#0e0e10';">
                                                    <span>Why this team?</span>
                                                    <span id="toggle-icon-{{ $team->id }}">▼</span>
                                                </button>

                                                <div id="why-this-team-{{ $team->id }}" style="display: none; margin-top: 8px; padding: 12px; background-color: #0e0e10; border-radius: 6px; border: 1px solid #3f3f46;">
                                                    <div style="font-size: 11px; color: #71717a; margin-bottom: 10px; font-weight: 600; text-transform: uppercase;">Detailed Compatibility Breakdown</div>

                                                    @if(!empty($breakdown))
                                                        <!-- Skill Match -->
                                                        @if(isset($breakdown['skill']))
                                                            @php
                                                                $skillScore = $breakdown['skill'];
                                                                $skillColor = $skillScore >= 75 ? '#10b981' : ($skillScore >= 50 ? '#f59e0b' : '#ef4444');
                                                            @endphp
                                                            <div style="margin-bottom: 12px;">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                                    <span style="font-size: 12px; color: #b3b3b5; font-weight: 500;">Skill Match</span>
                                                                    <span style="font-size: 12px; color: {{ $skillColor }}; font-weight: 700;">{{ round($skillScore) }}%</span>
                                                                </div>
                                                                <div style="width: 100%; height: 6px; background-color: #27272a; border-radius: 3px; overflow: hidden;">
                                                                    <div style="width: {{ $skillScore }}%; height: 100%; background-color: {{ $skillColor }}; transition: width 0.3s ease;"></div>
                                                                </div>
                                                                <div style="font-size: 10px; color: #71717a; margin-top: 4px; line-height: 1.4;">
                                                                    Your skill level aligns well with the team's average player skill.
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <!-- Role Fit -->
                                                        @if(isset($breakdown['role']))
                                                            @php
                                                                $roleScore = $breakdown['role'];
                                                                $roleColor = $roleScore >= 75 ? '#10b981' : ($roleScore >= 50 ? '#f59e0b' : '#ef4444');
                                                            @endphp
                                                            <div style="margin-bottom: 12px;">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                                    <span style="font-size: 12px; color: #b3b3b5; font-weight: 500;">Role Fit</span>
                                                                    <span style="font-size: 12px; color: {{ $roleColor }}; font-weight: 700;">{{ round($roleScore) }}%</span>
                                                                </div>
                                                                <div style="width: 100%; height: 6px; background-color: #27272a; border-radius: 3px; overflow: hidden;">
                                                                    <div style="width: {{ $roleScore }}%; height: 100%; background-color: {{ $roleColor }}; transition: width 0.3s ease;"></div>
                                                                </div>
                                                                <div style="font-size: 10px; color: #71717a; margin-top: 4px; line-height: 1.4;">
                                                                    @if(!empty($roleNeeds))
                                                                        Team needs: {{ implode(', ', $roleNeeds) }}. Your preferred roles match their requirements.
                                                                    @else
                                                                        Your preferred roles align with the team's needs.
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <!-- Region Match -->
                                                        @if(isset($breakdown['region']))
                                                            @php
                                                                $regionScore = $breakdown['region'];
                                                                $regionColor = $regionScore >= 75 ? '#10b981' : ($regionScore >= 50 ? '#f59e0b' : '#ef4444');
                                                            @endphp
                                                            <div style="margin-bottom: 12px;">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                                    <span style="font-size: 12px; color: #b3b3b5; font-weight: 500;">Region Match</span>
                                                                    <span style="font-size: 12px; color: {{ $regionColor }}; font-weight: 700;">{{ round($regionScore) }}%</span>
                                                                </div>
                                                                <div style="width: 100%; height: 6px; background-color: #27272a; border-radius: 3px; overflow: hidden;">
                                                                    <div style="width: {{ $regionScore }}%; height: 100%; background-color: {{ $regionColor }}; transition: width 0.3s ease;"></div>
                                                                </div>
                                                                <div style="font-size: 10px; color: #71717a; margin-top: 4px; line-height: 1.4;">
                                                                    Good regional match means lower latency and better play times together.
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <!-- Activity Time Match -->
                                                        @if(isset($breakdown['activity']))
                                                            @php
                                                                $activityScore = $breakdown['activity'];
                                                                $activityColor = $activityScore >= 75 ? '#10b981' : ($activityScore >= 50 ? '#f59e0b' : '#ef4444');
                                                            @endphp
                                                            <div style="margin-bottom: 12px;">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                                    <span style="font-size: 12px; color: #b3b3b5; font-weight: 500;">Activity Time Match</span>
                                                                    <span style="font-size: 12px; color: {{ $activityColor }}; font-weight: 700;">{{ round($activityScore) }}%</span>
                                                                </div>
                                                                <div style="width: 100%; height: 6px; background-color: #27272a; border-radius: 3px; overflow: hidden;">
                                                                    <div style="width: {{ $activityScore }}%; height: 100%; background-color: {{ $activityColor }}; transition: width 0.3s ease;"></div>
                                                                </div>
                                                                <div style="font-size: 10px; color: #71717a; margin-top: 4px; line-height: 1.4;">
                                                                    Your active hours align with when this team typically plays together.
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <!-- Team Balance (if available) -->
                                                        @if(isset($breakdown['balance']))
                                                            @php
                                                                $balanceScore = $breakdown['balance'];
                                                                $balanceColor = $balanceScore >= 75 ? '#10b981' : ($balanceScore >= 50 ? '#f59e0b' : '#ef4444');
                                                            @endphp
                                                            <div style="margin-bottom: 0;">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                                    <span style="font-size: 12px; color: #b3b3b5; font-weight: 500;">Team Balance</span>
                                                                    <span style="font-size: 12px; color: {{ $balanceColor }}; font-weight: 700;">{{ round($balanceScore) }}%</span>
                                                                </div>
                                                                <div style="width: 100%; height: 6px; background-color: #27272a; border-radius: 3px; overflow: hidden;">
                                                                    <div style="width: {{ $balanceScore }}%; height: 100%; background-color: {{ $balanceColor }}; transition: width 0.3s ease;"></div>
                                                                </div>
                                                                <div style="font-size: 10px; color: #71717a; margin-top: 4px; line-height: 1.4;">
                                                                    Adding you would improve the team's overall role and skill balance.
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div style="text-align: center; padding: 20px; color: #71717a; font-size: 12px;">
                                                            No detailed breakdown available for this team.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="team-actions">
                                                <a href="{{ route('teams.show', $team) }}" class="btn btn-secondary btn-sm" style="flex: 1;">
                                                    View Team
                                                </a>
                                                <button onclick="requestToJoin({{ $team->id }})" class="btn btn-primary btn-sm" style="flex: 1;">
                                                    Request to Join
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Available Teams -->
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 class="card-header" style="margin: 0;">👥 Available Teams</h3>
                            <div style="color: #b3b3b5; font-size: 14px;">{{ $teams->count() }} teams recruiting</div>
                        </div>
                        <div id="teams-grid" style="display: grid; gap: 20px;">
                            @foreach($teams as $team)
                                <div class="team-card" 
                                     data-team-id="{{ $team->id }}"
                                     data-server-id="{{ $team->server_id }}"
                                     data-game="{{ $team->game_appid }}" 
                                     data-skill="{{ $team->skill_level }}" 
                                     data-region="{{ $team->preferred_region }}" 
                                     data-activity="{{ $team->activity_time }}"
                                     data-status="{{ $team->status }}">
                                    <div class="team-header">
                                        <div>
                                            <div class="team-name">{{ $team->name }}</div>
                                            <div class="team-game">{{ $team->game_name ?? 'Unknown Game' }}</div>
                                        </div>
                                        <div class="compatibility-score">
                                            <div class="score-value">{{ $team->compatibility_score ?? '85' }}%</div>
                                            <div class="score-label">Match</div>
                                        </div>
                                    </div>

                                    <div class="team-members">
                                        @foreach($team->activeMembers->take(5) as $member)
                                            <div class="member-slot filled">
                                                <img src="{{ $member->user->profile->avatar_url }}" alt="{{ $member->user->display_name }}" class="member-avatar">
                                                <div class="member-name">{{ $member->user->display_name }}</div>
                                                @if($member->game_role)
                                                    <div class="member-role">{{ ucfirst(str_replace('_', ' ', $member->game_role)) }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                        @for($i = $team->activeMembers->count(); $i < $team->max_size; $i++)
                                            <div class="member-slot empty">
                                                <div class="member-placeholder">+</div>
                                                <div class="member-name">Open</div>
                                            </div>
                                        @endfor
                                    </div>

                                    @if($team->tags && $team->tags->isNotEmpty())
                                        <div class="team-tags">
                                            @foreach($team->tags->take(3) as $tag)
                                                <span class="team-tag">{{ ucfirst(str_replace('_', ' ', $tag)) }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="team-actions">
                                        <a href="{{ route('teams.show', $team) }}" class="btn btn-secondary btn-sm">View Team</a>
                                        @if($team->status === 'recruiting' && !$team->activeMembers->contains('user_id', auth()->id()))
                                            <button onclick="requestToJoin({{ $team->id }})" class="btn btn-primary btn-sm">Request to Join</button>
                                        @elseif($team->activeMembers->contains('user_id', auth()->id()))
                                            <span class="status-indicator status-in-team">Member</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

<!-- Create Request Modal -->
<div id="createRequestModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background-color: #18181b; border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-bottom: 24px; color: #efeff1;">Create Matchmaking Request</h3>
        <form id="createRequestForm" action="{{ route('matchmaking.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="game_appid">Game *</label>
                <select id="game_appid" name="game_appid" required onchange="updateGameName()">
                    <option value="">Select a game...</option>
                    <option value="730" data-name="Counter-Strike 2">Counter-Strike 2</option>
                    <option value="570" data-name="Dota 2">Dota 2</option>
                    <option value="230410" data-name="Warframe">Warframe</option>
                    <option value="1172470" data-name="Apex Legends">Apex Legends</option>
                    <option value="252490" data-name="Rust">Rust</option>
                    <option value="578080" data-name="PUBG">PUBG</option>
                </select>
                <input type="hidden" id="game_name" name="game_name" value="">
            </div>
            <div class="form-group">
                <label for="request_type">Request Type *</label>
                <select id="request_type" name="request_type" required>
                    <option value="">Select request type...</option>
                    <option value="find_team">Find Team to Join</option>
                    <option value="find_teammates">Find Teammates</option>
                    <option value="substitute">Substitute Player</option>
                </select>
            </div>
            <div class="form-group">
                <label for="skill_level">Skill Level</label>
                <select id="skill_level" name="skill_level">
                    <option value="any">Any Skill</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                    <option value="expert">Expert</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_roles">Preferred Roles</label>
                <select id="preferred_roles" name="preferred_roles[]" multiple>
                    <option value="entry_fragger">Entry Fragger</option>
                    <option value="support">Support</option>
                    <option value="awper">AWPer</option>
                    <option value="igl">In-Game Leader</option>
                    <option value="lurker">Lurker</option>
                    <option value="carry">Carry</option>
                    <option value="mid">Mid</option>
                    <option value="offlaner">Offlaner</option>
                    <option value="jungler">Jungler</option>
                </select>
                <small style="color: #b3b3b5;">Hold Ctrl/Cmd to select multiple roles</small>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="normal">Normal</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="message">Message (Optional)</label>
                <textarea id="message" name="message" rows="3" placeholder="Add any additional details about your request..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Request</button>
                <button type="button" onclick="hideCreateRequestModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Find Teams Results Modal -->
<div id="findTeamsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background-color: #18181b; border-radius: 12px; padding: 32px; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; color: #efeff1;">🎯 Compatible Teams Found</h3>
            <button onclick="hideFindTeamsModal()" style="background: none; border: none; color: #71717a; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#efeff1'" onmouseout="this.style.color='#71717a'">×</button>
        </div>

        <div id="findTeamsResults" style="display: grid; gap: 16px;">
            <!-- Results will be populated here -->
        </div>
    </div>
</div>

<script>
// Modal functions
function showCreateRequestModal() {
    document.getElementById('createRequestModal').style.display = 'flex';
}

function hideCreateRequestModal() {
    document.getElementById('createRequestModal').style.display = 'none';
}

function updateGameName() {
    const gameSelect = document.getElementById('game_appid');
    const gameNameInput = document.getElementById('game_name');
    const selectedOption = gameSelect.options[gameSelect.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.name) {
        gameNameInput.value = selectedOption.dataset.name;
    } else {
        gameNameInput.value = '';
    }
}

function showCreateTeamModal() {
    window.location.href = '{{ route('teams.create') }}';
}

// Filter functions
function filterTeams() {
    const gameFilter = document.getElementById('game-filter').value;
    const skillFilter = document.getElementById('skill-filter').value;
    const regionFilter = document.getElementById('region-filter').value;
    const activityFilter = document.getElementById('activity-filter').value;
    
    const teamCards = document.querySelectorAll('#teams-grid .team-card');
    
    teamCards.forEach(card => {
        let showCard = true;
        
        if (gameFilter && card.dataset.game !== gameFilter) {
            showCard = false;
        }
        if (skillFilter && card.dataset.skill !== skillFilter) {
            showCard = false;
        }
        if (regionFilter && card.dataset.region !== regionFilter) {
            showCard = false;
        }
        if (activityFilter && card.dataset.activity !== activityFilter) {
            showCard = false;
        }
        
        card.style.display = showCard ? 'block' : 'none';
    });
}

// Action functions
function requestToJoin(teamId) {
    if (confirm('Request to join this team?')) {
        fetch(`{{ url('/teams') }}/${teamId}/join`, {
            method: 'POST',
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
                alert(data.message || 'Error requesting to join team');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error requesting to join team');
        });
    }
}

function cancelRequest(requestId) {
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

function findTeams(requestId) {
    // Show loading state in modal
    showFindTeamsLoading();

    fetch(`{{ url('/matchmaking/find-teams') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ request_id: requestId })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.error || errorData.message || 'Failed to find teams');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.teams && data.teams.length > 0) {
            displayFindTeamsResults(data.teams);
        } else {
            showNoTeamsFound();
        }
    })
    .catch(error => {
        console.error('Error finding teams:', error);
        showFindTeamsError(error.message);
    });
}

// Find Teams Modal Helper Functions
function showFindTeamsLoading() {
    const modal = document.getElementById('findTeamsModal');
    const results = document.getElementById('findTeamsResults');

    results.innerHTML = `
        <div style="text-align: center; padding: 60px 40px; color: #b3b3b5;">
            <div style="width: 48px; height: 48px; border: 4px solid #3f3f46; border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
            <p style="font-size: 16px; margin: 0;">Searching for compatible teams...</p>
            <p style="font-size: 14px; color: #71717a; margin-top: 8px;">This may take a moment</p>
        </div>
    `;

    modal.style.display = 'flex';
}

function displayFindTeamsResults(teams) {
    const results = document.getElementById('findTeamsResults');

    let html = `<div style="margin-bottom: 16px; color: #10b981; font-size: 14px; font-weight: 600;">✓ Found ${teams.length} compatible team${teams.length !== 1 ? 's' : ''}</div>`;

    teams.forEach(team => {
        // Backend returns flat structure: { id, name, game_name, compatibility_score, match_reasons, role_needs, ... }
        const compatibility = team.compatibility_score || 0;
        const matchReasons = team.match_reasons || [];
        const roleNeeds = team.role_needs || [];

        // Determine compatibility color
        let compatColor = '#ef4444'; // red
        if (compatibility >= 80) compatColor = '#10b981'; // green
        else if (compatibility >= 60) compatColor = '#f59e0b'; // yellow
        else if (compatibility >= 40) compatColor = '#f97316'; // orange

        html += `
            <div class="team-card" style="padding: 20px; background-color: #0e0e10; border-radius: 8px; border: 1px solid #3f3f46; position: relative; overflow: hidden;">
                <!-- Compatibility indicator bar -->
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: ${compatColor};"></div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-left: 12px;">
                    <div style="flex: 1;">
                        <div style="font-size: 18px; font-weight: 600; color: #efeff1; margin-bottom: 4px;">
                            ${team.name}
                        </div>
                        <div style="font-size: 14px; color: #b3b3b5;">
                            ${team.game_name || 'Unknown Game'}
                        </div>
                        <div style="display: flex; gap: 6px; margin-top: 8px;">
                            <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                ${team.skill_level ? team.skill_level.charAt(0).toUpperCase() + team.skill_level.slice(1) : 'Casual'}
                            </span>
                            <span style="background-color: #3f3f46; color: #b3b3b5; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                ${team.current_size || 0}/${team.max_size || 5} Members
                            </span>
                            ${roleNeeds.length > 0 ? `
                                <span style="background-color: rgba(102, 126, 234, 0.2); color: #667eea; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                    Needs: ${roleNeeds.slice(0, 2).join(', ')}${roleNeeds.length > 2 ? '...' : ''}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 32px; font-weight: 700; color: ${compatColor}; line-height: 1;">
                            ${Math.round(compatibility)}%
                        </div>
                        <div style="font-size: 12px; color: #b3b3b5; text-transform: uppercase; margin-top: 4px;">Match</div>
                    </div>
                </div>

                ${matchReasons.length > 0 ? `
                    <div style="margin-bottom: 12px; padding: 10px; border-left: 3px solid ${compatColor}; background-color: rgba(102, 126, 234, 0.05);">
                        <div style="font-size: 10px; color: #71717a; margin-bottom: 4px; font-weight: 600;">WHY IT'S A GOOD MATCH</div>
                        <div style="font-size: 12px; color: #d4d4d8; line-height: 1.5;">
                            ${matchReasons.slice(0, 3).join(' • ')}
                        </div>
                    </div>
                ` : ''}

                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <a href="/teams/${team.id}" class="btn btn-secondary btn-sm" style="flex: 1;">View Team</a>
                    <button onclick="requestToJoinFromModal(${team.id})" class="btn btn-primary btn-sm" style="flex: 1;">Request to Join</button>
                </div>
            </div>
        `;
    });

    document.getElementById('findTeamsResults').innerHTML = html;
}

function showNoTeamsFound() {
    const results = document.getElementById('findTeamsResults');
    results.innerHTML = `
        <div style="text-align: center; padding: 60px 40px; color: #b3b3b5;">
            <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;">🔍</div>
            <p style="font-size: 18px; color: #efeff1; margin-bottom: 8px; font-weight: 600;">No compatible teams found</p>
            <p style="font-size: 14px; margin-bottom: 24px;">Try adjusting your matchmaking preferences or create your own team.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="hideFindTeamsModal()" class="btn btn-secondary">Close</button>
                <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
            </div>
        </div>
    `;
}

function showFindTeamsError(errorMessage) {
    const results = document.getElementById('findTeamsResults');
    results.innerHTML = `
        <div style="text-align: center; padding: 60px 40px;">
            <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
            <p style="font-size: 18px; color: #ef4444; margin-bottom: 8px; font-weight: 600;">Error Finding Teams</p>
            <p style="font-size: 14px; color: #b3b3b5; margin-bottom: 24px;">${errorMessage || 'An unexpected error occurred. Please try again.'}</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="hideFindTeamsModal()" class="btn btn-secondary">Close</button>
                <button onclick="location.reload()" class="btn btn-primary">Retry</button>
            </div>
        </div>
    `;
}

function hideFindTeamsModal() {
    document.getElementById('findTeamsModal').style.display = 'none';
}

function requestToJoinFromModal(teamId) {
    // Close the modal first
    hideFindTeamsModal();
    // Call the existing requestToJoin function
    requestToJoin(teamId);
}

// Close modal when clicking background
document.getElementById('findTeamsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideFindTeamsModal();
    }
});

// Add spin animation for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Close modal when clicking outside
document.getElementById('createRequestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCreateRequestModal();
    }
});

// Form submission
document.getElementById('createRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(errorData.error || errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            hideCreateRequestModal();
            alert('Matchmaking request created successfully! 🎮');
            location.reload();
        } else {
            alert(data.error || data.message || 'Error creating request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating request: ' + error.message);
    });
});

// Toggle "Why This Team?" expandable section
function toggleWhyThisTeam(teamId) {
    const section = document.getElementById('why-this-team-' + teamId);
    const icon = document.getElementById('toggle-icon-' + teamId);

    if (section.style.display === 'none') {
        section.style.display = 'block';
        icon.textContent = '▲';
    } else {
        section.style.display = 'none';
        icon.textContent = '▼';
    }
}
</script>
@endsection