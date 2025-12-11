@extends('layouts.app')

@section('title', 'Discover Servers - Glyph')

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="container">
        <div style="margin-bottom: 32px;">
            <h1 style="margin-bottom: 8px;">🔍 Discover Gaming Communities</h1>
            <p style="color: var(--color-text-secondary); font-size: 16px;">Find your perfect gaming community based on your interests and preferences</p>
        </div>

        <!-- Filter Controls -->
        <div class="card" style="margin-bottom: 32px;">
            <h3 class="card-header">Filter & Search</h3>
            
            <form id="filterForm" method="GET" action="{{ route('servers.discover') }}">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin: 0;">
                        <label for="search">Search Servers</label>
                        <input type="text" id="search" name="search" placeholder="Server name or description..." value="{{ request('search') }}">
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
                        <label for="game">Game</label>
                        <select id="game" name="game">
                            <option value="">All Games</option>
                            <option value="cs2" {{ request('game') === 'cs2' ? 'selected' : '' }}>Counter-Strike 2</option>
                            <option value="dota2" {{ request('game') === 'dota2' ? 'selected' : '' }}>Dota 2</option>
                            <option value="warframe" {{ request('game') === 'warframe' ? 'selected' : '' }}>Warframe</option>
                            <option value="apex_legends" {{ request('game') === 'apex_legends' ? 'selected' : '' }}>Apex Legends</option>
                            <option value="rust" {{ request('game') === 'rust' ? 'selected' : '' }}>Rust</option>
                            <option value="pubg" {{ request('game') === 'pubg' ? 'selected' : '' }}>PUBG</option>
                            <option value="rainbow_six_siege" {{ request('game') === 'rainbow_six_siege' ? 'selected' : '' }}>Rainbow Six Siege</option>
                            <option value="fall_guys" {{ request('game') === 'fall_guys' ? 'selected' : '' }}>Fall Guys</option>
                            <option value="valorant" {{ request('game') === 'valorant' ? 'selected' : '' }}>Valorant</option>
                            <option value="overwatch" {{ request('game') === 'overwatch' ? 'selected' : '' }}>Overwatch</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0;">
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
                    
                    <button type="submit" class="btn btn-primary" style="align-self: end;">Filter</button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px;">
                    <div class="form-group" style="margin: 0;">
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
                    
                    <div class="form-group" style="margin: 0;">
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
                    
                    <div class="form-group" style="margin: 0;">
                        <label for="sort">Sort By</label>
                        <select id="sort" name="sort">
                            <option value="recommended" {{ request('sort') === 'recommended' ? 'selected' : '' }}>Recommended</option>
                            <option value="members" {{ request('sort') === 'members' ? 'selected' : '' }}>Most Members</option>
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        </select>
                    </div>
                    
                    <a href="{{ route('servers.discover') }}" class="btn btn-secondary" style="align-self: end;">Clear</a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div style="display: grid; gap: 24px;">
            @if(isset($servers) && $servers->count() > 0)
                @foreach($servers as $server)
                    <div class="card" style="position: relative;">
                        @if(isset($serverRecommendations[$server->id]))
                            <div style="position: absolute; top: 24px; right: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                                {{ number_format($serverRecommendations[$server->id]['score'], 1) }}% Match
                            </div>
                        @endif

                        <div style="margin-bottom: 16px; padding-right: {{ isset($serverRecommendations[$server->id]) ? '120px' : '0' }};">
                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                                @if($server->icon_url)
                                    <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" style="width: 48px; height: 48px; border-radius: 8px;">
                                @else
                                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 20px;">
                                        {{ substr($server->name, 0, 1) }}
                                    </div>
                                @endif
                                
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 4px 0; color: var(--color-text-primary); font-weight: 600; font-size: 20px;">
                                        <a href="{{ route('server.show', $server) }}" style="color: inherit; text-decoration: none;">
                                            {{ $server->name }}
                                        </a>
                                    </h3>
                                    @if($server->description)
                                        <p style="margin: 0; color: var(--color-text-secondary); font-size: 14px; line-height: 1.4;">{{ Str::limit($server->description, 120) }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 20px;">
                            <div>
                                @if(isset($serverRecommendations[$server->id]) && !empty($serverRecommendations[$server->id]['reasons']))
                                    <h4 style="margin: 0 0 8px 0; color: var(--color-text-primary); font-size: 14px; font-weight: 600;">Recommended because:</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px;">
                                        @foreach(array_slice($serverRecommendations[$server->id]['reasons'], 0, 3) as $reason)
                                            <span style="font-size: 12px; background-color: var(--color-bg-primary); color: var(--color-text-secondary); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--color-border-primary);">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($server->tags && $server->tags->count() > 0)
                                    <h4 style="margin: 0 0 8px 0; color: var(--color-text-primary); font-size: 14px; font-weight: 600;">Server Tags</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        @foreach($server->tags->take(6) as $tag)
                                            <span style="font-size: 11px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3px 8px; border-radius: 3px; text-transform: uppercase; font-weight: 600;">
                                                {{ ucfirst(str_replace('_', ' ', $tag->tag_value)) }}
                                            </span>
                                        @endforeach
                                        @if($server->tags->count() > 6)
                                            <span style="font-size: 11px; color: var(--color-text-muted);">+{{ $server->tags->count() - 6 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 16px;">
                                <h4 style="margin: 0 0 12px 0; color: var(--color-text-primary); font-size: 14px; font-weight: 600;">Server Info</h4>
                                <div style="space-y: 8px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: var(--color-text-secondary); font-size: 13px;">Members</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600; font-size: 13px;">{{ $server->members()->count() }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: var(--color-text-secondary); font-size: 13px;">Channels</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600; font-size: 13px;">{{ $server->channels()->count() }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: var(--color-text-secondary); font-size: 13px;">Created</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600; font-size: 13px;">{{ $server->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($server->tags->where('tag_type', 'region')->first())
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: var(--color-text-secondary); font-size: 13px;">Region</span>
                                            <span style="color: var(--color-text-primary); font-weight: 600; font-size: 13px;">{{ strtoupper($server->tags->where('tag_type', 'region')->first()->tag_value) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center;">
                            <a href="{{ route('server.show', $server) }}" class="btn btn-primary">View Server</a>
                            @if(!$server->members->contains(auth()->user()))
                                <form method="POST" action="{{ route('server.join.direct', $server) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Join Server</button>
                                </form>
                            @else
                                <span style="color: #10b981; font-weight: 600; padding: 8px 16px; background-color: rgba(16, 185, 129, 0.1); border-radius: 6px; font-size: 14px;">Already a member</span>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- Pagination -->
                @if(method_exists($servers, 'links'))
                    <div style="margin-top: 32px;">
                        {{ $servers->links() }}
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 80px 20px; background-color: var(--color-surface); border-radius: 12px;">
                    <h3 style="margin-bottom: 16px; color: var(--color-text-primary);">No servers found</h3>
                    <p style="color: var(--color-text-secondary); margin-bottom: 24px;">Try adjusting your filters or search terms to find gaming communities.</p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <a href="{{ route('server.create') }}" class="btn btn-primary">Create a Server</a>
                        <a href="{{ route('servers.discover') }}" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            @endif
        </div>

        @if(!auth()->user()->steam_id)
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 32px; text-align: center; margin-top: 40px; color: white;">
                <h3 style="margin-bottom: 16px;">🎮 Get Personalized Recommendations</h3>
                <p style="margin-bottom: 24px; opacity: 0.9;">Link your Steam account to receive personalized server recommendations based on your gaming activity and preferences.</p>
                <a href="{{ route('steam.link') }}" class="btn" style="background-color: white; color: #667eea; font-weight: 600;">Link Steam Account</a>
            </div>
        @endif
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
                button.textContent = '✓ Joined!';
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