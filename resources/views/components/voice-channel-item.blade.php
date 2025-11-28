{{--
/**
 * Voice Channel Item Component (Phase 4 Enhanced)
 *
 * An expandable voice channel component for the server sidebar.
 * Displays channel name with speaker icon, connected users with
 * speaking indicators, mute/deafen status, and streaming badges.
 *
 * @component
 * @package Glyph
 * @version 2.0.0
 *
 * @param \App\Models\Channel $channel - The voice channel model instance
 * @param \App\Models\Server $server - The parent server model instance
 * @param bool $isConnected - Whether the current user is connected to this channel (default: false)
 * @param bool $canEdit - Whether to show the settings button on hover (default: false)
 * @param string|null $editRoute - Route for editing the channel settings
 * @param string|null $channelStatus - Optional status message for the channel
 *
 * @example Basic usage:
 * <x-voice-channel-item :channel="$channel" :server="$server" />
 *
 * @example With connected user:
 * <x-voice-channel-item
 *     :channel="$channel"
 *     :server="$server"
 *     :isConnected="true"
 *     :canEdit="$canManageChannels"
 * />
 */
--}}

@props([
    'channel',
    'server',
    'isConnected' => false,
    'canEdit' => false,
    'editRoute' => null,
    'channelStatus' => null,
])

@php
    // Validate required props
    if (!isset($channel) || !isset($server)) {
        throw new \InvalidArgumentException('voice-channel-item component requires $channel and $server props');
    }

    // Get active voice sessions for this channel
    $voiceSessions = \App\Models\VoiceSession::where('channel_id', $channel->id)
        ->whereNull('left_at')
        ->with(['user.profile'])
        ->get();

    $userCount = $voiceSessions->count();
    $connectedUsers = $voiceSessions->map(fn($session) => $session->user)->filter();

    // Build CSS classes for voice channel wrapper
    $wrapperClasses = ['voice-channel-wrapper'];
    if ($isConnected) {
        $wrapperClasses[] = 'connected';
    }

    // Build CSS classes for voice channel item
    $itemClasses = ['voice-channel-item'];
    if ($isConnected) {
        $itemClasses[] = 'voice-connected';
    }
    if ($userCount > 0) {
        $itemClasses[] = 'voice-active';
    }

    // Generate edit route if not provided
    $settingsRoute = $editRoute ?? route('server.admin.settings', $server) . '#channels';

    // Auto-expand if users are connected
    $autoExpand = $userCount > 0;
@endphp

{{-- Voice Channel Wrapper (Expandable Container) --}}
<div
    class="{{ implode(' ', $wrapperClasses) }}"
    x-data="{
        expanded: {{ $autoExpand ? 'true' : 'false' }},
        userCount: {{ $userCount }},
        users: {{ $connectedUsers->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->display_name,
            'avatar' => $u->profile->avatar_url ?? asset('images/default-avatar.png'),
            'isSpeaking' => false,
            'isMuted' => $voiceSessions->firstWhere('user_id', $u->id)?->is_muted ?? false,
            'isDeafened' => $voiceSessions->firstWhere('user_id', $u->id)?->is_deafened ?? false,
            'isStreaming' => false
        ])->values()->toJson() }}
    }"
    data-channel-id="{{ $channel->id }}"
    data-channel-type="voice"
    :class="{ 'collapsed': !expanded }"
>
    {{-- Voice Channel Header (Clickable to Join/Expand) --}}
    <div
        class="{{ implode(' ', $itemClasses) }}"
        @click="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
        role="button"
        tabindex="0"
        @keydown.enter="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
        @keydown.space.prevent="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
        aria-label="Join {{ $channel->name }} voice channel{{ $userCount > 0 ? ', ' . $userCount . ' users connected' : '' }}"
        aria-expanded="expanded"
    >
        {{-- Expand/Collapse Toggle --}}
        <button
            type="button"
            class="voice-channel-expand-icon"
            @click.stop="expanded = !expanded"
            x-show="userCount > 0"
            aria-label="Toggle user list"
            style="display: none;"
            x-init="$el.style.display = userCount > 0 ? 'flex' : 'none'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
            </svg>
        </button>

        {{-- Left Side: Icon and Name --}}
        <div class="voice-channel-left">
            {{-- Speaker/Volume Icon --}}
            <span class="voice-channel-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3.75a.75.75 0 00-1.264-.546L4.703 7H3.167a.75.75 0 00-.7.48A6.985 6.985 0 002 10c0 .887.165 1.737.468 2.52.111.29.39.48.7.48h1.535l4.033 3.796A.75.75 0 0010 16.25V3.75zM15.95 5.05a.75.75 0 00-1.06 1.061 5.5 5.5 0 010 7.778.75.75 0 001.06 1.06 7 7 0 000-9.899z"/>
                    <path d="M13.829 7.172a.75.75 0 00-1.061 1.06 2.5 2.5 0 010 3.536.75.75 0 001.06 1.06 4 4 0 000-5.656z"/>
                </svg>
            </span>

            {{-- Channel Name --}}
            <span class="channel-name">{{ $channel->name }}</span>
        </div>

        {{-- Right Side: User Count, Actions, and Settings --}}
        <div class="voice-channel-right">
            {{-- User Count Badge (shown when users are connected) --}}
            <span
                class="voice-user-count-badge"
                data-channel-id="{{ $channel->id }}"
                x-show="userCount > 0"
                x-text="userCount"
                aria-label=""
                x-bind:aria-label="userCount + ' users connected'"
            >{{ $userCount }}</span>

            {{-- Hover Actions --}}
            <div class="voice-channel-actions">
                {{-- Invite to Channel --}}
                <button
                    type="button"
                    class="voice-channel-action-btn"
                    @click.stop="alert('Invite feature coming soon!')"
                    title="Invite to Channel"
                    aria-label="Invite to channel"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 5a3 3 0 11-6 0 3 3 0 016 0zM2.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 018 18a9.953 9.953 0 01-5.385-1.572zM16.25 5.75a.75.75 0 00-1.5 0v2h-2a.75.75 0 000 1.5h2v2a.75.75 0 001.5 0v-2h2a.75.75 0 000-1.5h-2v-2z"/>
                    </svg>
                </button>

                {{-- Create Invite Link --}}
                <button
                    type="button"
                    class="voice-channel-action-btn"
                    @click.stop="navigator.clipboard.writeText(window.location.origin + '/servers/{{ $server->id }}/voice/{{ $channel->id }}'); alert('Invite link copied!')"
                    title="Create Invite Link"
                    aria-label="Create invite link"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z"/>
                        <path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z"/>
                    </svg>
                </button>
            </div>

            {{-- Settings Button (visible on hover for authorized users) --}}
            @if($canEdit)
                <button
                    class="channel-settings-btn"
                    @click.stop="window.location.href = '{{ $settingsRoute }}'"
                    title="Edit Channel"
                    aria-label="Edit {{ $channel->name }} settings"
                    type="button"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.187.447l-1.598-.54a6.993 6.993 0 01-1.929 1.115l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.114a7.05 7.05 0 010-2.227L1.821 7.773a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.187-.447l1.598.54A6.993 6.993 0 017.51 3.456l.33-1.652zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                </button>
            @endif
        </div>

        {{-- Screen Reader Only: Connection Status --}}
        @if($isConnected)
            <span class="sidebar-sr-only">Currently connected</span>
        @endif
    </div>

    {{-- Channel Status (if set) --}}
    @if($channelStatus)
        <div class="voice-channel-status" x-show="expanded">
            <span class="voice-channel-status-text">{{ $channelStatus }}</span>
            @if($canEdit)
                <button class="voice-channel-status-edit" title="Edit status" @click.stop>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3 h-3">
                        <path d="M13.488 2.513a1.75 1.75 0 00-2.475 0L2.988 10.538a1.75 1.75 0 00-.488 1.01l-.403 2.416a.75.75 0 00.873.873l2.416-.403a1.75 1.75 0 001.01-.488l8.025-8.025a1.75 1.75 0 000-2.476l-.933-.932z"/>
                    </svg>
                </button>
            @endif
        </div>
    @elseif($canEdit && $isConnected)
        {{-- Set Status Link (shown when connected and admin) --}}
        <div class="voice-channel-set-status" x-show="expanded" @click.stop>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3 h-3">
                <path d="M13.488 2.513a1.75 1.75 0 00-2.475 0L2.988 10.538a1.75 1.75 0 00-.488 1.01l-.403 2.416a.75.75 0 00.873.873l2.416-.403a1.75 1.75 0 001.01-.488l8.025-8.025a1.75 1.75 0 000-2.476l-.933-.932z"/>
            </svg>
            <span>Set a channel status</span>
        </div>
    @endif

    {{-- Connected Users List --}}
    <div
        class="voice-channel-users"
        x-show="expanded && userCount > 0"
        role="list"
        aria-label="Users in {{ $channel->name }}"
    >
        <template x-for="user in users.slice(0, 10)" :key="user.id">
            <div
                class="voice-user-item"
                :class="{ 'speaking': user.isSpeaking }"
                :data-user-id="user.id"
                data-channel-id="{{ $channel->id }}"
                role="listitem"
            >
                {{-- User Avatar with Speaking Ring --}}
                <div class="voice-user-avatar-wrapper" :class="{ 'speaking': user.isSpeaking }">
                    <img
                        :src="user.avatar"
                        :alt="user.name"
                        class="voice-user-avatar"
                        loading="lazy"
                    >

                    {{-- Muted Badge --}}
                    <div class="voice-muted-badge" x-show="user.isMuted && !user.isDeafened" title="Muted">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.547 3.062A.75.75 0 0110 3.75v12.5a.75.75 0 01-1.264.546L4.703 13H3.167a.75.75 0 01-.7-.48A6.985 6.985 0 012 10c0-.887.165-1.737.468-2.52a.75.75 0 01.7-.48h1.535l4.033-3.796a.75.75 0 01.811-.142zM13.28 6.22a.75.75 0 10-1.06 1.06L13.94 9l-1.72 1.72a.75.75 0 001.06 1.06L15 10.06l1.72 1.72a.75.75 0 101.06-1.06L16.06 9l1.72-1.72a.75.75 0 00-1.06-1.06L15 7.94l-1.72-1.72z"/>
                        </svg>
                    </div>

                    {{-- Deafened Badge --}}
                    <div class="voice-deafened-badge" x-show="user.isDeafened" title="Deafened">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-5.634 8.047.75.75 0 01-.776 1.012 7.49 7.49 0 01-.557-.062A7.5 7.5 0 1117.5 10a7.49 7.49 0 01-.062.557.75.75 0 011.012.776A6 6 0 0010 2zM5.22 14.22a.75.75 0 011.06 0l2.97 2.97 2.97-2.97a.75.75 0 111.06 1.06l-2.97 2.97 2.97 2.97a.75.75 0 11-1.06 1.06l-2.97-2.97-2.97 2.97a.75.75 0 01-1.06-1.06l2.97-2.97-2.97-2.97a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                {{-- Username --}}
                <span class="voice-user-name" x-text="user.name.length > 16 ? user.name.substring(0, 16) + '...' : user.name"></span>

                {{-- Status Icons --}}
                <div class="voice-user-status-icons">
                    <span class="voice-live-badge" x-show="user.isStreaming">LIVE</span>
                </div>
            </div>
        </template>

        {{-- Overflow indicator for more than 10 users --}}
        <div class="voice-users-overflow" x-show="users.length > 10">
            <span x-text="'+' + (users.length - 10) + ' more'"></span>
        </div>
    </div>
</div>

{{--
Note: This component expects the following global functions and Alpine.js setup:
- joinVoiceChannel(serverId, channelId, channelName) - Global function to join voice
- CSS classes from voice-sidebar.css and sidebar.css

Real-time updates are handled via WebSocket events:
- voice.user.joined - Add user to users array
- voice.user.left - Remove user from users array
- voice.user.speaking - Update user.isSpeaking
- voice.user.muted - Update user.isMuted
- voice.user.deafened - Update user.isDeafened
--}}
