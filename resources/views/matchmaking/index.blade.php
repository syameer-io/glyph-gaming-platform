@extends('layouts.app')

@section('title', 'Matchmaking - Glyph')

@push('styles')
<style>
    .matchmaking-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
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
    
    .request-card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #3f3f46;
        position: relative;
    }
    
    .request-status {
        position: absolute;
        top: 16px;
        right: 16px;
    }
    
    .request-game {
        font-size: 16px;
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 8px;
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
                                <div style="color: #b3b3b5; font-size: 14px;">{{ $request->gameName ?? 'Unknown Game' }}</div>
                                <div style="color: #efeff1; font-size: 12px;">{{ ucfirst($request->preferred_skill_level) }} • {{ ucfirst(str_replace('_', ' ', $request->preferred_region)) }}</div>
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
                                    <div class="request-status">
                                        <span class="status-indicator status-searching">Searching</span>
                                    </div>
                                    <div class="request-game">{{ $request->gameName ?? 'Unknown Game' }}</div>
                                    <div class="request-details">
                                        <div class="request-detail">
                                            <span class="request-detail-label">Skill Level</span>
                                            <span class="request-detail-value">{{ ucfirst($request->preferred_skill_level) }}</span>
                                        </div>
                                        <div class="request-detail">
                                            <span class="request-detail-label">Region</span>
                                            <span class="request-detail-value">{{ ucfirst(str_replace('_', ' ', $request->preferred_region)) }}</span>
                                        </div>
                                        <div class="request-detail">
                                            <span class="request-detail-label">Role</span>
                                            <span class="request-detail-value">{{ ucfirst(str_replace('_', ' ', $request->preferred_role)) }}</span>
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
                            <div style="text-align: center; padding: 40px; color: #b3b3b5;">
                                <div style="font-size: 24px; margin-bottom: 12px;">🎯</div>
                                <p>Looking for compatible teams...</p>
                                <p style="font-size: 14px; margin-top: 8px;">Create a matchmaking request to get personalized recommendations</p>
                            </div>
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
                                            <div class="team-game">{{ $team->gameName ?? 'Unknown Game' }}</div>
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
                <label for="game_appid">Game</label>
                <select id="game_appid" name="game_appid" required>
                    <option value="">Select a game...</option>
                    <option value="730">Counter-Strike 2</option>
                    <option value="570">Dota 2</option>
                    <option value="230410">Warframe</option>
                    <option value="1172470">Apex Legends</option>
                    <option value="252490">Rust</option>
                    <option value="578080">PUBG</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_skill_level">Preferred Skill Level</label>
                <select id="preferred_skill_level" name="preferred_skill_level" required>
                    <option value="">Select skill level...</option>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                    <option value="expert">Expert</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_role">Preferred Role</label>
                <select id="preferred_role" name="preferred_role" required>
                    <option value="">Select role...</option>
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
            </div>
            <div class="form-group">
                <label for="preferred_region">Preferred Region</label>
                <select id="preferred_region" name="preferred_region" required>
                    <option value="">Select region...</option>
                    <option value="na_east">NA East</option>
                    <option value="na_west">NA West</option>
                    <option value="eu_west">EU West</option>
                    <option value="eu_east">EU East</option>
                    <option value="asia">Asia</option>
                    <option value="oceania">Oceania</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_activity_time">Preferred Activity Time</label>
                <select id="preferred_activity_time" name="preferred_activity_time" required>
                    <option value="">Select activity time...</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                    <option value="late_night">Late Night</option>
                    <option value="weekends">Weekends</option>
                    <option value="weekdays">Weekdays</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create Request</button>
                <button type="button" onclick="hideCreateRequestModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
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
    fetch(`{{ url('/matchmaking/find-teams') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ request_id: requestId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Found ${data.teams.length} compatible teams!`);
            // You could show a modal with the teams here
        } else {
            alert(data.message || 'No compatible teams found');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error finding teams');
    });
}

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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCreateRequestModal();
            location.reload();
        } else {
            alert(data.message || 'Error creating request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating request');
    });
});
</script>
@endsection