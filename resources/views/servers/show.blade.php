@extends('layouts.app')

@section('title', $server->name . ' - Glyph')

@push('styles')
<style>
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
</style>
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

        <!-- Rest of the sidebar remains the same -->
        <!-- Channels List -->
        <div style="flex: 1; overflow-y: auto; padding: 8px;">
            <div style="margin-bottom: 16px;">
                <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 8px;">Text Channels</p>
                @foreach($server->channels->where('type', 'text') as $ch)
                    <a href="{{ route('channel.show', [$server, $ch]) }}" 
                       class="sidebar-link {{ isset($channel) && $channel->id === $ch->id ? 'active' : '' }}"
                       style="display: block; margin-bottom: 4px;">
                        <span style="color: #71717a; margin-right: 8px;">#</span>
                        {{ $ch->name }}
                    </a>
                @endforeach
            </div>

            <div>
                <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 8px;">Voice Channels</p>
                @foreach($server->channels->where('type', 'voice') as $ch)
                    <div class="sidebar-link" style="opacity: 0.5; cursor: not-allowed;">
                        <span style="color: #71717a; margin-right: 8px;">🔊</span>
                        {{ $ch->name }}
                    </div>
                @endforeach
            </div>
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
        
        <div style="padding: 16px; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center;">
            <div>
                <h2 style="margin-bottom: 16px;">Welcome to {{ $server->name }}!</h2>
                <p style="color: #b3b3b5; margin-bottom: 24px;">{{ $server->description ?: 'This is the beginning of your server.' }}</p>
                @if($defaultChannel)
                    <a href="{{ route('channel.show', [$server, $defaultChannel]) }}" class="btn btn-primary">
                        Open #{{ $defaultChannel->name }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Members Sidebar remains the same -->
    <div style="width: 240px; background-color: #18181b; padding: 16px; overflow-y: auto;">
        <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 16px;">
            Members — {{ $server->members->count() }}
        </p>
        
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
                        $membersByRole->put($roleKey, collect());
                    }
                    $membersByRole->get($roleKey)->push($member);
                } else {
                    // Members with no custom roles go to default "Member" role
                    $defaultRole = $server->roles()->where('name', 'Member')->first();
                    if ($defaultRole) {
                        $roleKey = 'Member';
                        if (!$membersByRole->has($roleKey)) {
                            $membersByRole->put($roleKey, collect());
                        }
                        $membersByRole->get($roleKey)->push($member);
                    }
                }
            }
            
            // Get roles for proper ordering
            $allRoles = $server->roles()->orderBy('position', 'desc')->get();
        @endphp

        @foreach($allRoles as $role)
            @if($membersByRole->has($role->name) && $membersByRole->get($role->name)->count() > 0)
                @php $roleMembers = $membersByRole->get($role->name); @endphp
                <p style="font-size: 12px; color: #71717a; margin-bottom: 8px; {{ $loop->index > 0 ? 'margin-top: 16px;' : '' }}">
                    {{ strtoupper($role->name) }} — {{ $roleMembers->count() }}
                </p>
                @foreach($roleMembers as $member)
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <img src="{{ $member->profile->avatar_url }}" alt="{{ $member->display_name }}" 
                             style="width: 32px; height: 32px; border-radius: 50%;">
                        <div>
                            <div style="font-size: 14px; color: {{ $role->color }};">{{ $member->display_name }}</div>
                            <div style="font-size: 12px; color: #71717a;">
                                <span class="status-indicator {{ $member->profile->status === 'online' ? 'status-online' : 'status-offline' }}"></span>
                                {{ ucfirst($member->profile->status) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        @endforeach
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
</script>
@endsection