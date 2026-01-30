@extends('layouts.app')

@section('title', 'Matchmaking - Glyph')

@section('content')
<x-navbar active-section="matchmaking" />

<main>
    <div class="mm-container">
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

        {{-- Page Header --}}
        <div class="page-header">
            <h1 class="page-title">Team Matchmaking</h1>
        </div>

        <div class="mm-main">
            <!-- Sidebar Filters -->
            <aside class="mm-sidebar">
                <div class="mm-filter-section" data-stagger="0">
                    <h4 class="mm-filter-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        Filters
                    </h4>
                    <div class="mm-filter-group">
                        <label for="game-filter">Game</label>
                        <select id="game-filter" onchange="filterTeams()">
                            <option value="">All Games</option>
                            <option value="730">Counter-Strike 2</option>
                            <option value="548430">Deep Rock Galactic</option>
                            <option value="493520">GTFO</option>
                        </select>
                    </div>
                    <div class="mm-filter-group">
                        <label for="skill-filter">Skill Level</label>
                        <select id="skill-filter" onchange="filterTeams()">
                            <option value="">Any Skill</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <div class="mm-filter-group">
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
                    <div class="mm-filter-group">
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
                <div class="mm-filter-section" data-stagger="1">
                    <h4 class="mm-filter-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Your Status
                    </h4>
                    @foreach(auth()->user()->activeMatchmakingRequests as $request)
                        <div class="mm-status-card">
                            <div class="mm-status-header">
                                <span class="mm-status-title">Active Request</span>
                                <span class="mm-status-badge">
                                    <span class="mm-status-dot"></span>
                                    Live
                                </span>
                            </div>
                            <div class="mm-status-game">{{ $request->game_name ?? 'Unknown Game' }}</div>
                            <div class="mm-status-details">
                                {{ ucfirst($request->skill_level ?? 'any') }}
                                @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                    &bull; {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                @endif
                            </div>
                            <button onclick="cancelRequest({{ $request->id }})" class="mm-btn mm-btn-danger" style="width: 100%; font-size: 12px;">
                                Cancel Request
                            </button>
                        </div>
                    @endforeach
                </div>
                @endif
            </aside>

            <!-- Main Content -->
            <div class="mm-content">
                @if($matchmakingRequests->isEmpty())
                <!-- Empty State -->
                <div class="mm-section" data-stagger="0">
                    <div class="mm-empty-state">
                        <div class="mm-empty-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3 class="mm-empty-title">No Active Requests</h3>
                        <p class="mm-empty-text">Start your gaming journey by creating a matchmaking request or forming a new team.</p>
                        <div class="mm-empty-actions">
                            <button onclick="showCreateRequestModal()" class="btn btn-primary">Find Teammates</button>
                            <a href="{{ route('teams.create') }}" class="btn btn-secondary">Create Team</a>
                        </div>
                    </div>
                </div>
                @else
                    <!-- Active Matchmaking Requests -->
                    @if($matchmakingRequests->isNotEmpty())
                    <div class="mm-section" data-stagger="0">
                        <div class="mm-section-header">
                            <h3 class="mm-section-title">
                                <span class="mm-section-title-icon">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                    </svg>
                                </span>
                                Looking for Teams
                            </h3>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            @foreach($matchmakingRequests as $request)
                                <div class="mm-request-card">
                                    <div class="mm-request-header">
                                        <div class="mm-request-game">{{ $request->game_name ?? 'Unknown Game' }}</div>
                                        <span class="mm-request-status">
                                            <span class="mm-status-dot"></span>
                                            Searching
                                        </span>
                                    </div>
                                    <div class="mm-request-details">
                                        <div class="mm-request-detail">
                                            <span class="mm-request-detail-label">Skill Level</span>
                                            <span class="mm-request-detail-value">{{ ucfirst($request->skill_level ?? 'any') }}</span>
                                        </div>
                                        <div class="mm-request-detail">
                                            <span class="mm-request-detail-label">Role</span>
                                            <span class="mm-request-detail-value">
                                                @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                                    {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                                @else
                                                    Any Role
                                                @endif
                                            </span>
                                        </div>
                                        <div class="mm-request-detail">
                                            <span class="mm-request-detail-label">Created</span>
                                            <span class="mm-request-detail-value">{{ $request->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <div class="mm-card-actions">
                                        <button onclick="findTeams({{ $request->id }})" class="mm-btn mm-btn-primary" style="flex: 1;">Find Teams</button>
                                        <button onclick="cancelRequest({{ $request->id }})" class="mm-btn mm-btn-secondary">Cancel</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Live Team Recommendations -->
                    <div class="mm-section" data-stagger="1" id="live-recommendations">
                        <div class="mm-section-header">
                            <h3 class="mm-section-title">
                                <span class="mm-section-title-icon">
                                    <svg viewBox="0 0 24 24">
                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                    </svg>
                                </span>
                                Live Recommendations
                            </h3>
                            <span class="mm-live-badge">
                                <span class="mm-live-dot"></span>
                                Live
                            </span>
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
                                <div class="mm-empty-state" style="border: none; background: transparent; padding: 32px;">
                                    <div class="mm-empty-icon">
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M9 9h.01M15 9h.01M8 14s1.5 2 4 2 4-2 4-2"/>
                                        </svg>
                                    </div>
                                    <h3 class="mm-empty-title">No Compatible Teams Found</h3>
                                    <p class="mm-empty-text">Try adjusting your criteria or check back later for new teams</p>
                                </div>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    @foreach($topRecommendations as $index => $recommendation)
                                        @php
                                            $team = $recommendation['team'];
                                            $compatScore = $recommendation['compatibility_score'];
                                            $breakdown = $recommendation['breakdown'] ?? [];

                                            // Determine score class
                                            $scoreClass = $compatScore >= 70 ? 'score-high' : ($compatScore >= 50 ? 'score-medium' : 'score-low');
                                        @endphp

                                        <div class="mm-card" style="animation-delay: {{ ($index + 1) * 100 }}ms;">
                                            <div class="mm-card-header">
                                                <div class="mm-card-info">
                                                    <h4 class="mm-card-name">
                                                        <a href="{{ route('teams.show', $team) }}">{{ $team->name }}</a>
                                                    </h4>
                                                    <div class="mm-card-meta">{{ $team->game_name ?? 'Unknown Game' }}</div>
                                                </div>

                                                <!-- Match Score Ring -->
                                                <div class="mm-match-ring">
                                                    <svg viewBox="0 0 36 36" class="mm-match-ring-svg animated">
                                                        <circle class="ring-bg" cx="18" cy="18" r="16"/>
                                                        <circle class="ring-fill {{ $scoreClass }}" cx="18" cy="18" r="16"
                                                                style="stroke-dasharray: {{ round($compatScore) }}, 100"/>
                                                    </svg>
                                                    <span class="mm-match-value">{{ round($compatScore) }}%</span>
                                                </div>
                                            </div>

                                            <!-- Compatibility Breakdown -->
                                            @if(!empty($breakdown))
                                            <div class="mm-breakdown-grid">
                                                @foreach($breakdown as $key => $value)
                                                    @if(is_numeric($value))
                                                    @php
                                                        $barClass = $value >= 70 ? 'score-high' : ($value >= 50 ? 'score-medium' : 'score-low');
                                                    @endphp
                                                    <div class="mm-breakdown-item">
                                                        <span class="mm-breakdown-label">{{ ucfirst($key) }}</span>
                                                        <div class="mm-breakdown-bar">
                                                            <div class="mm-breakdown-fill {{ $barClass }}" style="width: {{ min($value, 100) }}%"></div>
                                                        </div>
                                                        <span class="mm-breakdown-value">{{ round($value) }}%</span>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            @endif

                                            <!-- Team Tags -->
                                            <div class="mm-tags">
                                                @if($team->skill_level)
                                                    <span class="mm-tag">{{ ucfirst($team->skill_level) }}</span>
                                                @endif
                                                @if($team->preferred_region)
                                                    <span class="mm-tag mm-tag-secondary">{{ ucfirst(str_replace('_', ' ', $team->preferred_region)) }}</span>
                                                @endif
                                                <span class="mm-tag mm-tag-secondary">{{ $team->activeMembers->count() }}/{{ $team->max_size }} members</span>
                                            </div>

                                            <!-- Avatar Stack -->
                                            <div class="mm-avatar-stack">
                                                @foreach($team->activeMembers->take(5) as $member)
                                                    <img
                                                        src="{{ $member->user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                                                        alt="{{ $member->user->display_name }}"
                                                        title="{{ $member->user->display_name }}"
                                                        class="mm-avatar"
                                                    >
                                                @endforeach
                                                @if($team->activeMembers->count() > 5)
                                                    <div class="mm-avatar-overflow">+{{ $team->activeMembers->count() - 5 }}</div>
                                                @endif
                                            </div>

                                            <!-- Actions -->
                                            <div class="mm-card-actions">
                                                <a href="{{ route('teams.show', $team) }}" class="mm-btn mm-btn-secondary" style="flex: 1;">View Team</a>
                                                @if($team->recruitment_status === 'open')
                                                    <button onclick="joinTeamDirect({{ $team->id }}, event)" class="mm-btn mm-btn-success" style="flex: 1;">Join Team</button>
                                                @else
                                                    <button onclick="requestToJoin({{ $team->id }})" class="mm-btn mm-btn-primary" style="flex: 1;">Request to Join</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                @endif

                <!-- Players Looking for Teams (Team Leaders Only) -->
                @php
                    $recruitingTeams = auth()->user()->getRecruitingTeamsAsLeader();
                @endphp

                @if($recruitingTeams->isNotEmpty())
                <div class="mm-section" data-stagger="2" id="players-looking-section">
                    <div class="mm-section-header">
                        <h3 class="mm-section-title">
                            <span class="mm-section-title-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            Players Looking for Teams
                        </h3>
                        <span class="mm-live-badge">
                            <span class="mm-live-dot"></span>
                            Live
                        </span>
                    </div>
                    <p style="color: var(--color-text-muted); margin-bottom: 16px; font-size: 14px;">
                        Players with active matchmaking requests for your team's game. Invite them directly!
                    </p>
                    <div id="players-looking-content">
                        <div style="text-align: center; padding: 40px;">
                            <div style="width: 32px; height: 32px; border: 3px solid rgba(102, 126, 234, 0.2); border-top-color: var(--accent-primary); border-radius: 50%; animation: mmSpin 1s linear infinite; margin: 0 auto 12px;"></div>
                            <p style="color: var(--color-text-muted); font-size: 14px;">Loading players...</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</main>

<!-- Create Request Modal -->
<div id="createRequestModal" class="mm-modal-overlay">
    <div class="mm-modal">
        <div class="mm-modal-header">
            <h3>Create Matchmaking Request</h3>
            <button type="button" class="mm-modal-close" onclick="hideCreateRequestModal()" aria-label="Close">&times;</button>
        </div>

        <form id="createRequestForm" action="{{ route('matchmaking.store') }}" method="POST">
            @csrf
            <div class="mm-modal-body">
                <!-- Game Selection Section -->
                <div class="mm-form-section">
                    <div class="mm-form-section-title">Game Selection</div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#127919;</span>
                            Select Game
                            <span class="required">*</span>
                        </label>
                        <select id="game_appid" name="game_appid" required onchange="updateGameName(); loadSkillPreview(this.value); updateRoleOptions(this.value);">
                            <option value="">Choose a game to find teammates...</option>
                            <option value="730" data-name="Counter-Strike 2">Counter-Strike 2</option>
                            <option value="548430" data-name="Deep Rock Galactic">Deep Rock Galactic</option>
                            <option value="493520" data-name="GTFO">GTFO</option>
                        </select>
                        <input type="hidden" id="game_name" name="game_name" value="">
                    </div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#128200;</span>
                            Your Skill Level
                        </label>
                        <div id="skill_display_container" class="mm-skill-display-box">
                            <div id="skill_display" class="mm-skill-placeholder">
                                Select a game to see your skill level
                            </div>
                        </div>
                        <input type="hidden" id="skill_level" name="skill_level" value="unranked">
                        <span class="hint">Automatically calculated from your Steam stats and playtime</span>
                    </div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#127917;</span>
                            Preferred Roles
                        </label>
                        <div id="role_selection_container" class="mm-role-selection-box">
                            <div class="mm-role-placeholder">Select a game to see available roles</div>
                        </div>
                        <span class="hint">Hold Ctrl/Cmd to select multiple roles</span>
                    </div>
                </div>

                <!-- Preferences Section -->
                <div class="mm-form-section">
                    <div class="mm-form-section-title">Your Preferences</div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#127758;</span>
                            Preferred Regions
                        </label>
                        <select id="preferred_regions" name="preferred_regions[]" multiple>
                            <option value="NA">North America</option>
                            <option value="EU">Europe</option>
                            <option value="ASIA">Asia</option>
                            <option value="SA">South America</option>
                            <option value="OCEANIA">Oceania</option>
                            <option value="AFRICA">Africa</option>
                            <option value="MIDDLE_EAST">Middle East</option>
                        </select>
                        <span class="hint">Select regions where you prefer to play (for latency)</span>
                    </div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#128336;</span>
                            When Can You Play?
                        </label>
                        <select id="availability_hours" name="availability_hours[]" multiple>
                            <option value="morning">Morning (6AM-12PM)</option>
                            <option value="afternoon">Afternoon (12PM-6PM)</option>
                            <option value="evening">Evening (6PM-12AM)</option>
                            <option value="night">Night (12AM-6AM)</option>
                            <option value="flexible">Flexible Schedule</option>
                        </select>
                        <span class="hint">Select your typical gaming hours</span>
                    </div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#128172;</span>
                            Languages You Speak
                        </label>
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
                        <span class="hint">Select languages for team communication</span>
                    </div>
                </div>

                <!-- Additional Info Section -->
                <div class="mm-form-section">
                    <div class="mm-form-section-title">Additional Info</div>

                    <div class="mm-form-group">
                        <label>
                            <span class="icon">&#128221;</span>
                            Message (Optional)
                        </label>
                        <textarea id="message" name="message" rows="3" placeholder="Tell potential teammates more about yourself, your playstyle, or what you're looking for..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mm-modal-footer">
                <button type="button" onclick="hideCreateRequestModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Find Teams Results Modal -->
<div id="findTeamsModal" class="mm-modal-overlay" style="z-index: 2000;">
    <div class="mm-modal" style="max-width: 900px;">
        <div class="mm-modal-header">
            <h3>Compatible Teams Found</h3>
            <button type="button" class="mm-modal-close" onclick="hideFindTeamsModal()" aria-label="Close">&times;</button>
        </div>
        <div class="mm-modal-body" id="findTeamsResults" style="max-height: calc(90vh - 120px);">
            <!-- Results will be populated here -->
        </div>
    </div>
</div>

<!-- Invite Player Modal -->
<div id="invitePlayerModal" class="mm-modal-overlay" style="z-index: 2000;">
    <div class="mm-modal" style="max-width: 400px;">
        <div class="mm-modal-header">
            <h3>Invite Player to Team</h3>
            <button type="button" class="mm-modal-close" onclick="closeInviteModal()" aria-label="Close">&times;</button>
        </div>
        <div class="mm-modal-body">
            <div id="invitePlayerInfo" style="margin-bottom: 20px;"></div>

            <div class="mm-form-group" id="teamSelectGroup" style="display: none;">
                <label>Select Team</label>
                <select id="inviteTeamSelect"></select>
            </div>

            <div class="mm-form-group">
                <label>Role in Team</label>
                <select id="inviteRole">
                    <option value="member">Member</option>
                    <option value="co_leader">Co-Leader</option>
                </select>
            </div>

            <div class="mm-form-group">
                <label>Message (Optional)</label>
                <textarea id="inviteMessage" rows="2" placeholder="Found you through matchmaking! Would you like to join?"></textarea>
            </div>
        </div>
        <div class="mm-modal-footer">
            <button type="button" onclick="closeInviteModal()" class="btn btn-secondary">Cancel</button>
            <button id="sendInviteBtn" onclick="sendPlayerInvite()" class="btn btn-primary">Send Invitation</button>
        </div>
    </div>
</div>

<style>
@keyframes mmSpin {
    to { transform: rotate(360deg); }
}
</style>

<script>
// Modal functions
function showCreateRequestModal() {
    const modal = document.getElementById('createRequestModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideCreateRequestModal() {
    const modal = document.getElementById('createRequestModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
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
        container.className = 'mm-role-selection-box';
        container.innerHTML = '<div class="mm-role-placeholder">Select a game to see available roles</div>';
        return;
    }

    container.className = '';
    let html = '<select id="preferred_roles" name="preferred_roles[]" multiple style="width: 100%; padding: 12px 16px; background-color: var(--color-bg-primary); border: 1px solid var(--color-border-secondary); border-radius: 10px; color: var(--color-text-primary); font-size: 14px; min-height: 120px;">';
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
    const skillContainer = document.getElementById('skill_display_container');

    if (!skillDisplay) return;

    if (!gameAppId) {
        if (skillContainer) skillContainer.className = 'mm-skill-display-box';
        skillDisplay.className = 'mm-skill-placeholder';
        skillDisplay.innerHTML = 'Select a game to see your skill level';
        if (skillLevelInput) skillLevelInput.value = 'unranked';
        return;
    }

    // Show loading state
    if (skillContainer) skillContainer.className = 'mm-skill-display-box';
    skillDisplay.className = 'mm-skill-placeholder';
    skillDisplay.innerHTML = `
        <span style="display: inline-flex; align-items: center; gap: 8px;">
            <span style="display: inline-block; animation: mmSpin 1s linear infinite;">&#8987;</span>
            Loading your skill level...
        </span>
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
            <div style="color: var(--alert-error-text); font-size: 14px; padding: 10px 0;">
                Could not load skill level. Please try again.
            </div>
        `;
        if (skillLevelInput) skillLevelInput.value = 'unranked';
    }
}

function updateSkillDisplay(data) {
    const skillDisplay = document.getElementById('skill_display');
    const skillLevelInput = document.getElementById('skill_level');
    const skillContainer = document.getElementById('skill_display_container');
    if (!skillDisplay) return;

    if (skillContainer) skillContainer.className = '';
    skillDisplay.className = '';

    const { skill_level, skill_score, breakdown, is_unranked } = data;

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
        'expert': '&#11088;',
        'advanced': '&#127919;',
        'intermediate': '&#128200;',
        'beginner': '&#127918;',
        'unranked': '&#10067;',
    };

    const color = levelColors[skill_level] || '#71717a';
    const icon = levelIcons[skill_level] || '&#10067;';

    let breakdownHtml = '';
    if (!is_unranked && breakdown) {
        breakdownHtml = `<div style="font-weight: 600; margin-bottom: 12px; color: var(--color-text-primary);">Skill Breakdown</div>`;
        for (const [key, value] of Object.entries(breakdown)) {
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
                    <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid var(--color-border-secondary); font-size: 13px;">
                        <span style="color: var(--color-text-secondary);">${label}</span>
                        <span style="color: var(--color-text-primary); font-weight: 500;">${displayValue}</span>
                    </div>
                `;
            }
        }

        if (breakdown && breakdown.note) {
            breakdownHtml += `
                <div style="margin-top: 10px; padding: 8px; background: var(--color-bg-primary); border-radius: 4px; font-size: 12px; color: var(--color-text-muted);">
                    ${breakdown.note}
                </div>
            `;
        }
    } else {
        breakdownHtml = `
            <div style="font-weight: 600; margin-bottom: 8px; color: var(--color-text-primary);">Why Unranked?</div>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 8px 0;">
                We couldn't find enough game data.
            </p>
            <ul style="font-size: 12px; color: var(--color-text-muted); margin: 0; padding-left: 16px;">
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
                    ${!is_unranked && skill_score ? `<div style="font-size: 11px; color: var(--color-text-secondary);">Score: ${Math.round(skill_score)}/100</div>` : ''}
                </div>
                <span style="margin-left: 4px; color: var(--color-text-muted); font-size: 14px;">&#9432;</span>
            </div>

            <div id="skill-tooltip" style="
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                margin-top: 8px;
                background-color: var(--color-surface);
                border: 1px solid var(--color-border-primary);
                border-radius: 8px;
                padding: 16px;
                min-width: 280px;
                z-index: 100;
                box-shadow: var(--tooltip-shadow);
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

function joinTeamDirect(teamId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const btn = event?.target?.closest('button');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span style="display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: mmSpin 0.8s linear infinite;"></span>';
    }

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
            alert(data.message || 'Error joining team');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Join Team';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error joining team');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Join Team';
        }
    });
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

function showFindTeamsLoading() {
    const modal = document.getElementById('findTeamsModal');
    const results = document.getElementById('findTeamsResults');

    results.innerHTML = `
        <div style="text-align: center; padding: 60px 40px; color: var(--color-text-secondary);">
            <div style="width: 48px; height: 48px; border: 4px solid var(--color-border-primary); border-top-color: var(--accent-primary); border-radius: 50%; animation: mmSpin 1s linear infinite; margin: 0 auto 20px;"></div>
            <p style="font-size: 16px; margin: 0;">Searching for compatible teams...</p>
            <p style="font-size: 14px; color: var(--color-text-muted); margin-top: 8px;">This may take a moment</p>
        </div>
    `;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function displayFindTeamsResults(teams) {
    const results = document.getElementById('findTeamsResults');

    let html = `<div style="margin-bottom: 16px; color: #10b981; font-size: 14px; font-weight: 600;">Found ${teams.length} compatible team${teams.length !== 1 ? 's' : ''}</div>`;

    teams.forEach(team => {
        const compatibility = team.compatibility_score || 0;
        const scoreClass = compatibility >= 70 ? 'score-high' : (compatibility >= 50 ? 'score-medium' : 'score-low');
        const scoreColor = compatibility >= 70 ? '#10b981' : (compatibility >= 50 ? '#f59e0b' : '#ef4444');

        html += `
            <div class="mm-card" style="margin-bottom: 12px;">
                <div class="mm-card-header">
                    <div class="mm-card-info">
                        <h4 class="mm-card-name">${team.name}</h4>
                        <div class="mm-card-meta">${team.game_name || 'Unknown Game'}</div>
                    </div>
                    <div class="mm-match-ring">
                        <svg viewBox="0 0 36 36" class="mm-match-ring-svg animated">
                            <circle class="ring-bg" cx="18" cy="18" r="16"/>
                            <circle class="ring-fill ${scoreClass}" cx="18" cy="18" r="16" style="stroke-dasharray: ${Math.round(compatibility)}, 100"/>
                        </svg>
                        <span class="mm-match-value">${Math.round(compatibility)}%</span>
                    </div>
                </div>
                <div class="mm-tags">
                    <span class="mm-tag">${team.skill_level ? team.skill_level.charAt(0).toUpperCase() + team.skill_level.slice(1) : 'Casual'}</span>
                    <span class="mm-tag mm-tag-secondary">${team.current_size || 0}/${team.max_size || 5} Members</span>
                    ${team.preferred_region ? `<span class="mm-tag mm-tag-secondary">${team.preferred_region.replace(/_/g, ' ')}</span>` : ''}
                </div>
                <div class="mm-card-actions">
                    <a href="/teams/${team.id}" class="mm-btn mm-btn-secondary" style="flex: 1;">View Team</a>
                    <button onclick="requestToJoinFromModal(${team.id})" class="mm-btn mm-btn-primary" style="flex: 1;">Request to Join</button>
                </div>
            </div>
        `;
    });

    document.getElementById('findTeamsResults').innerHTML = html;
}

function showNoTeamsFound() {
    const results = document.getElementById('findTeamsResults');
    results.innerHTML = `
        <div class="mm-empty-state" style="border: none; background: transparent;">
            <div class="mm-empty-icon">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </div>
            <h3 class="mm-empty-title">No Compatible Teams Found</h3>
            <p class="mm-empty-text">Try adjusting your matchmaking preferences or create your own team.</p>
            <div class="mm-empty-actions">
                <button onclick="hideFindTeamsModal()" class="btn btn-secondary">Close</button>
                <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
            </div>
        </div>
    `;
}

function showFindTeamsError(errorMessage) {
    const results = document.getElementById('findTeamsResults');
    results.innerHTML = `
        <div class="mm-empty-state" style="border: none; background: transparent;">
            <div class="mm-empty-icon" style="background: rgba(239, 68, 68, 0.1);">
                <svg viewBox="0 0 24 24" style="stroke: #ef4444;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h3 class="mm-empty-title">Error Finding Teams</h3>
            <p class="mm-empty-text">${errorMessage || 'An unexpected error occurred. Please try again.'}</p>
            <div class="mm-empty-actions">
                <button onclick="hideFindTeamsModal()" class="btn btn-secondary">Close</button>
                <button onclick="location.reload()" class="btn btn-primary">Retry</button>
            </div>
        </div>
    `;
}

function hideFindTeamsModal() {
    document.getElementById('findTeamsModal').classList.remove('active');
    document.body.style.overflow = '';
}

function requestToJoinFromModal(teamId) {
    hideFindTeamsModal();
    requestToJoin(teamId);
}

// Close modal when clicking background
document.getElementById('findTeamsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideFindTeamsModal();
    }
});

document.getElementById('createRequestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCreateRequestModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const createModal = document.getElementById('createRequestModal');
        if (createModal && createModal.classList.contains('active')) {
            hideCreateRequestModal();
        }
        const findModal = document.getElementById('findTeamsModal');
        if (findModal && findModal.classList.contains('active')) {
            hideFindTeamsModal();
        }
        const inviteModal = document.getElementById('invitePlayerModal');
        if (inviteModal && inviteModal.classList.contains('active')) {
            closeInviteModal();
        }
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
            alert('Matchmaking request created successfully!');
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

// ===============================
// Players Looking for Teams Section
// ===============================
let currentInvitePlayer = null;

// Load players looking for teams on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPlayersLooking();
});

// Expose loadPlayersLooking to window for real-time updates
window.loadPlayersLooking = loadPlayersLooking;

function loadPlayersLooking() {
    const container = document.getElementById('players-looking-content');
    if (!container) return;

    fetch('/matchmaking/players-looking', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.players && data.players.length > 0) {
            renderPlayersLooking(data.players);
        } else {
            container.innerHTML = `
                <div class="mm-empty-state" style="border: none; background: transparent; padding: 32px;">
                    <div class="mm-empty-icon">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3 class="mm-empty-title">No Players Currently Looking</h3>
                    <p class="mm-empty-text">Check back later for new matchmaking requests</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading players:', error);
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--color-text-muted);">
                <p>Failed to load players. <a href="javascript:loadPlayersLooking()" style="color: var(--accent-primary);">Retry</a></p>
            </div>
        `;
    });
}

function renderPlayersLooking(players) {
    const container = document.getElementById('players-looking-content');
    container.innerHTML = players.map(player => createPlayerCard(player)).join('');
}

function createPlayerCard(player) {
    const scoreClass = player.compatibility_score >= 70 ? 'score-high' : (player.compatibility_score >= 50 ? 'score-medium' : 'score-low');

    const rolesHtml = (player.preferred_roles || []).slice(0, 3)
        .map(r => `<span class="mm-tag mm-tag-role">${r.replace(/_/g, ' ')}</span>`).join(' ');

    const regionsHtml = (player.preferred_regions || []).slice(0, 2)
        .map(r => `<span class="mm-tag mm-tag-secondary">${r}</span>`).join(' ');

    const avatar = player.avatar || '/images/default-avatar.png';
    const playerDataStr = JSON.stringify(player).replace(/'/g, "\\'").replace(/"/g, '&quot;');

    return `
        <div class="mm-player-card">
            <div class="mm-player-info">
                <img src="${avatar}"
                     class="mm-player-avatar ${scoreClass}"
                     alt="${player.username}"
                     onerror="this.src='/images/default-avatar.png'">
                <div class="mm-player-details">
                    <div class="mm-player-name">${player.display_name || player.username}</div>
                    <div class="mm-player-game">
                        ${player.game_name}
                        <span class="mm-tag" style="margin-left: 8px;">${player.skill_level || 'Unranked'}</span>
                    </div>
                    <div class="mm-player-tags">
                        ${rolesHtml}
                        ${regionsHtml}
                    </div>
                </div>
            </div>
            <div class="mm-player-score">
                <div class="mm-player-score-value ${scoreClass}">${Math.round(player.compatibility_score)}%</div>
                <div class="mm-player-score-label">Match</div>
            </div>
            <button class="mm-btn mm-btn-primary" onclick='openInviteModal(${playerDataStr})'>Invite</button>
        </div>
    `;
}

function openInviteModal(player) {
    if (typeof player === 'string') {
        try {
            player = JSON.parse(player);
        } catch (e) {
            console.error('Error parsing player data:', e);
            return;
        }
    }

    currentInvitePlayer = player;

    const avatar = player.avatar || '/images/default-avatar.png';
    document.getElementById('invitePlayerInfo').innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--color-bg-primary); border-radius: 8px;">
            <img src="${avatar}"
                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;"
                 onerror="this.src='/images/default-avatar.png'">
            <div>
                <div style="font-weight: 600; font-size: 16px; color: var(--color-text-primary);">${player.display_name || player.username}</div>
                <div style="font-size: 13px; color: var(--color-text-muted);">${player.game_name} - ${player.skill_level || 'Unranked'}</div>
            </div>
        </div>
    `;

    const teamSelectGroup = document.getElementById('teamSelectGroup');
    const teamSelect = document.getElementById('inviteTeamSelect');

    if (player.all_matching_teams && player.all_matching_teams.length > 1) {
        teamSelectGroup.style.display = 'block';
        teamSelect.innerHTML = player.all_matching_teams.map(t =>
            `<option value="${t.id}">${t.name} (${t.member_count}/${t.max_size})</option>`
        ).join('');
    } else {
        teamSelectGroup.style.display = 'none';
        const team = player.best_team || (player.all_matching_teams && player.all_matching_teams[0]);
        if (team) {
            teamSelect.innerHTML = `<option value="${team.id}">${team.name}</option>`;
        }
    }

    document.getElementById('invitePlayerModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeInviteModal() {
    document.getElementById('invitePlayerModal').classList.remove('active');
    document.body.style.overflow = '';
    currentInvitePlayer = null;
    document.getElementById('inviteMessage').value = '';
    document.getElementById('inviteRole').value = 'member';
}

function sendPlayerInvite() {
    if (!currentInvitePlayer) return;

    const teamId = document.getElementById('inviteTeamSelect').value;
    const role = document.getElementById('inviteRole').value;
    const message = document.getElementById('inviteMessage').value;

    const btn = document.getElementById('sendInviteBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Sending...';

    fetch(`/teams/${teamId}/invitations`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: currentInvitePlayer.user_id,
            role: role,
            message: message || 'Found you through matchmaking! Would you like to join our team?'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation sent successfully!');
            closeInviteModal();
            loadPlayersLooking();
        } else {
            alert(data.message || 'Failed to send invitation');
        }
    })
    .catch(error => {
        console.error('Error sending invitation:', error);
        alert('Failed to send invitation. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Close invite modal when clicking background
document.getElementById('invitePlayerModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeInviteModal();
    }
});
</script>
@endsection
