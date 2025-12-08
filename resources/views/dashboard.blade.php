@extends('layouts.app')

@section('title', 'Dashboard - Glyph')

@section('content')
<x-navbar />

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

        <div class="grid grid-cols-2" style="grid-template-columns: 1fr 300px;">
            <div>
                <h1 style="margin-bottom: 24px;">Welcome back, {{ $user->display_name }}!</h1>
                
                <div class="card" style="margin-bottom: 20px; padding: 20px;">
                    <h3 class="card-header" style="margin-bottom: 12px;">Quick Actions</h3>
                    <div class="grid grid-cols-3" style="gap: 10px;">
                        <a href="{{ route('matchmaking.index') }}" class="btn btn-primary" style="padding: 10px 16px; font-size: 14px;">🎯 Find Teammates</a>
                        <a href="{{ route('teams.index') }}" class="btn btn-primary" style="padding: 10px 16px; font-size: 14px;">👥 Browse Teams</a>
                        <a href="{{ route('servers.discover') }}" class="btn btn-primary" style="padding: 10px 16px; font-size: 14px;">🔍 Discover Servers</a>
                        <a href="{{ route('teams.create') }}" class="btn btn-secondary" style="padding: 10px 16px; font-size: 14px;">Create Team</a>
                        <a href="{{ route('server.create') }}" class="btn btn-secondary" style="padding: 10px 16px; font-size: 14px;">Create Server</a>
                        @if(!$user->steam_id)
                            <a href="{{ route('steam.link') }}" class="btn btn-secondary" style="padding: 10px 16px; font-size: 14px;">Link Steam</a>
                        @else
                            <a href="{{ route('profile.show', $user->username) }}" class="btn btn-secondary" style="padding: 10px 16px; font-size: 14px;">View Profile</a>
                        @endif
                    </div>
                </div>

                @if($user->steam_id && isset($recommendations) && $recommendations->isNotEmpty())
                <div class="card" style="margin-bottom: 24px;">
                    <h3 class="card-header">🎮 Recommended Servers</h3>
                    <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Based on your Steam gaming activity</p>
                    
                    @foreach($recommendations as $recommendation)
                        <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #667eea;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0; color: #efeff1; font-weight: 600;">
                                        <a href="{{ route('server.show', $recommendation['server']) }}" style="color: inherit; text-decoration: none;">
                                            {{ $recommendation['server']->name }}
                                        </a>
                                    </h4>
                                    @if($recommendation['server']->description)
                                        <p style="margin: 4px 0; color: #b3b3b5; font-size: 14px;">{{ Str::limit($recommendation['server']->description, 100) }}</p>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: #10b981;">{{ number_format($recommendation['score'], 1) }}% match</div>
                                        <div style="font-size: 12px; color: #71717a;">{{ $recommendation['server']->members->count() }} members</div>
                                    </div>
                                </div>
                            </div>
                            
                            @if(!empty($recommendation['reasons']))
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                    @foreach(array_slice($recommendation['reasons'], 0, 3) as $reason)
                                        <span style="font-size: 12px; background-color: #3f3f46; color: #b3b3b5; padding: 4px 8px; border-radius: 4px;">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($recommendation['server']->tags && $recommendation['server']->tags->count() > 0)
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                                    @foreach($recommendation['server']->tags->take(4) as $tag)
                                        <span style="font-size: 11px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; text-transform: uppercase; font-weight: 600;">{{ $tag->tag_value }}</span>
                                    @endforeach
                                    @if($recommendation['server']->tags->count() > 4)
                                        <span style="font-size: 11px; color: #71717a;">+{{ $recommendation['server']->tags->count() - 4 }} more</span>
                                    @endif
                                </div>
                            @endif

                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('server.show', $recommendation['server']) }}" class="btn btn-sm" style="background-color: #667eea; color: white; padding: 6px 12px; font-size: 12px;">View Server</a>
                                @if(!$recommendation['server']->members->contains(auth()->user()))
                                    <form method="POST" action="{{ route('server.join.direct', $recommendation['server']) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm" style="background-color: #10b981; color: white; padding: 6px 12px; font-size: 12px;">Join Server</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div style="text-align: center; margin-top: 16px;">
                        <a href="{{ route('recommendations.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px; margin-right: 16px;">View all recommendations →</a>
                        <a href="{{ route('servers.discover') }}" style="color: #10b981; text-decoration: none; font-size: 14px;">🔍 Discover all servers →</a>
                    </div>
                </div>
                @endif

                <!-- My Teams Widget -->
                @if(isset($user->teams) && $user->teams && $user->teams->count() > 0)
                <div class="card" style="margin-bottom: 24px;">
                    <h3 class="card-header">👥 My Teams</h3>
                    <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Your active team memberships</p>
                    
                    @foreach($user->teams->take(3) as $team)
                        <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #667eea;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0; color: #efeff1; font-weight: 600;">
                                        <a href="{{ route('teams.show', $team) }}" style="color: inherit; text-decoration: none;">
                                            {{ $team->name }}
                                        </a>
                                    </h4>
                                    <p style="margin: 4px 0; color: #b3b3b5; font-size: 14px;">{{ $team->game_name ?? 'Unknown Game' }}</p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: #10b981;">{{ $team->activeMembers ? $team->activeMembers->count() : 0 }}/{{ $team->max_size ?? $team->max_members ?? 5 }}</div>
                                    <div style="font-size: 12px; color: #71717a;">members</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                @if($team->activeMembers)
                                    @foreach($team->activeMembers->take(4) as $member)
                                        <img src="{{ $member->user->profile->avatar_url }}" alt="{{ $member->user->display_name }}" 
                                             style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #18181b;"
                                             title="{{ $member->user->display_name }}">
                                    @endforeach
                                    @if($team->activeMembers->count() > 4)
                                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #3f3f46; border: 2px solid #18181b; display: flex; align-items: center; justify-content: center; color: #b3b3b5; font-size: 10px; font-weight: 600;">
                                            +{{ $team->activeMembers->count() - 4 }}
                                        </div>
                                    @endif
                                    @if($team->activeMembers->where('user_id', $user->id)->where('role', 'leader')->count() > 0)
                                        <span style="font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">👑 Leader</span>
                                    @endif
                                @endif
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('teams.show', $team) }}" class="btn btn-sm" style="background-color: #667eea; color: white; padding: 6px 12px; font-size: 12px;">Manage Team</a>
                                @if(($team->team_data['recruitment_status'] ?? 'closed') === 'open' && ($team->activeMembers ? $team->activeMembers->count() : 0) < ($team->max_size ?? 5))
                                    <span style="font-size: 12px; background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 6px 8px; border-radius: 4px;">🔍 Recruiting</span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($user->teams->count() > 3)
                        <div style="text-align: center; margin-top: 16px;">
                            <a href="{{ route('teams.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px;">View all {{ $user->teams->count() }} teams →</a>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Active Matchmaking Widget -->
                @if(isset($user->activeMatchmakingRequests) && $user->activeMatchmakingRequests && $user->activeMatchmakingRequests->count() > 0)
                <div class="card" style="margin-bottom: 24px;">
                    <h3 class="card-header">🎯 Active Matchmaking</h3>
                    <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Your current team search requests</p>
                    
                    @foreach($user->activeMatchmakingRequests->take(2) as $request)
                        <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #f59e0b;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0; color: #efeff1; font-weight: 600;">{{ $request->game_name ?? 'Unknown Game' }}</h4>
                                    <p style="margin: 4px 0; color: #b3b3b5; font-size: 14px;">
                                        {{ ucfirst($request->skill_level ?? 'any') }} •
                                        @if($request->preferred_regions && count($request->preferred_regions) > 0)
                                            {{ ucfirst(str_replace('_', ' ', $request->preferred_regions[0])) }}
                                        @else
                                            Any Region
                                        @endif
                                        •
                                        @if($request->preferred_roles && count($request->preferred_roles) > 0)
                                            {{ implode(', ', array_map('ucfirst', array_map(fn($role) => str_replace('_', ' ', $role), $request->preferred_roles))) }}
                                        @else
                                            Any Role
                                        @endif
                                    </p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; padding: 4px 8px; background-color: rgba(102, 126, 234, 0.2); color: #667eea; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <div style="width: 6px; height: 6px; background-color: #667eea; border-radius: 50%; animation: pulse 2s infinite;"></div>
                                    Searching
                                </div>
                            </div>
                            
                            <div style="font-size: 12px; color: #71717a; margin-bottom: 12px;">
                                Created {{ $request->created_at->diffForHumans() }}
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <a href="{{ route('matchmaking.index') }}" class="btn btn-sm" style="background-color: #667eea; color: white; padding: 6px 12px; font-size: 12px;">View Matches</a>
                                <button onclick="cancelMatchmakingRequest({{ $request->id }})" class="btn btn-sm" style="background-color: #ef4444; color: white; padding: 6px 12px; font-size: 12px;">Cancel</button>
                            </div>
                        </div>
                    @endforeach

                    <div style="text-align: center; margin-top: 16px;">
                        <a href="{{ route('matchmaking.index') }}" style="color: #667eea; text-decoration: none; font-size: 14px;">Manage all requests →</a>
                    </div>
                </div>
                @endif

                <!-- Server Goals Widget -->
                @if($user->servers && $user->servers->isNotEmpty())
                    @php
                        $activeGoals = collect();
                        foreach($user->servers as $server) {
                            if($server->goals && $server->goals->where('status', 'active')->count() > 0) {
                                $activeGoals = $activeGoals->merge($server->goals->where('status', 'active'));
                            }
                        }
                    @endphp
                    
                    @if($activeGoals->count() > 0)
                    <div class="card" style="margin-bottom: 24px;">
                        <h3 class="card-header">🏆 Active Community Goals</h3>
                        <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Goals from your servers</p>
                        
                        @foreach($activeGoals->take(2) as $goal)
                            <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #10b981;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0; color: #efeff1; font-weight: 600;">{{ $goal->title }}</h4>
                                        <p style="margin: 4px 0; color: #b3b3b5; font-size: 14px;">{{ $goal->server->name ?? 'Server' }}</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: #10b981;">{{ round(($goal->current_value ?? 0) / $goal->target_value * 100, 1) }}%</div>
                                        <div style="font-size: 12px; color: #71717a;">progress</div>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div style="margin-bottom: 12px;">
                                    <div style="width: 100%; height: 6px; background-color: #3f3f46; border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: {{ $goal->target_value > 0 ? min(($goal->current_value ?? 0) / $goal->target_value * 100, 100) : 0 }}%; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-size: 12px; color: #b3b3b5;">
                                        {{ $goal->participants->count() ?? 0 }} participants • 
                                        {{ $goal->current_value ?? 0 }}/{{ $goal->target_value }}
                                    </div>
                                    <a href="{{ route('server.show', $goal->server) }}#goals" class="btn btn-sm" style="background-color: #10b981; color: white; padding: 4px 8px; font-size: 11px;">View Goal</a>
                                </div>
                            </div>
                        @endforeach

                        <div style="text-align: center; margin-top: 16px;">
                            <a href="{{ route('servers.discover') }}" style="color: #10b981; text-decoration: none; font-size: 14px;">View all community goals →</a>
                        </div>
                    </div>
                    @endif
                @endif

                <div class="card">
                    <h3 class="card-header">Recent Activity</h3>
                    @forelse($recentActivity as $activity)
                        <div style="padding: 12px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 8px;">
                            @if($activity['type'] === 'message')
                                <p><strong>{{ $activity['user'] }}</strong> posted in <span style="color: #a78bfa;">#{{ $activity['channel'] }}</span></p>
                                <p style="font-size: 11px; color: #52525b;">in {{ $activity['server'] }}</p>
                            @elseif($activity['type'] === 'join')
                                <p><strong>{{ $activity['user'] }}</strong> joined <span style="color: #10b981;">{{ $activity['server'] }}</span></p>
                            @elseif($activity['type'] === 'team_join')
                                <p><strong>{{ $activity['user'] }}</strong> joined team <span style="color: #f59e0b;">{{ $activity['server'] }}</span></p>
                                @if($activity['channel'])
                                    <p style="font-size: 11px; color: #52525b;">{{ $activity['channel'] }}</p>
                                @endif
                            @elseif($activity['type'] === 'friend_accept')
                                <p><strong>{{ $activity['user'] }}</strong> <span style="color: #ec4899;">accepted your friend request</span></p>
                            @endif
                            <p style="font-size: 12px; color: #71717a; margin-top: 4px;">{{ $activity['time'] }}</p>
                        </div>
                    @empty
                        <p style="color: #71717a;">No recent activity</p>
                    @endforelse
                </div>
            </div>

            <div class="sidebar">
                <h3 style="margin-bottom: 16px;">Online Friends</h3>
                @forelse($onlineFriends as $friend)
                    <div class="user-card">
                        <img src="{{ $friend->profile->avatar_url }}" alt="{{ $friend->display_name }}" class="user-card-avatar">
                        <div class="user-card-info">
                            <div class="user-card-name">
                                <span class="status-indicator status-online"></span>
                                {{ $friend->display_name }}
                            </div>
                            @if($friend->profile->current_game)
                                <div style="font-size: 12px; color: #10b981;">
                                    Playing {{ $friend->profile->current_game['name'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 24px 16px; background-color: #0e0e10; border-radius: 8px; border: 2px dashed #27272a;">
                        <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                        </div>
                        <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 12px;">No friends online</p>
                        <a href="{{ route('friends.search') }}" style="color: #667eea; font-size: 13px; text-decoration: none;">Find friends</a>
                    </div>
                @endforelse

                @if($user->servers && $user->servers->isNotEmpty())
                    <div style="height: 1px; background-color: #27272a; margin: 24px 0;"></div>
                    <h3 style="margin-bottom: 16px;">Your Servers</h3>
                    @foreach($user->servers as $server)
                        <a href="{{ route('server.show', $server) }}" class="sidebar-link">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                @if($server->icon_url)
                                <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
                                @else
                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">{{ substr($server->name, 0, 2) }}</div>
                                @endif
                                <div style="flex: 1; min-width: 0;">
                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $server->name }}</div>
                                    <div style="font-size: 11px; color: #71717a;">{{ $server->members->count() }} members</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</main>

@push('styles')
<style>
    @keyframes pulse {
        0% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
        100% {
            opacity: 1;
        }
    }
</style>
@endpush

<script>
function cancelMatchmakingRequest(requestId) {
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

// Handle direct join with AJAX for better UX
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

{{-- Steam Reminder Modal - Shows for users without Steam linked --}}
@if($showSteamReminder ?? false)
    <x-steam-reminder-modal :show="true" />
@endif
@endsection