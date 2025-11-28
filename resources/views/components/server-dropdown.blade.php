{{--
    Discord-style Server Dropdown Component
    Phase 3: Server Header & Navigation

    A dropdown menu triggered by the server name with:
    - Server boost progress bar
    - Invite People option
    - Server Settings (admin only)
    - Notification Settings
    - Privacy Settings
    - Edit Server Profile
    - Hide Muted Channels toggle
    - Leave Server (danger, at bottom)

    @param Server $server - The current server
    @param bool $isAdmin - Whether the user is a server admin
    @param bool $isOwner - Whether the user is the server owner
--}}

@props([
    'server',
    'isAdmin' => false,
    'isOwner' => false,
])

@php
    // Calculate boost progress (placeholder - can be implemented with real boost data)
    $boostLevel = 0;
    $currentBoosts = 0;
    $boostsNeeded = 2;
    $boostProgress = 0;
@endphp

<div
    class="server-dropdown"
    x-data="{
        open: false,
        hideMuted: localStorage.getItem('hideMutedChannels_{{ $server->id }}') === 'true'
    }"
    @keydown.escape.window="open = false"
>
    {{-- Dropdown Trigger --}}
    <button
        class="server-dropdown-trigger"
        @click="open = !open"
        @keydown.enter.prevent="open = !open"
        @keydown.space.prevent="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="Server options"
    >
        <span class="server-name">{{ $server->name }}</span>
        <svg
            class="dropdown-chevron"
            :class="{ 'rotated': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div
        class="server-dropdown-menu"
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @click.away="open = false"
        x-cloak
        role="menu"
        aria-orientation="vertical"
    >
        {{-- Server Boost Section --}}
        <div class="dropdown-section boost-section">
            <div class="boost-progress">
                <div class="boost-progress-bar" style="width: {{ $boostProgress }}%"></div>
            </div>
            <div class="boost-info">
                <svg class="boost-sparkle" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2Z"/>
                </svg>
                <span class="boost-level">Level {{ $boostLevel }}</span>
                <span>{{ $boostsNeeded }} Boosts for Level {{ $boostLevel + 1 }}</span>
            </div>
        </div>

        <div class="dropdown-divider"></div>

        {{-- Invite People --}}
        <button
            class="dropdown-item"
            @click="$dispatch('open-invite-modal'); open = false"
            role="menuitem"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>Invite People</span>
        </button>

        {{-- Server Settings (Admin Only) --}}
        @if($isAdmin)
            <a
                href="{{ route('server.admin.settings', $server) }}"
                class="dropdown-item"
                role="menuitem"
                @click="open = false"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Server Settings</span>
            </a>
        @endif

        {{-- Notification Settings --}}
        <button
            class="dropdown-item"
            @click="$dispatch('open-notification-settings'); open = false"
            role="menuitem"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span>Notification Settings</span>
        </button>

        {{-- Privacy Settings --}}
        <button
            class="dropdown-item"
            @click="$dispatch('open-privacy-settings'); open = false"
            role="menuitem"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <span>Privacy Settings</span>
        </button>

        <div class="dropdown-divider"></div>

        {{-- Edit Server Profile --}}
        <button
            class="dropdown-item"
            @click="$dispatch('open-edit-server-profile'); open = false"
            role="menuitem"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>Edit Server Profile</span>
        </button>

        {{-- Hide Muted Channels Toggle --}}
        <button
            class="dropdown-item"
            @click="hideMuted = !hideMuted; localStorage.setItem('hideMutedChannels_{{ $server->id }}', hideMuted); $dispatch('toggle-muted-channels', { hide: hideMuted })"
            role="menuitem"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
            <span>Hide Muted Channels</span>
            <div
                class="dropdown-toggle"
                :class="{ 'active': hideMuted }"
            ></div>
        </button>

        <div class="dropdown-divider"></div>

        {{-- Leave Server (or Delete Server for owner) --}}
        @if($isOwner)
            <form method="POST" action="{{ route('server.destroy', $server) }}" style="display: contents;">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="dropdown-item danger"
                    role="menuitem"
                    onclick="return confirm('Are you sure you want to delete this server? This action cannot be undone and will delete all channels, messages, and remove all members.')"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Delete Server</span>
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('server.leave', $server) }}" style="display: contents;">
                @csrf
                <button
                    type="submit"
                    class="dropdown-item danger"
                    role="menuitem"
                    onclick="return confirm('Are you sure you want to leave this server?')"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Leave Server</span>
                </button>
            </form>
        @endif
    </div>
</div>
