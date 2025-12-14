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
        background-color: var(--color-surface);
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
        color: var(--color-text-primary);
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
        color: var(--color-text-secondary);
        margin-bottom: 6px;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 8px 12px;
        background-color: var(--color-input-bg);
        border: 1px solid var(--color-input-border);
        border-radius: 6px;
        color: var(--color-input-text);
        font-size: 14px;
    }
    
    .status-card {
        background-color: var(--color-surface);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--color-border-primary);
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
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--card-border);
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
        color: var(--color-text-primary);
        margin-bottom: 4px;
    }

    .team-game {
        font-size: 14px;
        color: var(--color-text-secondary);
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
        color: var(--color-text-secondary);
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
        background-color: var(--color-bg-primary);
        border-radius: 8px;
        border: 1px solid var(--color-border-primary);
    }

    .member-slot.filled {
        border-color: #10b981;
    }

    .member-slot.empty {
        border: 2px dashed var(--color-border-primary);
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
        background-color: var(--color-surface-active);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text-muted);
        font-size: 18px;
    }

    .member-name {
        font-size: 12px;
        color: var(--color-text-secondary);
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
        background-color: var(--color-surface-active);
        color: var(--color-text-secondary);
        padding: 4px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    
    .team-actions {
        display: flex;
        gap: 8px;
    }
    
    .card {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid var(--card-border);
        position: relative;
        overflow: hidden;
        width: 100%;
        box-sizing: border-box;
    }

    .card-header {
        color: var(--color-text-primary);
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .request-card {
        background-color: var(--color-surface);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--color-border-primary);
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
        color: var(--color-text-primary);
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
        color: var(--color-text-muted);
        text-transform: uppercase;
    }

    .request-detail-value {
        font-size: 14px;
        color: var(--color-text-primary);
        font-weight: 500;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .quick-action {
        background-color: var(--color-surface);
        border: 1px solid var(--color-border-primary);
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        text-decoration: none;
        color: var(--color-text-primary);
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
        color: var(--color-text-secondary);
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
<x-navbar active-section="matchmaking" />

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
                            <option value="548430">Deep Rock Galactic</option>
                            <option value="493520">GTFO</option>
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
                                    <p>No compatible teams found</p>
                                    <p style="font-size: 14px; margin-top: 8px;">Try adjusting your criteria or check back later for new teams</p>
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
                                            $breakdown = $recommendation['breakdown'] ?? [];
                                        @endphp

                                        <x-team-card
                                            :team="$team"
                                            :showCompatibility="true"
                                            :compatibilityScore="$compatScore"
                                            :compatibilityDetails="$breakdown"
                                            context="matchmaking"
                                        />
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
                                <div data-team-id="{{ $team->id }}"
                                     data-server-id="{{ $team->server_id }}"
                                     data-game="{{ $team->game_appid }}"
                                     data-skill="{{ $team->skill_level }}"
                                     data-region="{{ $team->preferred_region }}"
                                     data-activity="{{ $team->activity_time }}"
                                     data-status="{{ $team->status }}">
                                    <x-team-card
                                        :team="$team"
                                        context="browse"
                                    />
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
    <div style="background-color: var(--color-surface); border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-bottom: 24px; color: var(--color-text-primary);">Create Matchmaking Request</h3>
        <form id="createRequestForm" action="{{ route('matchmaking.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="game_appid">Game *</label>
                <select id="game_appid" name="game_appid" required onchange="updateGameName(); loadSkillPreview(this.value); updateRoleOptions(this.value);">
                    <option value="">Select a game...</option>
                    <option value="730" data-name="Counter-Strike 2">Counter-Strike 2</option>
                    <option value="570" data-name="Dota 2">Dota 2</option>
                    <option value="230410" data-name="Warframe">Warframe</option>
                    <option value="548430" data-name="Deep Rock Galactic">Deep Rock Galactic</option>
                    <option value="493520" data-name="GTFO">GTFO</option>
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
                <label>Your Skill Level</label>
                <div id="skill_display_container">
                    <div id="skill_display" style="color: var(--color-text-muted); font-size: 14px; padding: 10px 0;">
                        Select a game to see your skill level
                    </div>
                </div>
                <input type="hidden" id="skill_level" name="skill_level" value="unranked">
                <small style="color: var(--color-text-muted); font-size: 12px; margin-top: 4px; display: block;">
                    Calculated automatically from your Steam stats
                </small>
            </div>
            <div class="form-group">
                <label for="preferred_roles">Preferred Roles</label>
                <div id="role_selection_container">
                    <p style="color: var(--color-text-muted); font-size: 14px; padding: 10px 0; margin: 0;">Select a game to see available roles</p>
                </div>
                <small style="color: var(--color-text-secondary);">Hold Ctrl/Cmd to select multiple roles</small>
            </div>
            <div class="form-group">
                <label for="preferred_regions">Preferred Regions</label>
                <select id="preferred_regions" name="preferred_regions[]" multiple>
                    <option value="NA">North America</option>
                    <option value="EU">Europe</option>
                    <option value="ASIA">Asia</option>
                    <option value="SA">South America</option>
                    <option value="OCEANIA">Oceania</option>
                    <option value="AFRICA">Africa</option>
                    <option value="MIDDLE_EAST">Middle East</option>
                </select>
                <small style="color: var(--color-text-secondary);">Select your preferred gaming regions</small>
            </div>
            <div class="form-group">
                <label for="availability_hours">When Can You Play?</label>
                <select id="availability_hours" name="availability_hours[]" multiple>
                    <option value="morning">Morning (6AM-12PM)</option>
                    <option value="afternoon">Afternoon (12PM-6PM)</option>
                    <option value="evening">Evening (6PM-12AM)</option>
                    <option value="night">Night (12AM-6AM)</option>
                    <option value="flexible">Flexible Schedule</option>
                </select>
                <small style="color: var(--color-text-secondary);">Select your typical gaming hours</small>
            </div>
            <div class="form-group">
                <label for="languages">Languages You Speak</label>
                <select id="languages" name="languages[]" multiple>
                    <option value="en" selected>English</option>
                    <option value="es">Spanish</option>
                    <option value="zh">Chinese</option>
                    <option value="fr">French</option>
                    <option value="de">German</option>
                    <option value="pt">Portuguese</option>
                    <option value="ru">Russian</option>
                    <option value="ja">Japanese</option>
                    <option value="ko">Korean</option>
                </select>
                <small style="color: var(--color-text-secondary);">Select languages for team communication</small>
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
    <div style="background-color: var(--color-surface); border-radius: 12px; padding: 32px; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; color: var(--color-text-primary);">🎯 Compatible Teams Found</h3>
            <button onclick="hideFindTeamsModal()" style="background: none; border: none; color: var(--color-text-muted); font-size: 24px; cursor: pointer; padding: 0; line-height: 1; transition: color 0.2s;">×</button>
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

// Game-specific roles configuration
const GAME_ROLES = {
    '730': [ // Counter-Strike 2
        { value: 'entry_fragger', label: 'Entry Fragger' },
        { value: 'awper', label: 'AWPer' },
        { value: 'igl', label: 'In-Game Leader' },
        { value: 'lurker', label: 'Lurker' },
        { value: 'support', label: 'Support' },
        { value: 'anchor', label: 'Anchor' }
    ],
    '570': [ // Dota 2
        { value: 'carry', label: 'Carry (Pos 1)' },
        { value: 'mid', label: 'Mid (Pos 2)' },
        { value: 'offlaner', label: 'Offlaner (Pos 3)' },
        { value: 'soft_support', label: 'Soft Support (Pos 4)' },
        { value: 'hard_support', label: 'Hard Support (Pos 5)' }
    ],
    '230410': [ // Warframe
        { value: 'dps', label: 'DPS' },
        { value: 'tank', label: 'Tank' },
        { value: 'support', label: 'Support' },
        { value: 'crowd_control', label: 'Crowd Control' }
    ],
    '548430': [ // Deep Rock Galactic
        { value: 'scout', label: 'Scout' },
        { value: 'driller', label: 'Driller' },
        { value: 'engineer', label: 'Engineer' },
        { value: 'gunner', label: 'Gunner' }
    ],
    '493520': [ // GTFO
        { value: 'scout', label: 'Scout' },
        { value: 'cqc', label: 'CQC (Close Quarters)' },
        { value: 'sniper', label: 'Sniper' },
        { value: 'support', label: 'Support' }
    ]
};

// Update role options based on selected game
function updateRoleOptions(gameAppId) {
    const container = document.getElementById('role_selection_container');
    if (!container) return;

    const roles = GAME_ROLES[gameAppId];

    if (!roles) {
        container.innerHTML = '<p style="color: var(--color-text-muted); font-size: 14px; padding: 10px 0; margin: 0;">Select a game to see available roles</p>';
        return;
    }

    let html = '<select id="preferred_roles" name="preferred_roles[]" multiple>';
    roles.forEach(role => {
        html += `<option value="${role.value}">${role.label}</option>`;
    });
    html += '</select>';
    container.innerHTML = html;
}

// Auto-skill calculation - Load skill preview when game is selected
async function loadSkillPreview(gameAppId) {
    const skillDisplay = document.getElementById('skill_display');
    const skillLevelInput = document.getElementById('skill_level');

    if (!skillDisplay) return;

    if (!gameAppId) {
        skillDisplay.innerHTML = `
            <div style="color: #71717a; font-size: 14px; padding: 10px 0;">
                Select a game to see your skill level
            </div>
        `;
        if (skillLevelInput) skillLevelInput.value = 'unranked';
        return;
    }

    // Show loading state
    skillDisplay.innerHTML = `
        <div style="color: #71717a; font-size: 14px; padding: 10px 0;">
            <span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Loading your skill level...
        </div>
    `;

    try {
        const response = await fetch(`/matchmaking/skill-preview?game_appid=${gameAppId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to fetch skill data');
        }

        const data = await response.json();
        updateSkillDisplay(data);

    } catch (error) {
        console.error('Error fetching skill:', error);
        skillDisplay.innerHTML = `
            <div style="color: #ef4444; font-size: 14px; padding: 10px 0;">
                ❌ Could not load skill level. Please try again.
            </div>
        `;
        if (skillLevelInput) skillLevelInput.value = 'unranked';
    }
}

function updateSkillDisplay(data) {
    const skillDisplay = document.getElementById('skill_display');
    const skillLevelInput = document.getElementById('skill_level');
    if (!skillDisplay) return;

    const { skill_level, skill_score, breakdown, is_unranked } = data;

    // Update hidden input for form submission
    if (skillLevelInput) {
        skillLevelInput.value = skill_level || 'unranked';
    }

    const levelColors = {
        'expert': '#10b981',
        'advanced': '#667eea',
        'intermediate': '#f59e0b',
        'beginner': '#71717a',
        'unranked': '#9ca3af',
    };

    const levelIcons = {
        'expert': '⭐',
        'advanced': '🎯',
        'intermediate': '📊',
        'beginner': '🎮',
        'unranked': '❓',
    };

    const color = levelColors[skill_level] || '#71717a';
    const icon = levelIcons[skill_level] || '❓';

    let breakdownHtml = '';
    if (!is_unranked && breakdown) {
        breakdownHtml = `<div style="font-weight: 600; margin-bottom: 12px; color: #efeff1;">Skill Breakdown</div>`;
        for (const [key, value] of Object.entries(breakdown)) {
            // Skip 'note' (handled separately) and 'weights' (rendered as a special section)
            if (key !== 'note' && key !== 'weights') {
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                let displayValue = value;

                if (key.includes('hours')) {
                    displayValue = `${parseFloat(value).toFixed(1)} hrs`;
                } else if (key.includes('ratio')) {
                    displayValue = parseFloat(value).toFixed(2);
                } else if (!isNaN(value)) {
                    displayValue = `${parseFloat(value).toFixed(1)}%`;
                }

                breakdownHtml += `
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #27272a; font-size: 13px;">
                        <span style="color: #a1a1aa;">${label}</span>
                        <span style="color: #efeff1; font-weight: 500;">${displayValue}</span>
                    </div>
                `;
            }
        }

        // Render weights as a formatted section if present
        if (breakdown.weights && typeof breakdown.weights === 'object') {
            const weightsFormatted = Object.entries(breakdown.weights)
                .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${(v * 100).toFixed(0)}%`)
                .join(', ');
            breakdownHtml += `
                <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #27272a; font-size: 13px;">
                    <span style="color: #a1a1aa;">Weights</span>
                    <span style="color: #efeff1; font-weight: 500; font-size: 11px; text-align: right; max-width: 180px;">${weightsFormatted}</span>
                </div>
            `;
        }

        if (breakdown && breakdown.note) {
            breakdownHtml += `
                <div style="margin-top: 10px; padding: 8px; background: #0e0e10; border-radius: 4px; font-size: 12px; color: #71717a;">
                    ℹ️ ${breakdown.note}
                </div>
            `;
        }
    } else {
        breakdownHtml = `
            <div style="font-weight: 600; margin-bottom: 8px; color: #efeff1;">Why Unranked?</div>
            <p style="font-size: 13px; color: #a1a1aa; margin: 0 0 8px 0;">
                We couldn't find enough game data.
            </p>
            <ul style="font-size: 12px; color: #71717a; margin: 0; padding-left: 16px;">
                <li>Haven't played this game yet</li>
                <li>Steam profile is private</li>
                <li>Less than 10 hours playtime</li>
            </ul>
        `;
    }

    skillDisplay.innerHTML = `
        <div style="position: relative;">
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                background-color: ${color}20;
                border: 1px solid ${color}40;
                border-radius: 8px;
                cursor: pointer;
            " class="skill-badge" onclick="toggleSkillTooltip()">
                <span style="font-size: 18px;">${icon}</span>
                <div>
                    <div style="font-size: 14px; font-weight: 600; color: ${color}; text-transform: uppercase;">
                        ${is_unranked ? 'Unranked' : skill_level.charAt(0).toUpperCase() + skill_level.slice(1)}
                    </div>
                    ${!is_unranked && skill_score ? `<div style="font-size: 11px; color: #b3b3b5;">Score: ${Math.round(skill_score)}/100</div>` : ''}
                </div>
                <span style="margin-left: 4px; color: #71717a; font-size: 14px;">ℹ️</span>
            </div>

            <div id="skill-tooltip" style="
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                margin-top: 8px;
                background-color: #18181b;
                border: 1px solid #3f3f46;
                border-radius: 8px;
                padding: 16px;
                min-width: 280px;
                z-index: 100;
                box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            ">
                ${breakdownHtml}
            </div>
        </div>
    `;
}

function toggleSkillTooltip() {
    const tooltip = document.getElementById('skill-tooltip');
    if (tooltip) {
        tooltip.style.display = tooltip.style.display === 'none' ? 'block' : 'none';
    }
}

// Close skill tooltip when clicking outside
document.addEventListener('click', function(e) {
    const tooltip = document.getElementById('skill-tooltip');
    const badge = document.querySelector('.skill-badge');
    if (tooltip && badge && !badge.contains(e.target) && !tooltip.contains(e.target)) {
        tooltip.style.display = 'none';
    }
});

function showCreateTeamModal() {
    window.location.href = '{{ route('teams.create') }}';
}

// Filter functions
function filterTeams() {
    const gameFilter = document.getElementById('game-filter').value;
    const skillFilter = document.getElementById('skill-filter').value;
    const regionFilter = document.getElementById('region-filter').value;
    const activityFilter = document.getElementById('activity-filter').value;

    const teamWrappers = document.querySelectorAll('#teams-grid > div[data-team-id]');

    teamWrappers.forEach(wrapper => {
        let showCard = true;

        if (gameFilter && wrapper.dataset.game !== gameFilter) {
            showCard = false;
        }
        if (skillFilter && wrapper.dataset.skill !== skillFilter) {
            showCard = false;
        }
        if (regionFilter && wrapper.dataset.region !== regionFilter) {
            showCard = false;
        }
        if (activityFilter && wrapper.dataset.activity !== activityFilter) {
            showCard = false;
        }

        wrapper.style.display = showCard ? 'block' : 'none';
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
                        <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                            <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                ${team.skill_level ? team.skill_level.charAt(0).toUpperCase() + team.skill_level.slice(1) : 'Casual'}
                            </span>
                            <span style="background-color: #3f3f46; color: #b3b3b5; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                ${team.current_size || 0}/${team.max_size || 5} Members
                            </span>
                            ${team.preferred_region ? `
                                <span style="background-color: #3f3f46; color: #b3b3b5; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">
                                    ${team.preferred_region.replace(/_/g, ' ')}
                                </span>
                            ` : ''}
                            ${team.required_roles && team.required_roles.length > 0 ?
                                team.required_roles.map(role => `
                                    <span style="background-color: rgba(102, 126, 234, 0.2); color: #8b9aef; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(102, 126, 234, 0.3);">
                                        ${role.replace(/_/g, ' ')}
                                    </span>
                                `).join('')
                            : ''}
                            ${team.activity_times && team.activity_times.length > 0 ?
                                team.activity_times.slice(0, 2).map(time => `
                                    <span style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(245, 158, 11, 0.3);">
                                        ${time.replace(/_/g, ' ')}
                                    </span>
                                `).join('')
                            : ''}
                            ${team.activity_times && team.activity_times.length > 2 ? `
                                <span style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    +${team.activity_times.length - 2} more
                                </span>
                            ` : ''}
                            ${team.languages && team.languages.length > 0 ?
                                (() => {
                                    const languageMap = {
                                        'en': 'English', 'es': 'Spanish', 'zh': 'Chinese',
                                        'fr': 'French', 'de': 'German', 'pt': 'Portuguese',
                                        'ru': 'Russian', 'ja': 'Japanese', 'ko': 'Korean'
                                    };
                                    return team.languages.slice(0, 2).map(lang => `
                                        <span style="background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(16, 185, 129, 0.3);">
                                            ${languageMap[lang] || lang}
                                        </span>
                                    `).join('');
                                })()
                            : ''}
                            ${team.languages && team.languages.length > 2 ? `
                                <span style="background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 3px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    +${team.languages.length - 2} more
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