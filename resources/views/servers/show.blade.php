@extends('layouts.app')

@section('title', $server->name . ' - Glyph')

@push('head')
    <!-- Agora App ID for Voice Chat (Public - Safe to expose) -->
    <meta name="agora-app-id" content="{{ config('services.agora.app_id') }}">
@endpush

@push('styles')
<style>
    /* Hide x-cloak elements until Alpine.js loads */
    [x-cloak] { display: none !important; }

    .kebab-menu {
        position: relative;
        display: inline-block;
    }

    .kebab-button {
        background: none;
        border: none;
        color: #71717a;
        font-size: 16px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .kebab-button:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }

    .kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        min-width: 120px;
        z-index: 1000;
        display: none;
    }

    .kebab-dropdown.active {
        display: block;
    }

    .kebab-option {
        display: block;
        width: 100%;
        padding: 8px 12px;
        background: none;
        border: none;
        color: #ffffff;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .kebab-option:hover {
        background-color: #3f3f46;
    }

    .kebab-option:first-child {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .kebab-option:last-child {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .kebab-option.danger {
        color: #f87171;
    }

    .kebab-option.danger:hover {
        background-color: #dc2626;
        color: #ffffff;
    }

    .back-button:hover {
        background-color: #3f3f46 !important;
        color: #ffffff !important;
    }

    /* Voice Chat Styles */
    .voice-channel-link:hover {
        background-color: #3f3f46;
        border-radius: 4px;
        padding: 4px 8px;
        margin-left: -8px;
        margin-right: -8px;
    }

    .voice-user-count {
        transition: all 0.2s ease;
    }

    .voice-channel-link.active {
        background-color: #667eea;
        color: #ffffff;
        border-radius: 4px;
        padding: 4px 8px;
        margin-left: -8px;
        margin-right: -8px;
    }

    /* Voice Controls Panel */
    #voice-controls-panel {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    #mute-toggle-btn:hover {
        background-color: #52525b !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    #mute-toggle-btn.muted {
        background-color: #ef4444 !important;
        color: white !important;
    }

    #disconnect-btn:hover {
        background-color: #dc2626 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Network Quality Indicators */
    .network-quality-excellent {
        background-color: #10b981 !important;
    }

    .network-quality-good {
        background-color: #f59e0b !important;
    }

    .network-quality-poor {
        background-color: #ef4444 !important;
    }

    /* Voice Speaking Animation */
    @keyframes pulse-ring {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        50% {
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.3);
        }
        100% {
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }
    }

    .voice-speaking-indicator {
        animation: pulse-ring 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .member-avatar.speaking {
        border: 2px solid #10b981;
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.5);
    }

    /* In Voice Badge Animation */
    .in-voice-badge {
        animation: fadeInScale 0.3s ease-out;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Connection Quality Pulse */
    #voice-connection-indicator.connecting {
        background-color: #f59e0b !important;
        animation: pulse 1s ease-in-out infinite;
    }

    #voice-connection-indicator.disconnected {
        background-color: #ef4444 !important;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
</style>
@endpush

@push('scripts')
    @vite(['resources/js/voice-chat.js'])
@endpush

@section('content')
<div style="display: flex; height: 100vh;">
    <!-- Server Sidebar -->
    <div style="width: 240px; background-color: #18181b; display: flex; flex-direction: column;">
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46;">
            <h3 style="font-size: 16px; margin: 0;">{{ $server->name }}</h3>
            @if(auth()->user()->isServerAdmin($server->id))
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <span style="font-size: 12px; color: #71717a;">Invite Code: </span>
                        <code style="font-size: 12px; background-color: #0e0e10; padding: 2px 6px; border-radius: 4px;">{{ $server->invite_code }}</code>
                    </div>
                    <a href="{{ route('server.admin.settings', $server) }}" class="btn btn-secondary btn-sm">
                        Server Settings
                    </a>
                </div>
            @endif
        </div>

        <!-- Channels List - Phase 1 UI Improvement -->
        <div class="sidebar-channels-container" style="flex: 1; overflow-y: auto; padding: var(--sidebar-padding-x, 8px);">
            {{-- Text Channels Category --}}
            <x-channel-category
                name="TEXT CHANNELS"
                :serverId="$server->id"
                :canAddChannel="auth()->user()->isServerAdmin($server->id)"
                :addChannelRoute="route('server.admin.settings', $server) . '#channels'"
                type="text"
            >
                @foreach($server->channels->where('type', 'text') as $ch)
                    <x-text-channel-item
                        :channel="$ch"
                        :server="$server"
                        :active="isset($channel) && $channel->id === $ch->id"
                        :canEdit="auth()->user()->isServerAdmin($server->id)"
                        :editRoute="route('server.admin.settings', $server) . '#channels'"
                    />
                @endforeach
            </x-channel-category>

            {{-- Voice Channels Category --}}
            <x-channel-category
                name="VOICE CHANNELS"
                :serverId="$server->id"
                :canAddChannel="auth()->user()->isServerAdmin($server->id)"
                :addChannelRoute="route('server.admin.settings', $server) . '#channels'"
                type="voice"
            >
                @foreach($server->channels->where('type', 'voice') as $ch)
                    <x-voice-channel-item
                        :channel="$ch"
                        :server="$server"
                        :canEdit="auth()->user()->isServerAdmin($server->id)"
                        :editRoute="route('server.admin.settings', $server) . '#channels'"
                    />
                @endforeach
            </x-channel-category>
        </div>

        <!-- User Section -->
        <div style="padding: 16px; border-top: 1px solid #3f3f46; background-color: #0e0e10;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" 
                     style="width: 32px; height: 32px; border-radius: 50%;">
                <div style="flex: 1;">
                    <div style="font-size: 14px; font-weight: 600;">{{ auth()->user()->display_name }}</div>
                    <div style="font-size: 12px; color: #71717a;">{{ auth()->user()->username }}</div>
                </div>
                <div class="kebab-menu">
                    <button class="kebab-button" onclick="toggleKebabMenu('user-settings')" style="padding: 4px;">⚙️</button>
                    <div class="kebab-dropdown" id="kebab-user-settings">
                        @if($server->creator_id === auth()->id())
                            <form method="POST" action="{{ route('server.destroy', $server) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kebab-option danger" onclick="return confirm('Are you sure you want to delete this server? This action cannot be undone and will delete all channels, messages, and remove all members.')">
                                    Delete Server
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('server.leave', $server) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="kebab-option danger" onclick="return confirm('Are you sure you want to leave this server?')">
                                    Leave Server
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div style="flex: 1; display: flex; flex-direction: column; background-color: #0e0e10;">
        <!-- Header Bar -->
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46; background-color: #18181b; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="{{ route('dashboard') }}" class="back-button" style="display: flex; align-items: center; gap: 8px; color: #71717a; text-decoration: none; padding: 8px 12px; border-radius: 6px; transition: background-color 0.2s, color 0.2s;">
                    <span style="font-size: 16px;">←</span>
                    <span style="font-size: 14px; font-weight: 500;">Dashboard</span>
                </a>
                <span style="color: #3f3f46; font-size: 16px;">|</span>
                <h3 style="margin: 0; font-size: 18px; color: #efeff1;">{{ $server->name }}</h3>
            </div>
        </div>
        
        <div style="flex: 1; overflow-y: auto; padding: 16px;">
            <!-- Welcome Section -->
            <div style="text-align: center; margin-bottom: 32px; padding: 24px; background-color: #18181b; border-radius: 12px;">
                <h2 style="margin-bottom: 16px;">Welcome to {{ $server->name }}!</h2>
                <p style="color: #b3b3b5; margin-bottom: 24px;">{{ $server->description ?: 'This is the beginning of your server.' }}</p>
                @if($defaultChannel)
                    <a href="{{ route('channel.show', [$server, $defaultChannel]) }}" class="btn btn-primary">
                        Open #{{ $defaultChannel->name }}
                    </a>
                @endif
            </div>

            <!-- Active Goals Section -->
            @if($activeGoals && $activeGoals->count() > 0)
                <div id="goals-section" style="margin-bottom: 32px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: #efeff1; font-size: 20px; font-weight: 600;">🏆 Active Community Goals</h3>
                        <span style="color: #71717a; font-size: 14px;">{{ $activeGoals->count() }} active {{ $activeGoals->count() === 1 ? 'goal' : 'goals' }}</span>
                    </div>

                    <div style="display: grid; gap: 16px;">
                        @foreach($activeGoals as $goal)
                            <div class="goal-card" data-goal-id="{{ $goal->id }}" style="background-color: #18181b; border-radius: 12px; padding: 20px; border-left: 4px solid #667eea;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 8px 0; color: #efeff1; font-size: 18px; font-weight: 600;">{{ $goal->title }}</h4>
                                        <p style="margin: 0 0 12px 0; color: #b3b3b5; font-size: 14px; line-height: 1.5;">{{ $goal->description }}</p>
                                        
                                        <!-- Goal Meta Information -->
                                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                                            <span style="font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; font-weight: 600;">
                                                {{ ucfirst($goal->goal_type) }}
                                            </span>
                                            @if($goal->game_name)
                                                <span style="font-size: 12px; background-color: #3f3f46; color: #b3b3b5; padding: 4px 8px; border-radius: 6px;">
                                                    🎮 {{ $goal->game_name }}
                                                </span>
                                            @endif
                                            @if($goal->deadline)
                                                <span style="font-size: 12px; color: #f59e0b; background-color: rgba(245, 158, 11, 0.1); padding: 4px 8px; border-radius: 6px;">
                                                    📅 Due {{ \Carbon\Carbon::parse($goal->deadline)->diffForHumans() }}
                                                </span>
                                            @endif
                                            <span style="font-size: 12px; background-color: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 8px; border-radius: 6px;">
                                                💎 {{ ucfirst($goal->difficulty ?? 'Medium') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Join/Leave Button -->
                                    <div style="margin-left: 16px;">
                                        @php
                                            $userParticipant = $goal->participants->where('user_id', auth()->id())->where('participation_status', 'active')->first();
                                        @endphp
                                        
                                        @if($userParticipant)
                                            <button onclick="leaveGoal({{ $goal->id }})" class="btn-leave-goal" style="background-color: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                                ✓ Joined
                                            </button>
                                        @else
                                            <button onclick="joinGoal({{ $goal->id }})" class="btn-join-goal" style="background-color: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                                Join Goal
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Progress Section -->
                                <div style="margin-bottom: 16px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-size: 14px; color: #b3b3b5; font-weight: 500;">Progress</span>
                                        <span class="progress-text" style="font-size: 14px; color: #efeff1; font-weight: 600;">
                                            {{ $goal->current_progress ?? 0 }} / {{ $goal->target_value }}
                                            ({{ $goal->target_value > 0 ? round(($goal->current_progress ?? 0) / $goal->target_value * 100, 1) : 0 }}%)
                                        </span>
                                    </div>
                                    <div style="width: 100%; height: 8px; background-color: #3f3f46; border-radius: 4px; overflow: hidden;">
                                        <div class="progress-bar" style="height: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: {{ $goal->target_value > 0 ? min(($goal->current_progress ?? 0) / $goal->target_value * 100, 100) : 0 }}%; transition: width 0.8s ease;"></div>
                                    </div>
                                </div>

                                <!-- Participants Section -->
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="participant-count" style="font-size: 14px; color: #b3b3b5;">
                                            👥 {{ $goal->participants->where('participation_status', 'active')->count() }} participants
                                        </span>
                                        <div class="participants-list" style="display: flex;">
                                            @foreach($goal->participants->where('participation_status', 'active')->take(5) as $participant)
                                                <img src="{{ $participant->user->profile->avatar_url }}" alt="{{ $participant->user->display_name }}" 
                                                     style="width: 24px; height: 24px; border-radius: 50%; margin-left: -4px; border: 2px solid #18181b;"
                                                     title="{{ $participant->user->display_name }} - {{ round($participant->contribution_percentage, 1) }}% contribution">
                                            @endforeach
                                            @if($goal->participants->where('participation_status', 'active')->count() > 5)
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #3f3f46; margin-left: -4px; border: 2px solid #18181b; display: flex; align-items: center; justify-content: center; color: #b3b3b5; font-size: 10px; font-weight: 600;">
                                                    +{{ $goal->participants->where('participation_status', 'active')->count() - 5 }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="showGoalDetails({{ $goal->id }})" class="btn btn-sm" style="background-color: #3f3f46; color: #efeff1; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">
                                            View Details
                                        </button>
                                        @if($userParticipant)
                                            <button onclick="updateProgress({{ $goal->id }})" class="btn btn-sm" style="background-color: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">
                                                Update Progress
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div style="text-align: center; padding: 40px; background-color: #18181b; border-radius: 12px; margin-bottom: 32px; border: 2px dashed #3f3f46;">
                    <div style="font-size: 48px; margin-bottom: 16px;">🎯</div>
                    <h3 style="color: #71717a; margin-bottom: 8px; font-weight: 600;">No Active Goals</h3>
                    <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 20px;">This server doesn't have any community goals yet.</p>
                    @if(auth()->user()->isServerAdmin($server->id))
                        <a href="{{ route('server.admin.settings', $server) }}#goals" class="btn btn-primary btn-sm">
                            Create Your First Goal
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Members Sidebar - Phase 2 Enhanced -->
    <div class="member-list-container" style="width: 240px; background-color: #18181b; padding: 8px; overflow-y: auto;">
        <div class="member-list-header">
            Members — {{ $server->members->count() }}
        </div>

        @php
            // Group members by their highest role
            $membersByRole = collect();

            foreach($server->members as $member) {
                $highestRole = $member->roles()
                    ->wherePivot('server_id', $server->id)
                    ->orderBy('position', 'desc')
                    ->first();

                if ($highestRole) {
                    $roleKey = $highestRole->name;
                    if (!$membersByRole->has($roleKey)) {
                        $membersByRole->put($roleKey, collect([
                            'role' => $highestRole,
                            'members' => collect()
                        ]));
                    }
                    $membersByRole->get($roleKey)['members']->push($member);
                } else {
                    // Members with no custom roles go to default "Member" role
                    $defaultRole = $server->roles()->where('name', 'Member')->first();
                    if ($defaultRole) {
                        $roleKey = 'Member';
                        if (!$membersByRole->has($roleKey)) {
                            $membersByRole->put($roleKey, collect([
                                'role' => $defaultRole,
                                'members' => collect()
                            ]));
                        }
                        $membersByRole->get($roleKey)['members']->push($member);
                    }
                }
            }

            // Get roles for proper ordering
            $allRoles = $server->roles()->orderBy('position', 'desc')->get();
        @endphp

        @foreach($allRoles as $role)
            @if($membersByRole->has($role->name) && $membersByRole->get($role->name)['members']->count() > 0)
                @php
                    $roleData = $membersByRole->get($role->name);
                    $roleMembers = $roleData['members'];
                @endphp

                {{-- Role Header Component --}}
                <x-role-header
                    :roleName="$role->name"
                    :count="$roleMembers->count()"
                    :serverId="$server->id"
                    :roleColor="$role->color ?? '#96989d'"
                >
                    @foreach($roleMembers as $member)
                        {{-- Member Item Component --}}
                        <x-member-item
                            :member="$member"
                            :server="$server"
                            :role="$role"
                            :isOwner="$server->creator_id === $member->id"
                        />
                    @endforeach
                </x-role-header>
            @endif
        @endforeach
    </div>
</div>

<!-- Voice Controls Panel (Fixed Bottom Bar) -->
<div id="voice-controls-panel" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background-color: #18181b; border-top: 2px solid #3f3f46; padding: 12px 20px; z-index: 1000; box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.3);">
    <div style="max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981;" id="voice-connection-indicator"></div>
                <div>
                    <div style="font-size: 12px; color: #71717a; font-weight: 600;">VOICE CONNECTED</div>
                    <div style="font-size: 14px; color: #efeff1; font-weight: 600;" id="voice-channel-name">Voice Channel</div>
                </div>
            </div>
            <div id="voice-network-quality" style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; background-color: #0e0e10; border-radius: 6px;">
                <div style="width: 6px; height: 6px; border-radius: 50%; background-color: #10b981;" id="network-quality-indicator"></div>
                <span style="font-size: 12px; color: #71717a;">Connection: <span id="network-quality-text" style="color: #10b981; font-weight: 600;">Excellent</span></span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 12px;">
            <button id="mute-toggle-btn" onclick="toggleMute()" style="background-color: #3f3f46; color: #efeff1; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <span id="mute-icon">🎤</span>
                <span id="mute-text">Mute</span>
            </button>
            <button id="disconnect-btn" onclick="disconnectVoice()" style="background-color: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <span>📞</span>
                <span>Disconnect</span>
            </button>
        </div>
    </div>
</div>

<script>
function toggleKebabMenu(menuId) {
    // Close all other kebab menus
    document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'kebab-' + menuId) {
            dropdown.classList.remove('active');
        }
    });
    
    // Toggle the clicked menu
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.toggle('active');
}

function closeKebabMenu(menuId) {
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.remove('active');
}

// Close kebab menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.kebab-menu')) {
        document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});

// Goal functionality
function joinGoal(goalId) {
    const button = document.querySelector(`[onclick="joinGoal(${goalId})"]`);
    const originalText = button.innerHTML;
    
    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '⏳ Joining...';
    button.style.backgroundColor = '#6b7280';
    
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/join`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Join goal response:', data); // Debug logging
        
        if (data.success) {
            try {
                // Update button to "Joined" state
                button.innerHTML = '✓ Joined';
                button.style.backgroundColor = '#ef4444';
                button.onclick = () => leaveGoal(goalId);
                
                // Update participant count
                const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
                if (goalCard) {
                    const participantCount = goalCard.querySelector('.participant-count');
                    if (participantCount) {
                        const currentCount = parseInt(participantCount.textContent.match(/\d+/)[0]);
                        participantCount.textContent = participantCount.textContent.replace(/\d+/, currentCount + 1);
                    }
                    
                    // Add user avatar to participants list
                    const participantsList = goalCard.querySelector('.participants-list');
                    if (participantsList) {
                        const userAvatar = document.createElement('img');
                        userAvatar.src = '{{ auth()->user()->profile->avatar_url }}';
                        userAvatar.alt = '{{ auth()->user()->display_name }}';
                        userAvatar.style.cssText = 'width: 24px; height: 24px; border-radius: 50%; margin-left: -4px; border: 2px solid #18181b;';
                        userAvatar.title = '{{ auth()->user()->display_name }} - Just joined!';
                        participantsList.appendChild(userAvatar);
                    }
                }
                
                // Show success message
                showNotification('Successfully joined the goal!', 'success');
            } catch (domError) {
                console.error('DOM manipulation error:', domError);
                // Still show success message even if DOM update fails
                showNotification('Successfully joined the goal!', 'success');
            }
        } else {
            // Reset button on error
            button.disabled = false;
            button.innerHTML = originalText;
            button.style.backgroundColor = '#10b981';
            showNotification(data.error || data.message || 'Failed to join goal', 'error');
        }
    })
    .catch(error => {
        console.error('Network or parsing error:', error);
        button.disabled = false;
        button.innerHTML = originalText;
        button.style.backgroundColor = '#10b981';
        showNotification('An error occurred while joining the goal: ' + error.message, 'error');
    });
}

function leaveGoal(goalId) {
    if (!confirm('Are you sure you want to leave this goal? Your progress will be preserved.')) {
        return;
    }
    
    const button = document.querySelector(`[onclick="leaveGoal(${goalId})"]`);
    const originalText = button.innerHTML;
    
    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '⏳ Leaving...';
    button.style.backgroundColor = '#6b7280';
    
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/leave`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button to "Join" state
            button.innerHTML = 'Join Goal';
            button.style.backgroundColor = '#10b981';
            button.onclick = () => joinGoal(goalId);
            
            // Update participant count
            const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
            const participantCount = goalCard.querySelector('.participant-count');
            const currentCount = parseInt(participantCount.textContent.match(/\d+/)[0]);
            participantCount.textContent = participantCount.textContent.replace(/\d+/, Math.max(0, currentCount - 1));
            
            // Remove user avatar from participants list (simplified - would need more complex logic for exact removal)
            
            // Show success message
            showNotification('Successfully left the goal', 'success');
        } else {
            // Reset button on error
            button.disabled = false;
            button.innerHTML = originalText;
            button.style.backgroundColor = '#ef4444';
            showNotification(data.error || 'Failed to leave goal', 'error');
        }
    })
    .catch(error => {
        console.error('Error leaving goal:', error);
        button.disabled = false;
        button.innerHTML = originalText;
        button.style.backgroundColor = '#ef4444';
        showNotification('An error occurred while leaving the goal', 'error');
    });
}

function showGoalDetails(goalId) {
    // Redirect to the goal detail page
    window.location.href = `{{ url('servers/' . $server->id . '/goals') }}/${goalId}`;
}

function updateProgress(goalId) {
    const progressValue = prompt('Enter your current progress value:');
    
    if (progressValue === null || progressValue === '') {
        return; // User cancelled
    }
    
    if (isNaN(progressValue) || progressValue < 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/my-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            progress: parseInt(progressValue)
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Update progress response:', data); // Debug logging
        
        if (data.success) {
            try {
                // Update progress bar and text
                const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
                if (goalCard) {
                    const progressBar = goalCard.querySelector('.progress-bar');
                    const progressText = goalCard.querySelector('.progress-text');
                    
                    if (data.goal) {
                        const newProgress = data.goal.current_progress;
                        const targetValue = data.goal.target_value;
                        const percentage = Math.round((newProgress / targetValue) * 100 * 10) / 10;
                        
                        if (progressBar) {
                            progressBar.style.width = Math.min(percentage, 100) + '%';
                        }
                        if (progressText) {
                            progressText.textContent = `${newProgress} / ${targetValue} (${percentage}%)`;
                        }
                    }
                }
                
                showNotification('Progress updated successfully!', 'success');
            } catch (domError) {
                console.error('DOM manipulation error:', domError);
                // Still show success message even if DOM update fails
                showNotification('Progress updated successfully!', 'success');
            }
        } else {
            showNotification(data.error || data.message || 'Failed to update progress', 'error');
        }
    })
    .catch(error => {
        console.error('Network or parsing error:', error);
        showNotification('An error occurred while updating progress: ' + error.message, 'error');
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#10b981';
            break;
        case 'error':
            notification.style.backgroundColor = '#ef4444';
            break;
        default:
            notification.style.backgroundColor = '#667eea';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100px)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}

// Add hover effects for buttons
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects for goal buttons
    const style = document.createElement('style');
    style.textContent = `
        .btn-join-goal:hover {
            background-color: #059669 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-leave-goal:hover {
            background-color: #dc2626 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .goal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .goal-card {
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-1px);
        }
        
        button {
            transition: all 0.2s ease;
        }
    `;
    document.head.appendChild(style);
});

// Voice Chat Global Variables
let voiceChat = null;
let currentVoiceChannel = null;
let isMuted = false;

// Join Voice Channel
async function joinVoiceChannel(serverId, channelId, channelName) {
    try {
        // Check if already connected to a voice channel
        if (voiceChat && voiceChat.isConnected) {
            if (currentVoiceChannel === channelId) {
                showNotification('You are already connected to this voice channel', 'info');
                return;
            }

            // Disconnect from current channel first
            await disconnectVoice();
        }

        // Show connecting state
        const connectionIndicator = document.getElementById('voice-connection-indicator');
        if (connectionIndicator) {
            connectionIndicator.classList.add('connecting');
        }

        showNotification('Connecting to voice channel...', 'info');

        // Import the VoiceChat class (check if already loaded globally)
        if (typeof window.VoiceChat === 'undefined') {
            showNotification('Voice chat module not loaded. Please refresh the page.', 'error');
            return;
        }

        // Create new voice chat instance
        voiceChat = new window.VoiceChat();

        // Set up callbacks for UI updates
        voiceChat.on('connectionStateChange', (state) => {
            updateConnectionUI(state);
        });

        voiceChat.on('networkQualityChange', (quality) => {
            updateNetworkQualityUI(quality);
        });

        voiceChat.on('userJoined', (userId) => {
            updateVoiceUserIndicators();
        });

        voiceChat.on('userLeft', (userId) => {
            updateVoiceUserIndicators();
        });

        voiceChat.on('notification', (message, type) => {
            showNotification(message, type);
        });

        // Join the channel
        await voiceChat.joinChannel(channelId);

        // Update UI
        currentVoiceChannel = channelId;
        document.getElementById('voice-channel-name').textContent = channelName;
        document.getElementById('voice-controls-panel').style.display = 'block';

        // Mark voice channel as active
        document.querySelectorAll('.voice-channel-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.querySelector(`.voice-channel-link[data-channel-id="${channelId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Show user's own "In Voice" badge
        const myBadge = document.querySelector(`.in-voice-badge[data-user-id="{{ auth()->id() }}"]`);
        if (myBadge) {
            myBadge.style.display = 'inline-block';
        }

        if (connectionIndicator) {
            connectionIndicator.classList.remove('connecting');
        }

        showNotification(`Connected to ${channelName}`, 'success');
    } catch (error) {
        console.error('Error joining voice channel:', error);
        showNotification('Failed to join voice channel: ' + error.message, 'error');

        const connectionIndicator = document.getElementById('voice-connection-indicator');
        if (connectionIndicator) {
            connectionIndicator.classList.remove('connecting');
            connectionIndicator.classList.add('disconnected');
        }
    }
}

// Disconnect from Voice
async function disconnectVoice() {
    if (!voiceChat) {
        return;
    }

    try {
        await voiceChat.leaveChannel();

        // Update UI
        document.getElementById('voice-controls-panel').style.display = 'none';
        document.querySelectorAll('.voice-channel-link').forEach(link => {
            link.classList.remove('active');
        });

        // Hide user's own "In Voice" badge
        const myBadge = document.querySelector(`.in-voice-badge[data-user-id="{{ auth()->id() }}"]`);
        if (myBadge) {
            myBadge.style.display = 'none';
        }

        voiceChat = null;
        currentVoiceChannel = null;
        isMuted = false;

        // Reset mute button
        document.getElementById('mute-toggle-btn').classList.remove('muted');
        document.getElementById('mute-icon').textContent = '🎤';
        document.getElementById('mute-text').textContent = 'Mute';

        showNotification('Disconnected from voice channel', 'info');
    } catch (error) {
        console.error('Error disconnecting from voice:', error);
        showNotification('Error disconnecting: ' + error.message, 'error');
    }
}

// Toggle Mute
async function toggleMute() {
    if (!voiceChat) {
        return;
    }

    try {
        isMuted = await voiceChat.toggleMute();

        // Update mute button UI
        const muteBtn = document.getElementById('mute-toggle-btn');
        const muteIcon = document.getElementById('mute-icon');
        const muteText = document.getElementById('mute-text');

        if (isMuted) {
            muteBtn.classList.add('muted');
            muteIcon.textContent = '🔇';
            muteText.textContent = 'Unmute';
            showNotification('Microphone muted', 'info');
        } else {
            muteBtn.classList.remove('muted');
            muteIcon.textContent = '🎤';
            muteText.textContent = 'Mute';
            showNotification('Microphone unmuted', 'success');
        }
    } catch (error) {
        console.error('Error toggling mute:', error);
        showNotification('Error toggling mute: ' + error.message, 'error');
    }
}

// Update Connection State UI
function updateConnectionUI(state) {
    const indicator = document.getElementById('voice-connection-indicator');
    if (!indicator) return;

    indicator.classList.remove('connecting', 'disconnected');

    switch (state) {
        case 'CONNECTING':
            indicator.classList.add('connecting');
            break;
        case 'CONNECTED':
            indicator.style.backgroundColor = '#10b981';
            break;
        case 'DISCONNECTED':
            indicator.classList.add('disconnected');
            break;
        case 'RECONNECTING':
            indicator.classList.add('connecting');
            break;
    }
}

// Update Network Quality UI
function updateNetworkQualityUI(quality) {
    const qualityIndicator = document.getElementById('network-quality-indicator');
    const qualityText = document.getElementById('network-quality-text');

    if (!qualityIndicator || !qualityText) return;

    qualityIndicator.classList.remove('network-quality-excellent', 'network-quality-good', 'network-quality-poor');

    if (quality >= 4) {
        qualityIndicator.classList.add('network-quality-excellent');
        qualityText.textContent = 'Excellent';
        qualityText.style.color = '#10b981';
    } else if (quality >= 2) {
        qualityIndicator.classList.add('network-quality-good');
        qualityText.textContent = 'Good';
        qualityText.style.color = '#f59e0b';
    } else {
        qualityIndicator.classList.add('network-quality-poor');
        qualityText.textContent = 'Poor';
        qualityText.style.color = '#ef4444';
    }
}

// Update Voice User Indicators
function updateVoiceUserIndicators() {
    // This will be updated via Laravel Echo real-time events
    // Placeholder for future real-time updates
}

// Laravel Echo Real-Time Voice Presence Listeners
@if(isset($server))
document.addEventListener('DOMContentLoaded', function() {
    const serverId = {{ $server->id }};

    // Listen for voice user joined events
    window.Echo.private(`server.${serverId}`)
        .listen('.voice.user.joined', (event) => {
            console.log('User joined voice:', event);

            // Show "In Voice" badge for the user
            const badge = document.querySelector(`.in-voice-badge[data-user-id="${event.user_id}"]`);
            if (badge) {
                badge.style.display = 'inline-block';
            }

            // Update voice channel user count
            const channelCount = document.querySelector(`.voice-user-count[data-channel-id="${event.channel_id}"]`);
            if (channelCount) {
                const countSpan = channelCount.querySelector('.count');
                const currentCount = parseInt(countSpan.textContent) || 0;
                countSpan.textContent = currentCount + 1;
                channelCount.style.display = 'block';
            }

            // Show notification if not the current user
            if (event.user_id !== {{ auth()->id() }}) {
                showNotification(`${event.user_name} joined ${event.channel_name}`, 'info');
            }
        })
        .listen('.voice.user.left', (event) => {
            console.log('User left voice:', event);

            // Hide "In Voice" badge for the user
            const badge = document.querySelector(`.in-voice-badge[data-user-id="${event.user_id}"]`);
            if (badge) {
                badge.style.display = 'none';
            }

            // Update voice channel user count
            const channelCount = document.querySelector(`.voice-user-count[data-channel-id="${event.channel_id}"]`);
            if (channelCount) {
                const countSpan = channelCount.querySelector('.count');
                const currentCount = parseInt(countSpan.textContent) || 0;
                const newCount = Math.max(0, currentCount - 1);
                countSpan.textContent = newCount;

                if (newCount === 0) {
                    channelCount.style.display = 'none';
                }
            }

            // Show notification if not the current user
            if (event.user_id !== {{ auth()->id() }}) {
                showNotification(`${event.user_name} left ${event.channel_name}`, 'info');
            }
        })
        .listen('.voice.user.muted', (event) => {
            console.log('User mute status changed:', event);

            // Could add muted icon next to user
            // For now, just log it
        })
        .listen('.user.status.updated', (event) => {
            console.log('[Server] User status updated:', event);
            updateMemberStatus(event);
        });
});
@endif

/**
 * Update member status in the member list (Phase 2)
 * Called when receiving real-time status update broadcasts
 */
function updateMemberStatus(data) {
    const { user_id, status, status_color, activity, has_custom_status, full_custom_status } = data;

    // Find all member items for this user
    const memberItems = document.querySelectorAll(`.member-item-enhanced[data-user-id="${user_id}"]`);

    memberItems.forEach(memberItem => {
        // Update status data attribute
        memberItem.dataset.status = status;

        // Update status indicator
        const statusIndicator = memberItem.querySelector('.member-status');
        if (statusIndicator) {
            statusIndicator.dataset.status = status;
        }

        // Update activity text
        const activityElement = memberItem.querySelector('.member-activity');
        if (activity) {
            if (activityElement) {
                activityElement.textContent = activity;
                activityElement.style.display = '';
            } else {
                // Create activity element if it doesn't exist
                const infoElement = memberItem.querySelector('.member-info');
                if (infoElement) {
                    const newActivity = document.createElement('div');
                    newActivity.className = 'member-activity';
                    newActivity.textContent = activity;
                    infoElement.appendChild(newActivity);
                }
            }
        } else if (activityElement) {
            activityElement.style.display = 'none';
        }
    });

    console.log(`[Status] Updated status for user ${user_id} to ${status}`);
}
</script>
@endsection