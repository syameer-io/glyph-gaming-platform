{{--
    Discord-style Channel Header Component
    Phase 3: Server Header & Navigation

    A header bar with:
    - Channel icon and name
    - Channel topic/description (optional)
    - Action icons: member toggle, search
    - Tooltips on hover

    @param Channel $channel - The current channel
    @param Server $server - The current server
    @param bool $memberListVisible - Whether member list is visible
--}}

@props([
    'channel',
    'server',
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
        x-data="{ memberListVisible: {{ $memberListVisible ? 'true' : 'false' }} }"
    >
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

        {{-- Search Icon --}}
        <button
            class="header-icon-btn"
            @click="$dispatch('open-search-modal')"
            data-tooltip="Search (Ctrl+K)"
            title="Search"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </div>
</div>
