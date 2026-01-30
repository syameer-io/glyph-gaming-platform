@extends('layouts.app')

@section('title', 'Gaming Teams - Glyph')

@section('content')
<x-navbar active-section="teams" />

<main>
    <div class="teams-container">
        {{-- Flash Messages --}}
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
            <h1 class="page-title">Teams</h1>
        </div>

        {{-- CTA Banner for new users --}}
        @if(!auth()->user()->teams()->exists())
        <div class="teams-cta-banner">
            <div class="teams-cta-banner-content">
                <h3>Ready to Form Your Gaming Squad?</h3>
                <p>Create or join teams to compete together, improve your skills, and dominate the competition.</p>
                <a href="{{ route('teams.create') }}" class="btn">Create Your First Team</a>
            </div>
        </div>
        @endif

        {{-- Main Layout --}}
        <div class="teams-main">
            {{-- Sidebar --}}
            <aside class="teams-sidebar">
                {{-- My Teams Section --}}
                <div class="teams-filter-section" data-stagger="0">
                    <h4 class="teams-filter-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        My Teams
                    </h4>
                    @if(auth()->user()->teams()->exists())
                        <div class="teams-my-teams">
                            @foreach(auth()->user()->teams()->limit(3)->get() as $myTeam)
                                <a href="{{ route('teams.show', $myTeam) }}" class="teams-my-team-item">
                                    <span class="teams-my-team-dot"></span>
                                    <span class="teams-my-team-name">{{ $myTeam->name }}</span>
                                </a>
                            @endforeach
                        </div>
                        @if(auth()->user()->teams()->count() > 3)
                            <div class="teams-my-teams-more">
                                <a href="#">View all ({{ auth()->user()->teams()->count() }})</a>
                            </div>
                        @endif
                    @else
                        <div class="teams-my-teams-empty">
                            No teams yet
                        </div>
                    @endif
                </div>

                {{-- Search & Filter Section --}}
                <div class="teams-filter-section" data-stagger="1">
                    <h4 class="teams-filter-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Search & Filter
                    </h4>
                    <div class="teams-filter-group">
                        <label for="search">Team Name</label>
                        <input type="text" id="search" placeholder="Search teams..." onkeyup="filterTeams()">
                    </div>
                </div>

                {{-- Game Filter Section --}}
                <div class="teams-filter-section" data-stagger="2">
                    <h4 class="teams-filter-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <path d="M6 12h4M8 10v4M15 11h2M15 13h2"/>
                        </svg>
                        Game
                    </h4>
                    <div class="teams-filter-group">
                        <select id="game-filter" onchange="filterTeams()">
                            <option value="">All Games</option>
                            <option value="730">Counter-Strike 2</option>
                            <option value="548430">Deep Rock Galactic</option>
                            <option value="493520">GTFO</option>
                        </select>
                    </div>
                </div>

                {{-- Criteria Filter Section --}}
                <div class="teams-filter-section" data-stagger="3">
                    <h4 class="teams-filter-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        Criteria
                    </h4>
                    <div class="teams-filter-group">
                        <label for="skill-filter">Skill Level</label>
                        <select id="skill-filter" onchange="filterTeams()">
                            <option value="">Any Skill</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <div class="teams-filter-group">
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
                    <div class="teams-filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" onchange="filterTeams()">
                            <option value="">Any Status</option>
                            <option value="recruiting">Recruiting</option>
                            <option value="full">Full</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                </div>
            </aside>

            {{-- Content --}}
            <div class="teams-content">
                {{-- Results Section --}}
                <div class="teams-section" data-stagger="0">
                    <div class="teams-section-header">
                        <h3 class="teams-section-title">
                            <span class="teams-section-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            Browse Teams
                        </h3>
                    </div>

                    {{-- Results Header --}}
                    <div class="teams-results-header">
                        <span class="teams-results-count" id="results-count">
                            Showing {{ $teams->count() }} teams
                        </span>
                        <div class="teams-sort-controls">
                            <span class="teams-sort-label">Sort by:</span>
                            <select class="teams-sort-select" id="sort-select" onchange="sortTeams()">
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
                    {{-- Empty State --}}
                    <div class="teams-empty-state">
                        <div class="teams-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3 class="teams-empty-title">No Teams Found</h3>
                        <p class="teams-empty-text">
                            Be the first to create a team for your favorite game!
                        </p>
                        <div class="teams-empty-actions">
                            <a href="{{ route('teams.create') }}" class="btn btn-primary">Create Team</a>
                        </div>
                    </div>
                    @else
                    {{-- Teams Grid --}}
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

        card.style.display = showCard ? 'flex' : 'none';
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
    const spinner = button.querySelector('.teams-loading-spinner');

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
            button.innerHTML = '<span style="color: #10b981;">Joined!</span>';
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
    const spinner = button.querySelector('.teams-loading-spinner');

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
            button.innerHTML = '<span style="color: #f59e0b;">Request Sent</span>';
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
