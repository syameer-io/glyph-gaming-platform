@extends('layouts.app')

@section('title', 'Discover Servers - Glyph')

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="discover-container">
        {{-- Page Header - Matches teams/matchmaking/lobbies pattern --}}
        <div class="page-header">
            <h1 class="page-title">Discover Gaming Communities</h1>
        </div>

        {{-- Steam Recommendations CTA - Above filters for visibility --}}
        @if(!auth()->user()->steam_id)
            <div class="discover-steam-cta">
                {{-- Animated Background Layer --}}
                <div class="steam-cta-bg">
                    <div class="steam-cta-mesh"></div>
                    <div class="steam-cta-orb steam-orb-1"></div>
                    <div class="steam-cta-orb steam-orb-2"></div>
                    <div class="steam-cta-orb steam-orb-3"></div>
                    <div class="steam-cta-scanline"></div>
                </div>

                {{-- Content --}}
                <div class="steam-cta-content">
                    {{-- Left: Icon & Status --}}
                    <div class="steam-cta-icon-container">
                        <div class="steam-cta-icon-ring">
                            <div class="steam-cta-icon-ring-inner"></div>
                            <svg class="steam-cta-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a10 10 0 0 1 10 10 10 10 0 0 1-10 10c-4.6 0-8.45-3.08-9.64-7.27l3.83 1.58a2.84 2.84 0 0 0 2.78 2.27c1.56 0 2.83-1.27 2.83-2.83v-.13l3.4-2.43h.08a3.79 3.79 0 1 0-3.79-3.79v.08l-2.42 3.4-.14.01a2.82 2.82 0 0 0-2.79-2.4c-1.25 0-2.32.82-2.69 1.95L2.12 9.9A10 10 0 0 1 12 2m0 18a8 8 0 0 0 7.41-5h.01L12 18.41V20M4.34 14.93l1.94.8a2.84 2.84 0 0 0 1.47 2.86l-1.97-.82A8.04 8.04 0 0 1 4 12c0-.39.03-.77.09-1.15l2.19.91c-.18.38-.28.81-.28 1.26 0 .74.27 1.41.71 1.93l-2.37-.02m11.42-5.68a2.53 2.53 0 1 1 0 5.06 2.53 2.53 0 0 1 0-5.06m0 .94a1.58 1.58 0 1 0 0 3.16 1.58 1.58 0 0 0 0-3.16z"/>
                            </svg>
                            <div class="steam-cta-pulse"></div>
                        </div>
                    </div>

                    {{-- Center: Text Content --}}
                    <div class="steam-cta-text">
                        <div class="steam-cta-label">
                            <span class="steam-cta-label-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                </svg>
                            </span>
                            Unlock Smart Matching
                        </div>
                        <h3 class="steam-cta-title">Get Personalized Recommendations</h3>
                        <p class="steam-cta-description">
                            Connect Steam to discover communities that match your playstyle, games, and skill level
                        </p>
                    </div>

                    {{-- Right: Action --}}
                    <div class="steam-cta-action">
                        <a href="{{ route('steam.link') }}" class="steam-cta-btn">
                            <span class="steam-cta-btn-bg"></span>
                            <span class="steam-cta-btn-content">
                                <svg class="steam-cta-btn-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2a10 10 0 0 1 10 10 10 10 0 0 1-10 10c-4.6 0-8.45-3.08-9.64-7.27l3.83 1.58a2.84 2.84 0 0 0 2.78 2.27c1.56 0 2.83-1.27 2.83-2.83v-.13l3.4-2.43h.08a3.79 3.79 0 1 0-3.79-3.79v.08l-2.42 3.4-.14.01a2.82 2.82 0 0 0-2.79-2.4c-1.25 0-2.32.82-2.69 1.95L2.12 9.9A10 10 0 0 1 12 2m0 18a8 8 0 0 0 7.41-5h.01L12 18.41V20M4.34 14.93l1.94.8a2.84 2.84 0 0 0 1.47 2.86l-1.97-.82A8.04 8.04 0 0 1 4 12c0-.39.03-.77.09-1.15l2.19.91c-.18.38-.28.81-.28 1.26 0 .74.27 1.41.71 1.93l-2.37-.02m11.42-5.68a2.53 2.53 0 1 1 0 5.06 2.53 2.53 0 0 1 0-5.06m0 .94a1.58 1.58 0 1 0 0 3.16 1.58 1.58 0 0 0 0-3.16z"/>
                                </svg>
                                <span>Link Steam Account</span>
                            </span>
                            <span class="steam-cta-btn-shine"></span>
                        </a>
                        <div class="steam-cta-privacy">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Read-only access</span>
                        </div>
                    </div>
                </div>

            </div>
        @endif

        {{-- Filter Controls --}}
        <div class="discover-filter-section">
            <div class="discover-filter-header">
                <h3 class="discover-filter-title">
                    <span class="discover-filter-title-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                    </span>
                    Filter & Search
                </h3>
            </div>

            <form id="filterForm" method="GET" action="{{ route('servers.discover') }}">
                <div class="discover-filter-grid">
                    <div class="discover-filter-group">
                        <label for="search">Search Servers</label>
                        <input type="text" id="search" name="search" placeholder="Server name or description..." value="{{ request('search') }}">
                    </div>

                    <div class="discover-filter-group">
                        <label for="game">Game</label>
                        <select id="game" name="game">
                            <option value="">All Games</option>
                            <option value="cs2" {{ request('game') === 'cs2' ? 'selected' : '' }}>Counter-Strike 2</option>
                            <option value="deep_rock_galactic" {{ request('game') === 'deep_rock_galactic' ? 'selected' : '' }}>Deep Rock Galactic</option>
                            <option value="gtfo" {{ request('game') === 'gtfo' ? 'selected' : '' }}>GTFO</option>
                        </select>
                    </div>

                    <div class="discover-filter-group">
                        <label for="skill_level">Skill Level</label>
                        <select id="skill_level" name="skill_level">
                            <option value="">All Levels</option>
                            <option value="beginner" {{ request('skill_level') === 'beginner' ? 'selected' : '' }}>Beginner</option>
                            <option value="intermediate" {{ request('skill_level') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                            <option value="advanced" {{ request('skill_level') === 'advanced' ? 'selected' : '' }}>Advanced</option>
                            <option value="expert" {{ request('skill_level') === 'expert' ? 'selected' : '' }}>Expert</option>
                            <option value="casual" {{ request('skill_level') === 'casual' ? 'selected' : '' }}>Casual</option>
                            <option value="competitive" {{ request('skill_level') === 'competitive' ? 'selected' : '' }}>Competitive</option>
                        </select>
                    </div>

                    <button type="submit" class="discover-filter-btn discover-filter-btn-primary">Filter</button>
                </div>

                <div class="discover-filter-grid-second">
                    <div class="discover-filter-group">
                        <label for="region">Region</label>
                        <select id="region" name="region">
                            <option value="">All Regions</option>
                            <option value="na_east" {{ request('region') === 'na_east' ? 'selected' : '' }}>North America East</option>
                            <option value="na_west" {{ request('region') === 'na_west' ? 'selected' : '' }}>North America West</option>
                            <option value="eu_west" {{ request('region') === 'eu_west' ? 'selected' : '' }}>Europe West</option>
                            <option value="eu_east" {{ request('region') === 'eu_east' ? 'selected' : '' }}>Europe East</option>
                            <option value="asia" {{ request('region') === 'asia' ? 'selected' : '' }}>Asia</option>
                            <option value="oceania" {{ request('region') === 'oceania' ? 'selected' : '' }}>Oceania</option>
                        </select>
                    </div>

                    <div class="discover-filter-group">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <option value="">All Languages</option>
                            <option value="english" {{ request('language') === 'english' ? 'selected' : '' }}>English</option>
                            <option value="spanish" {{ request('language') === 'spanish' ? 'selected' : '' }}>Spanish</option>
                            <option value="french" {{ request('language') === 'french' ? 'selected' : '' }}>French</option>
                            <option value="german" {{ request('language') === 'german' ? 'selected' : '' }}>German</option>
                            <option value="russian" {{ request('language') === 'russian' ? 'selected' : '' }}>Russian</option>
                            <option value="chinese" {{ request('language') === 'chinese' ? 'selected' : '' }}>Chinese</option>
                        </select>
                    </div>

                    <div class="discover-filter-group">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="recommended" {{ request('sort') === 'recommended' ? 'selected' : '' }}>Recommended</option>
                            <option value="members" {{ request('sort') === 'members' ? 'selected' : '' }}>Most Members</option>
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        </select>
                    </div>

                    <a href="{{ route('servers.discover') }}" class="discover-filter-btn discover-filter-btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        {{-- Results --}}
        <div class="discover-results">
            @if(isset($servers) && $servers->count() > 0)
                @foreach($servers as $server)
                    <div class="discover-server-card">
                        @if(isset($serverRecommendations[$server->id]))
                            <div class="discover-match-badge">
                                {{ number_format($serverRecommendations[$server->id]['score'], 1) }}% Match
                            </div>
                        @endif

                        <div class="discover-server-header" style="{{ isset($serverRecommendations[$server->id]) ? 'padding-right: 120px;' : '' }}">
                            @if($server->icon_url)
                                <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" class="discover-server-icon">
                            @else
                                <div class="discover-server-icon-placeholder">
                                    {{ strtoupper(substr($server->name, 0, 1)) }}
                                </div>
                            @endif

                            <div class="discover-server-info">
                                <h3 class="discover-server-name">
                                    <a href="{{ route('server.show', $server) }}">
                                        {{ $server->name }}
                                    </a>
                                </h3>
                                @if($server->description)
                                    <p class="discover-server-description">{{ Str::limit($server->description, 120) }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="discover-server-body">
                            <div>
                                @if(isset($serverRecommendations[$server->id]) && !empty($serverRecommendations[$server->id]['reasons']))
                                    <h4 class="discover-reasons-title">Recommended because:</h4>
                                    <div class="discover-reasons">
                                        @foreach(array_slice($serverRecommendations[$server->id]['reasons'], 0, 3) as $reason)
                                            <span class="discover-reason-tag">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($server->tags && $server->tags->count() > 0)
                                    <h4 class="discover-tags-title">Server Tags</h4>
                                    <div class="discover-tags">
                                        @foreach($server->tags->take(6) as $tag)
                                            <span class="discover-tag">
                                                {{ ucfirst(str_replace('_', ' ', $tag->tag_value)) }}
                                            </span>
                                        @endforeach
                                        @if($server->tags->count() > 6)
                                            <span class="discover-tag-overflow">+{{ $server->tags->count() - 6 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="discover-server-stats">
                                <h4 class="discover-server-stats-title">Server Info</h4>
                                <div class="discover-stat-row">
                                    <span class="discover-stat-label">Members</span>
                                    <span class="discover-stat-value">{{ $server->members()->count() }}</span>
                                </div>
                                <div class="discover-stat-row">
                                    <span class="discover-stat-label">Channels</span>
                                    <span class="discover-stat-value">{{ $server->channels()->count() }}</span>
                                </div>
                                <div class="discover-stat-row">
                                    <span class="discover-stat-label">Created</span>
                                    <span class="discover-stat-value">{{ $server->created_at->diffForHumans() }}</span>
                                </div>
                                @if($server->tags->where('tag_type', 'region')->first())
                                    <div class="discover-stat-row">
                                        <span class="discover-stat-label">Region</span>
                                        <span class="discover-stat-value">{{ strtoupper($server->tags->where('tag_type', 'region')->first()->tag_value) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="discover-server-actions">
                            <a href="{{ route('server.show', $server) }}" class="btn btn-primary">View Server</a>
                            @if(!$server->members->contains(auth()->user()))
                                <form method="POST" action="{{ route('server.join.direct', $server) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Join Server</button>
                                </form>
                            @else
                                <span class="discover-member-badge">Already a member</span>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Pagination --}}
                @if(method_exists($servers, 'links'))
                    <div style="margin-top: 16px;">
                        {{ $servers->links() }}
                    </div>
                @endif
            @else
                <div class="discover-empty">
                    <h3 class="discover-empty-title">No servers found</h3>
                    <p class="discover-empty-text">Try adjusting your filters or search terms to find gaming communities.</p>
                    <div class="discover-empty-actions">
                        <a href="{{ route('server.create') }}" class="btn btn-primary">Create a Server</a>
                        <a href="{{ route('servers.discover') }}" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</main>

<script>
// Auto-submit form when filters change
document.querySelectorAll('#filterForm select').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Search input debouncing
let searchTimeout;
document.getElementById('search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});

// Handle direct join with AJAX for better UX
document.querySelectorAll('form[action*="join-direct"]').forEach(form => {
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
                button.style.backgroundColor = '#10b981';

                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1000);
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
</script>
@endsection
