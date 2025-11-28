{{--
    Discord-style Channel Header Component
    Phase 3: Server Header & Navigation

    A header bar with:
    - Channel icon and name
    - Channel topic/description (optional)
    - Action icons: threads, notification, pin, member toggle, search
    - Tooltips on hover

    @param Channel $channel - The current channel
    @param Server $server - The current server
    @param int $pinnedCount - Number of pinned messages
    @param int $notificationCount - Number of unread notifications
    @param bool $memberListVisible - Whether member list is visible
--}}

@props([
    'channel',
    'server',
    'pinnedCount' => 0,
    'notificationCount' => 0,
    'memberListVisible' => true,
])

<div class="channel-header">
    {{-- Left Side: Channel Info --}}
    <div class="channel-header-left">
        <div class="channel-header-info">
            {{-- Channel Icon --}}
            @if($channel->type === 'voice')
                <span class="channel-header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3C10.34 3 9 4.34 9 6V12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12V6C15 4.34 13.66 3 12 3Z"/>
                        <path d="M19 12C19 15.53 16.39 18.44 13 18.93V21H16V23H8V21H11V18.93C7.61 18.44 5 15.53 5 12H7C7 14.76 9.24 17 12 17C14.76 17 17 14.76 17 12H19Z"/>
                    </svg>
                </span>
            @else
                <span class="channel-header-icon">#</span>
            @endif

            {{-- Channel Name --}}
            <h1 class="channel-header-name">{{ $channel->name }}</h1>
        </div>

        {{-- Topic Divider & Topic (if exists) --}}
        @if($channel->description)
            <div class="channel-header-divider"></div>
            <span
                class="channel-header-topic"
                title="{{ $channel->description }}"
                @click="$dispatch('show-channel-topic')"
            >
                {{ $channel->description }}
            </span>
        @endif
    </div>

    {{-- Right Side: Action Icons --}}
    <div
        class="channel-header-actions"
        x-data="{
            showNotifications: false,
            showPinned: false,
            memberListVisible: {{ $memberListVisible ? 'true' : 'false' }}
        }"
    >
        {{-- Threads Icon (if text channel) --}}
        @if($channel->type === 'text')
            <button
                class="header-icon-btn"
                data-tooltip="Threads"
                title="Threads"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </button>
        @endif

        {{-- Notification Bell --}}
        <div class="notification-dropdown-wrapper" x-data="{ showNotifications: false }">
            <button
                class="header-icon-btn"
                :class="{ 'active': showNotifications }"
                @click="showNotifications = !showNotifications; showPinned = false"
                data-tooltip="Notification Settings"
                title="Notification Settings"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($notificationCount > 0)
                    <span class="notification-badge">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
                @endif
            </button>

            {{-- Notification Dropdown --}}
            <x-notification-dropdown
                :server="$server"
                :channel="$channel"
                x-show="showNotifications"
                @click.away="showNotifications = false"
            />
        </div>

        {{-- Pinned Messages --}}
        <div class="pinned-messages-wrapper" x-data="{ showPinned: false }">
            <button
                class="header-icon-btn"
                :class="{ 'active': showPinned }"
                @click="showPinned = !showPinned; showNotifications = false"
                data-tooltip="Pinned Messages"
                title="Pinned Messages"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                @if($pinnedCount > 0)
                    <span class="notification-badge">{{ $pinnedCount }}</span>
                @endif
            </button>

            {{-- Pinned Messages Popover --}}
            <x-pinned-messages
                :channel="$channel"
                :server="$server"
                x-show="showPinned"
                @click.away="showPinned = false"
            />
        </div>

        {{-- Divider --}}
        <div class="header-action-divider"></div>

        {{-- Member List Toggle --}}
        <div class="member-list-toggle">
            <button
                class="header-icon-btn"
                :class="{ 'active': memberListVisible }"
                @click="memberListVisible = !memberListVisible; $dispatch('toggle-member-list', { visible: memberListVisible }); localStorage.setItem('memberListVisible', memberListVisible)"
                data-tooltip="Toggle Member List"
                title="Toggle Member List"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </div>

        {{-- Search --}}
        <div class="header-search" @click="$dispatch('open-search-modal')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                placeholder="Search"
                readonly
                @focus="$dispatch('open-search-modal')"
            >
            <span class="search-shortcut">Ctrl+K</span>
        </div>
    </div>
</div>
