@extends('layouts.app')

@section('title', 'Teams - Glyph')

@push('styles')
<style>
    .teams-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
    }
    
    .teams-sidebar {
        background-color: var(--color-surface);
        border-radius: 12px;
        padding: 24px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }
    
    .teams-content {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    
    .search-bar {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .search-input {
        flex: 1;
        padding: 12px 16px;
        background-color: var(--color-bg-primary);
        border: 2px solid var(--color-border-primary);
        border-radius: 8px;
        color: var(--color-text-primary);
        font-size: 16px;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #667eea;
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
        background-color: var(--color-bg-primary);
        border: 1px solid var(--color-border-primary);
        border-radius: 6px;
        color: var(--color-text-primary);
        font-size: 14px;
    }
    
    .teams-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .teams-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .team-card {
        background-color: var(--color-surface);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid var(--color-border-primary);
        transition: all 0.2s;
        position: relative;
    }
    
    .team-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
    
    .team-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }
    
    .team-info {
        flex: 1;
    }
    
    .team-name {
        font-size: 20px;
        font-weight: 600;
        color: var(--color-text-primary);
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .team-game {
        font-size: 14px;
        color: var(--color-text-secondary);
        margin-bottom: 8px;
    }
    
    .team-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-recruiting {
        background-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }
    
    .status-full {
        background-color: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    
    .status-private {
        background-color: rgba(156, 163, 175, 0.2);
        color: #9ca3af;
    }
    
    .team-stats {
        text-align: right;
        min-width: 120px;
    }
    
    .skill-level {
        font-size: 14px;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 4px;
    }
    
    .member-count {
        font-size: 12px;
        color: var(--color-text-secondary);
    }

    .team-members {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .member-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--color-border-primary);
        transition: all 0.2s;
    }
    
    .member-avatar:hover {
        border-color: #667eea;
        transform: scale(1.1);
    }
    
    .member-placeholder {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: var(--color-bg-primary);
        border: 2px dashed var(--color-border-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text-muted);
        font-size: 14px;
        font-weight: 600;
    }

    .team-description {
        color: var(--color-text-secondary);
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
        max-height: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
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
        font-weight: 500;
    }
    
    .team-tag.skill {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .team-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    
    .results-count {
        color: var(--color-text-secondary);
        font-size: 14px;
    }

    .sort-controls {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .sort-label {
        color: var(--color-text-secondary);
        font-size: 14px;
    }

    .sort-select {
        padding: 6px 10px;
        background-color: var(--color-bg-primary);
        border: 1px solid var(--color-border-primary);
        border-radius: 6px;
        color: var(--color-text-primary);
        font-size: 14px;
    }
    
    .create-team-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        margin-bottom: 24px;
    }
    
    .create-team-banner h3 {
        color: white;
        margin-bottom: 8px;
    }
    
    .create-team-banner p {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 16px;
    }
    
    .create-team-banner .btn {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .create-team-banner .btn:hover {
        background-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid var(--color-border-primary);
        border-radius: 50%;
        border-top-color: #667eea;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .teams-container {
            grid-template-columns: 1fr;
        }
        
        .teams-sidebar {
            position: static;
        }
        
        .teams-grid {
            grid-template-columns: 1fr;
        }
        
        .search-bar {
            flex-direction: column;
        }
        
        .results-header {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }
        
        .team-header {
            flex-direction: column;
            gap: 12px;
        }
        
        .team-stats {
            position: static;
            text-align: left;
        }
    }
</style>
@endpush

@section('content')
<x-navbar active-section="teams" />

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
            <h1>👥 Gaming Teams</h1>
            <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
        </div>

        @if(!auth()->user()->teams()->exists())
        <div class="create-team-banner">
            <h3>🚀 Ready to Form Your Gaming Squad?</h3>
            <p>Create or join teams to compete together, improve your skills, and dominate the competition.</p>
            <a href="{{ route('teams.create') }}" class="btn">Create Your First Team</a>
        </div>
        @endif

        <div class="teams-container">
            <!-- Sidebar Filters -->
            <div class="teams-sidebar">
                <div class="filter-section">
                    <h4>Search & Filter</h4>
                    <div class="filter-group">
                        <label for="search">Team Name</label>
                        <input type="text" id="search" placeholder="Search teams..." onkeyup="filterTeams()">
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Game</h4>
                    <div class="filter-group">
                        <select id="game-filter" onchange="filterTeams()">
                            <option value="">All Games</option>
                            <option value="730">Counter-Strike 2</option>
                            <option value="548430">Deep Rock Galactic</option>
                            <option value="493520">GTFO</option>
                        </select>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>Criteria</h4>
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
                            <option value="oceania">Oceania</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" onchange="filterTeams()">
                            <option value="">Any Status</option>
                            <option value="recruiting">Recruiting</option>
                            <option value="full">Full</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                </div>

                <div class="filter-section">
                    <h4>My Teams</h4>
                    @if(auth()->user()->teams()->exists())
                        @foreach(auth()->user()->teams()->limit(3)->get() as $myTeam)
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <div style="width: 8px; height: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%;"></div>
                                <a href="{{ route('teams.show', $myTeam) }}" style="color: var(--color-text-secondary); text-decoration: none; font-size: 14px; flex: 1;">{{ $myTeam->name }}</a>
                            </div>
                        @endforeach
                        @if(auth()->user()->teams()->count() > 3)
                            <div style="text-align: center; margin-top: 12px;">
                                <a href="#" style="color: #667eea; font-size: 12px; text-decoration: none;">View all ({{ auth()->user()->teams()->count() }})</a>
                            </div>
                        @endif
                    @else
                        <div style="text-align: center; padding: 16px; color: var(--color-text-muted); font-size: 14px;">
                            No teams yet
                        </div>
                    @endif
                </div>
            </div>

            <!-- Main Content -->
            <div class="teams-content">
                <div class="results-header">
                    <div class="results-count" id="results-count">
                        Showing {{ $teams->count() }} teams
                    </div>
                    <div class="sort-controls">
                        <span class="sort-label">Sort by:</span>
                        <select class="sort-select" id="sort-select" onchange="sortTeams()">
                            <option value="created_at_desc">Newest First</option>
                            <option value="created_at_asc">Oldest First</option>
                            <option value="members_desc">Most Members</option>
                            <option value="members_asc">Fewest Members</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                        </select>
                    </div>
                </div>

                @if($teams->isEmpty())
                <!-- Empty State -->
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
                    <h3 style="margin-bottom: 12px; color: var(--color-text-primary);">No Teams Found</h3>
                    <p style="color: var(--color-text-secondary); margin-bottom: 24px;">
                        Be the first to create a team for your favorite game!
                    </p>
                    <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
                </div>
                @else
                <div class="teams-grid" id="teams-grid">
                    @foreach($teams as $team)
                        <x-team-card
                            :team="$team"
                            context="browse"
                        />
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</main>

<script>
let allTeams = [];

document.addEventListener('DOMContentLoaded', function() {
    // Store all teams for filtering
    allTeams = Array.from(document.querySelectorAll('.team-card'));
});

function filterTeams() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const gameFilter = document.getElementById('game-filter').value;
    const skillFilter = document.getElementById('skill-filter').value;
    const regionFilter = document.getElementById('region-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    let visibleCount = 0;
    
    allTeams.forEach(card => {
        let showCard = true;
        
        // Search filter
        if (searchTerm && !card.dataset.name.includes(searchTerm)) {
            showCard = false;
        }
        
        // Game filter
        if (gameFilter && card.dataset.game !== gameFilter) {
            showCard = false;
        }
        
        // Skill filter
        if (skillFilter && card.dataset.skill !== skillFilter) {
            showCard = false;
        }
        
        // Region filter
        if (regionFilter && card.dataset.region !== regionFilter) {
            showCard = false;
        }
        
        // Status filter
        if (statusFilter && card.dataset.status !== statusFilter) {
            showCard = false;
        }
        
        card.style.display = showCard ? 'block' : 'none';
        if (showCard) visibleCount++;
    });
    
    // Update results count
    document.getElementById('results-count').textContent = `Showing ${visibleCount} teams`;
}

function sortTeams() {
    const sortValue = document.getElementById('sort-select').value;
    const [sortBy, sortOrder] = sortValue.split('_');
    
    const sortedTeams = [...allTeams].sort((a, b) => {
        let aValue, bValue;
        
        switch (sortBy) {
            case 'created':
                aValue = parseInt(a.dataset.created);
                bValue = parseInt(b.dataset.created);
                break;
            case 'members':
                aValue = parseInt(a.dataset.members);
                bValue = parseInt(b.dataset.members);
                break;
            case 'name':
                aValue = a.dataset.name;
                bValue = b.dataset.name;
                break;
        }
        
        if (sortOrder === 'desc') {
            return aValue < bValue ? 1 : -1;
        } else {
            return aValue > bValue ? 1 : -1;
        }
    });
    
    const container = document.getElementById('teams-grid');
    sortedTeams.forEach(team => container.appendChild(team));
}

// Direct join for OPEN teams
function joinTeamDirect(teamId, event) {
    // Get the button element
    const button = event ? event.currentTarget : window.event.srcElement;
    if (!button) {
        console.error('joinTeamDirect: Could not find button element');
        return;
    }

    const btnText = button.querySelector('.btn-text');
    const spinner = button.querySelector('.loading-spinner');

    // Show loading state
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';
    button.disabled = true;

    fetch(`{{ url('/teams') }}/${teamId}/join-direct`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button to show success state
            button.innerHTML = '<span style="color: #10b981;">✓ Joined!</span>';
            button.style.background = 'rgba(16, 185, 129, 0.2)';
            button.style.cursor = 'default';

            // Show success toast
            if (typeof Toast !== 'undefined') {
                Toast.success(data.message || 'Successfully joined the team!');
            }

            // Reload after a moment to show the toast
            setTimeout(() => location.reload(), 1500);
        } else {
            // Show error and restore button
            if (typeof Toast !== 'undefined') {
                Toast.error(data.error || data.message || 'Error joining team');
            } else {
                alert(data.error || data.message || 'Error joining team');
            }

            if (btnText) btnText.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);

        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred while joining the team. Please try again.');
        } else {
            alert('Error joining team');
        }

        if (btnText) btnText.style.display = 'inline';
        if (spinner) spinner.style.display = 'none';
        button.disabled = false;
    });
}

// Request to join for CLOSED teams
function requestToJoin(teamId, event) {
    // Get the button element
    const button = event ? event.currentTarget : window.event.srcElement;
    if (!button) {
        console.error('requestToJoin: Could not find button element');
        return;
    }

    const btnText = button.querySelector('.btn-text');
    const spinner = button.querySelector('.loading-spinner');

    // Show loading state
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';
    button.disabled = true;

    // Submit join request to the join-requests endpoint
    fetch(`{{ url('/teams') }}/${teamId}/join-requests`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ message: '' }) // Optional message
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button to show pending state
            button.innerHTML = '<span style="color: #f59e0b;">⏳ Request Sent</span>';
            button.style.background = 'rgba(245, 158, 11, 0.2)';
            button.style.cursor = 'default';

            // Show success toast
            if (typeof Toast !== 'undefined') {
                Toast.success(data.message || 'Join request sent! The team leader will review your request.');
            }

            // Reload after a moment to show the toast
            setTimeout(() => location.reload(), 1500);
        } else {
            // Show error and restore button
            if (typeof Toast !== 'undefined') {
                Toast.error(data.error || data.message || 'Error sending join request');
            } else {
                alert(data.error || data.message || 'Error sending join request');
            }

            if (btnText) btnText.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);

        if (typeof Toast !== 'undefined') {
            Toast.error('An error occurred while sending join request. Please try again.');
        } else {
            alert('Error sending join request');
        }

        if (btnText) btnText.style.display = 'inline';
        if (spinner) spinner.style.display = 'none';
        button.disabled = false;
    });
}

// Real-time search
document.getElementById('search').addEventListener('input', filterTeams);
</script>
@endsection