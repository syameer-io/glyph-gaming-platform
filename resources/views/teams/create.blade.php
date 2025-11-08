@extends('layouts.app')

@section('title', 'Create Team - Glyph')

@push('styles')
<style>
    .create-team-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-container {
        background-color: #18181b;
        border-radius: 12px;
        padding: 32px;
        border: 1px solid #3f3f46;
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-section:last-child {
        margin-bottom: 0;
    }
    
    .section-header {
        margin-bottom: 20px;
        border-bottom: 1px solid #3f3f46;
        padding-bottom: 16px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 4px;
    }
    
    .section-description {
        font-size: 14px;
        color: #b3b3b5;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-row.single {
        grid-template-columns: 1fr;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #efeff1;
    }
    
    .form-group .form-description {
        font-size: 12px;
        color: #71717a;
        margin-bottom: 8px;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        background-color: #0e0e10;
        border: 2px solid #3f3f46;
        border-radius: 8px;
        color: #efeff1;
        font-size: 16px;
        transition: all 0.2s;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        background-color: #18181b;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .checkbox-group label {
        margin: 0;
        color: #b3b3b5;
        cursor: pointer;
    }
    
    .role-selection {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    
    .role-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background-color: #0e0e10;
        border: 2px solid #3f3f46;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .role-option:hover {
        border-color: #667eea;
    }
    
    .role-option.selected {
        border-color: #667eea;
        background-color: rgba(102, 126, 234, 0.1);
    }
    
    .role-option input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .role-option label {
        margin: 0;
        color: #efeff1;
        font-size: 14px;
        cursor: pointer;
        flex: 1;
    }
    
    .role-description {
        font-size: 12px;
        color: #71717a;
        margin-top: 4px;
    }
    
    .member-slots {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }
    
    .member-slot {
        background-color: #0e0e10;
        border: 2px solid #3f3f46;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }
    
    .member-slot.leader {
        border-color: #667eea;
        background-color: rgba(102, 126, 234, 0.1);
    }
    
    .slot-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        margin: 0 auto 12px;
        object-fit: cover;
    }
    
    .slot-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: #3f3f46;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #71717a;
        font-size: 24px;
    }
    
    .slot-name {
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 4px;
    }
    
    .slot-role {
        font-size: 12px;
        color: #b3b3b5;
        text-transform: uppercase;
    }
    
    .preview-section {
        background-color: #0e0e10;
        border-radius: 8px;
        padding: 20px;
        margin-top: 24px;
    }
    
    .preview-header {
        font-size: 16px;
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 16px;
    }
    
    .team-preview {
        background-color: #18181b;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #3f3f46;
    }
    
    .preview-team-name {
        font-size: 18px;
        font-weight: 600;
        color: #efeff1;
        margin-bottom: 8px;
    }
    
    .preview-game {
        font-size: 14px;
        color: #b3b3b5;
        margin-bottom: 12px;
    }
    
    .preview-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }
    
    .preview-tag {
        font-size: 11px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .form-actions {
        display: flex;
        gap: 16px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #3f3f46;
    }
    
    .error-message {
        color: #ef4444;
        font-size: 14px;
        margin-top: 8px;
    }
    
    .game-info {
        background-color: rgba(102, 126, 234, 0.1);
        border: 1px solid #667eea;
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
        font-size: 14px;
        color: #b3b3b5;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @media (max-width: 768px) {
        .form-container {
            padding: 24px;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .role-selection {
            grid-template-columns: 1fr;
        }
        
        .member-slots {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
        
        .form-actions {
            flex-direction: column;
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
        <div class="create-team-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <h1>🚀 Create New Team</h1>
                    <p style="color: #b3b3b5; margin-top: 8px;">Form your gaming squad and start competing together</p>
                </div>
                <a href="{{ route('teams.index') }}" class="btn btn-secondary">← Back to Teams</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-error" style="margin-bottom: 24px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('teams.store') }}" method="POST" id="createTeamForm">
                @csrf
                <div class="form-container">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-title">📝 Basic Information</div>
                            <div class="section-description">Set up your team's identity and core details</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Team Name *</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="50" onkeyup="updatePreview()">
                                <div class="form-description">Choose a unique and memorable name for your team</div>
                            </div>
                            <div class="form-group">
                                <label for="game_appid">Game *</label>
                                <select id="game_appid" name="game_appid" required onchange="updateGameInfo(); updatePreview()">
                                    <option value="">Select a game...</option>
                                    <option value="730" {{ old('game_appid') == '730' ? 'selected' : '' }}>Counter-Strike 2</option>
                                    <option value="570" {{ old('game_appid') == '570' ? 'selected' : '' }}>Dota 2</option>
                                    <option value="230410" {{ old('game_appid') == '230410' ? 'selected' : '' }}>Warframe</option>
                                    <option value="1172470" {{ old('game_appid') == '1172470' ? 'selected' : '' }}>Apex Legends</option>
                                    <option value="252490" {{ old('game_appid') == '252490' ? 'selected' : '' }}>Rust</option>
                                    <option value="578080" {{ old('game_appid') == '578080' ? 'selected' : '' }}>PUBG</option>
                                    <option value="359550" {{ old('game_appid') == '359550' ? 'selected' : '' }}>Rainbow Six Siege</option>
                                    <option value="433850" {{ old('game_appid') == '433850' ? 'selected' : '' }}>Fall Guys</option>
                                </select>
                                <input type="hidden" id="game_name" name="game_name" value="{{ old('game_name') }}">
                                <div id="game-info" class="game-info" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="form-row single">
                            <div class="form-group">
                                <label for="server_id">Server *</label>
                                <select id="server_id" name="server_id" required>
                                    <option value="">Select a server...</option>
                                    @foreach($servers as $server)
                                        <option value="{{ $server->id }}" {{ old('server_id') == $server->id ? 'selected' : '' }}>{{ $server->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-description">Choose which server this team will be associated with</div>
                            </div>
                        </div>

                        <div class="form-row single">
                            <div class="form-group">
                                <label for="description">Team Description</label>
                                <textarea id="description" name="description" placeholder="Describe your team's goals, playstyle, and what you're looking for in teammates..." onkeyup="updatePreview()">{{ old('description') }}</textarea>
                                <div class="form-description">Optional: Help potential members understand your team's culture and goals</div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Configuration -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-title">⚙️ Team Configuration</div>
                            <div class="section-description">Define your team's structure and requirements</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="max_size">Team Size *</label>
                                <select id="max_size" name="max_size" required disabled style="background-color: #18181b; cursor: not-allowed; opacity: 0.7;">
                                    <option value="">Select a game first...</option>
                                </select>
                                <div class="form-description" id="team-size-info" style="color: #667eea; margin-top: 6px; display: none;">
                                    🔒 Team size is automatically set based on the game's competitive standard
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="skill_level">Skill Level *</label>
                                <select id="skill_level" name="skill_level" required onchange="updatePreview()">
                                    <option value="">Select skill level...</option>
                                    <option value="beginner" {{ old('skill_level') == 'beginner' ? 'selected' : '' }}>Beginner - New to the game</option>
                                    <option value="intermediate" {{ old('skill_level') == 'intermediate' ? 'selected' : '' }}>Intermediate - Some experience</option>
                                    <option value="advanced" {{ old('skill_level') == 'advanced' ? 'selected' : '' }}>Advanced - Experienced player</option>
                                    <option value="expert" {{ old('skill_level') == 'expert' ? 'selected' : '' }}>Expert - Competitive level</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="preferred_region">Preferred Region *</label>
                                <select id="preferred_region" name="preferred_region" required onchange="updatePreview()">
                                    <option value="">Select region...</option>
                                    <option value="na_east" {{ old('preferred_region') == 'na_east' ? 'selected' : '' }}>North America East</option>
                                    <option value="na_west" {{ old('preferred_region') == 'na_west' ? 'selected' : '' }}>North America West</option>
                                    <option value="eu_west" {{ old('preferred_region') == 'eu_west' ? 'selected' : '' }}>Europe West</option>
                                    <option value="eu_east" {{ old('preferred_region') == 'eu_east' ? 'selected' : '' }}>Europe East</option>
                                    <option value="asia" {{ old('preferred_region') == 'asia' ? 'selected' : '' }}>Asia</option>
                                    <option value="oceania" {{ old('preferred_region') == 'oceania' ? 'selected' : '' }}>Oceania</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="recruitment_status">Recruitment Status *</label>
                                <select id="recruitment_status" name="recruitment_status" required>
                                    <option value="open" {{ old('recruitment_status') == 'open' ? 'selected' : '' }}>Open - Anyone can join</option>
                                    <option value="closed" {{ old('recruitment_status') == 'closed' ? 'selected' : '' }}>Closed - Invite only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="communication_required" name="communication_required" value="1" {{ old('communication_required') ? 'checked' : '' }}>
                                    <label for="communication_required">Voice communication required</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="competitive_focus" name="competitive_focus" value="1" {{ old('competitive_focus') ? 'checked' : '' }}>
                                    <label for="competitive_focus">Competitive focus</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Matchmaking Preferences -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-title">🎯 Matchmaking Preferences</div>
                            <div class="section-description">Help the matchmaking algorithm find the right players for your team</div>
                        </div>

                        <div class="form-group">
                            <label>Required Roles (Optional)</label>
                            <div class="form-description">Select roles you're looking for. Leave empty if you're flexible</div>
                            <div class="role-selection" style="margin-top: 12px;">
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="entry_fragger">
                                    <span>Entry Fragger</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="support">
                                    <span>Support</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="awper">
                                    <span>AWPer</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="igl">
                                    <span>IGL</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="lurker">
                                    <span>Lurker</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="carry">
                                    <span>Carry</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="mid">
                                    <span>Mid</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="offlaner">
                                    <span>Offlaner</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="dps">
                                    <span>DPS</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="tank">
                                    <span>Tank</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="required_roles[]" value="healer">
                                    <span>Healer</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Team Activity Times (Optional)</label>
                            <div class="form-description">When is your team typically active? Select all that apply</div>
                            <div class="role-selection" style="margin-top: 12px;">
                                <label class="role-option">
                                    <input type="checkbox" name="activity_times[]" value="morning" onchange="updatePreview()">
                                    <span>Morning (6AM-12PM)</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="activity_times[]" value="afternoon" onchange="updatePreview()">
                                    <span>Afternoon (12PM-6PM)</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="activity_times[]" value="evening" onchange="updatePreview()">
                                    <span>Evening (6PM-12AM)</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="activity_times[]" value="night" onchange="updatePreview()">
                                    <span>Night (12AM-6AM)</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="activity_times[]" value="flexible" onchange="updatePreview()">
                                    <span>Flexible Schedule</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Languages (Optional)</label>
                            <div class="form-description">Languages spoken by your team. Select all that apply</div>
                            <div class="role-selection" style="margin-top: 12px;">
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="en" checked>
                                    <span>English</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="es">
                                    <span>Spanish</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="zh">
                                    <span>Chinese</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="fr">
                                    <span>French</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="de">
                                    <span>German</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="pt">
                                    <span>Portuguese</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="ru">
                                    <span>Russian</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="ja">
                                    <span>Japanese</span>
                                </label>
                                <label class="role-option">
                                    <input type="checkbox" name="languages[]" value="ko">
                                    <span>Korean</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Team Structure -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-title">👥 Team Structure</div>
                            <div class="section-description">Preview your team setup and member roles</div>
                        </div>

                        <div id="member-slots" class="member-slots">
                            <!-- Dynamic member slots will be generated here -->
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="preview-section">
                        <div class="preview-header">🔍 Team Preview</div>
                        <div class="team-preview" id="team-preview">
                            <div class="preview-team-name" id="preview-name">Team Name</div>
                            <div class="preview-game" id="preview-game">Select a game</div>
                            <div class="preview-tags" id="preview-tags">
                                <!-- Tags will be generated here -->
                            </div>
                            <div id="preview-description" style="color: #b3b3b5; font-size: 14px;">Team description will appear here...</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('teams.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Team</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
const gameInfo = {
    '730': {
        name: 'Counter-Strike 2',
        description: 'Tactical FPS requiring precise aim, strategy, and team coordination.',
        recommendedSize: 5,
        sizeLabel: '5 Players (Competitive 5v5)',
        roles: ['Entry Fragger', 'Support', 'AWPer', 'IGL', 'Lurker']
    },
    '570': {
        name: 'Dota 2',
        description: 'MOBA with complex mechanics, requiring strategic thinking and role coordination.',
        recommendedSize: 5,
        sizeLabel: '5 Players (Standard MOBA)',
        roles: ['Carry', 'Mid', 'Offlaner', 'Support', 'Hard Support']
    },
    '230410': {
        name: 'Warframe',
        description: 'Co-op action game with diverse Warframes and role specializations.',
        recommendedSize: 4,
        sizeLabel: '4 Players (Full Squad)',
        roles: ['DPS', 'Support', 'Tank', 'Specialist']
    },
    '1172470': {
        name: 'Apex Legends',
        description: 'Battle royale emphasizing team composition and tactical play.',
        recommendedSize: 3,
        sizeLabel: '3 Players (Trio)',
        roles: ['Assault', 'Recon', 'Support']
    },
    '252490': {
        name: 'Rust',
        description: 'Survival game with base building and PvP combat.',
        recommendedSize: 5,
        sizeLabel: '5 Players (Zerg Squad)',
        roles: ['Builder', 'Farmer', 'PvP', 'Scout', 'Leader']
    },
    '578080': {
        name: 'PUBG',
        description: 'Battle royale with realistic gunplay and tactical teamwork.',
        recommendedSize: 4,
        sizeLabel: '4 Players (Squad)',
        roles: ['Entry', 'Support', 'Sniper', 'Scout']
    },
    '359550': {
        name: 'Rainbow Six Siege',
        description: 'Tactical shooter emphasizing strategy, communication, and operator synergy.',
        recommendedSize: 5,
        sizeLabel: '5 Players (Ranked Team)',
        roles: ['Entry', 'Support', 'Anchor', 'Roamer', 'IGL']
    },
    '1446780': {
        name: 'Fall Guys',
        description: 'Party game with chaotic mini-games and team challenges.',
        recommendedSize: 4,
        sizeLabel: '4 Players (Squad Show)',
        roles: ['Grabber', 'Support', 'Speedrunner', 'Tank']
    }
};

function updateGameInfo() {
    const gameSelect = document.getElementById('game_appid');
    const gameInfoDiv = document.getElementById('game-info');
    const gameNameInput = document.getElementById('game_name');
    const sizeSelect = document.getElementById('max_size');
    const sizeInfoDiv = document.getElementById('team-size-info');
    const selectedGame = gameSelect.value;

    if (selectedGame && gameInfo[selectedGame]) {
        const info = gameInfo[selectedGame];

        // Update game name hidden field
        gameNameInput.value = info.name;

        gameInfoDiv.innerHTML = `
            <strong>${info.name}</strong><br>
            ${info.description}
        `;
        gameInfoDiv.style.display = 'block';

        // Auto-set and lock team size based on game
        sizeSelect.innerHTML = `<option value="${info.recommendedSize}">${info.sizeLabel}</option>`;
        sizeSelect.value = info.recommendedSize;
        sizeSelect.disabled = false; // Enable but still locked to single option

        // Show locked info message
        sizeInfoDiv.style.display = 'block';

        // Update member slots with new size
        updateMemberSlots();
        updatePreview();
    } else {
        // No game selected - reset team size
        sizeSelect.innerHTML = '<option value="">Select a game first...</option>';
        sizeSelect.value = '';
        sizeSelect.disabled = true;
        sizeInfoDiv.style.display = 'none';
        gameInfoDiv.style.display = 'none';
    }
}

function updateMemberSlots() {
    const maxMembers = parseInt(document.getElementById('max_size').value);
    const slotsContainer = document.getElementById('member-slots');
    
    if (!maxMembers) {
        slotsContainer.innerHTML = '';
        return;
    }
    
    let slotsHTML = '';
    
    // Leader slot (always present)
    slotsHTML += `
        <div class="member-slot leader">
            <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" class="slot-avatar">
            <div class="slot-name">{{ auth()->user()->display_name }}</div>
            <div class="slot-role">Team Leader</div>
        </div>
    `;
    
    // Empty slots for other members
    for (let i = 2; i <= maxMembers; i++) {
        slotsHTML += `
            <div class="member-slot">
                <div class="slot-placeholder">+</div>
                <div class="slot-name">Open Slot</div>
                <div class="slot-role">Member</div>
            </div>
        `;
    }
    
    slotsContainer.innerHTML = slotsHTML;
}

function updatePreview() {
    const name = document.getElementById('name').value || 'Team Name';
    const gameSelect = document.getElementById('game_appid');
    const skillLevel = document.getElementById('skill_level').value;
    const region = document.getElementById('preferred_region').value;
    const activityTimesCheckboxes = document.querySelectorAll('input[name="activity_times[]"]:checked');
    const activityTimes = Array.from(activityTimesCheckboxes).map(cb => cb.value);
    const description = document.getElementById('description').value;

    // Update name
    document.getElementById('preview-name').textContent = name;

    // Update game
    const gameText = gameSelect.options[gameSelect.selectedIndex].text || 'Select a game';
    document.getElementById('preview-game').textContent = gameText;

    // Update tags
    const tagsContainer = document.getElementById('preview-tags');
    let tagsHTML = '';

    if (skillLevel) {
        tagsHTML += `<span class="preview-tag">${skillLevel.charAt(0).toUpperCase() + skillLevel.slice(1)}</span>`;
    }
    if (region) {
        tagsHTML += `<span class="preview-tag">${region.replace('_', ' ').toUpperCase()}</span>`;
    }
    if (activityTimes.length > 0) {
        activityTimes.forEach(time => {
            tagsHTML += `<span class="preview-tag">${time.toUpperCase()}</span>`;
        });
    }

    tagsContainer.innerHTML = tagsHTML;
    
    // Update description
    const descriptionText = description || 'Team description will appear here...';
    document.getElementById('preview-description').textContent = descriptionText;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateGameInfo();
    updateMemberSlots();
    updatePreview();
});

// Form validation and AJAX submission
document.getElementById('createTeamForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Always prevent default form submission

    const requiredFields = ['name', 'game_appid', 'server_id', 'max_size', 'skill_level', 'preferred_region', 'recruitment_status'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#ef4444';
        } else {
            field.style.borderColor = '#3f3f46';
        }
    });
    
    if (!isValid) {
        showMessage('Please fill in all required fields', 'error');
        return;
    }
    
    // Submit form via AJAX
    submitTeamForm(this);
});

function submitTeamForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    // Show loading state
    submitButton.disabled = true;
    submitButton.textContent = 'Creating Team...';
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Submit via fetch
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(JSON.stringify(data));
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Redirect to teams page after short delay
            setTimeout(() => {
                window.location.href = '{{ route("teams.index") }}';
            }, 1500);
        } else {
            showMessage(data.message || data.error || 'An error occurred while creating the team', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        try {
            const errorData = JSON.parse(error.message);
            if (errorData.errors) {
                // Show validation errors
                const errorMessages = Object.values(errorData.errors).flat();
                showMessage(errorMessages.join(', '), 'error');
            } else if (errorData.error) {
                showMessage(errorData.error, 'error');
            } else {
                showMessage('An error occurred while creating the team. Please try again.', 'error');
            }
        } catch (parseError) {
            showMessage('An error occurred while creating the team. Please try again.', 'error');
        }
    })
    .finally(() => {
        // Reset button state
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}

function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.ajax-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `ajax-message alert alert-${type === 'success' ? 'success' : 'error'}`;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        background-color: ${type === 'success' ? '#10b981' : '#ef4444'};
        animation: slideInRight 0.3s ease-out;
    `;
    messageDiv.textContent = message;
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => messageDiv.remove(), 300);
        }
    }, 5000);
}
</script>
@endsection