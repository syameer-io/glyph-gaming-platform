{{--
/**
 * Voice Channel Item Component
 *
 * A clickable voice channel component for the server sidebar.
 * Displays channel name with speaker icon, user count badge,
 * connected users list, and optional settings button.
 *
 * @component
 * @package Glyph
 * @version 1.0.0
 *
 * @param \App\Models\Channel $channel - The voice channel model instance
 * @param \App\Models\Server $server - The parent server model instance
 * @param int $userCount - Number of users currently in the voice channel (default: 0)
 * @param bool $isConnected - Whether the current user is connected to this channel (default: false)
 * @param bool $canEdit - Whether to show the settings button on hover (default: false)
 * @param \Illuminate\Support\Collection|null $connectedUsers - Collection of users currently in the channel
 * @param string|null $editRoute - Route for editing the channel settings
 *
 * @example Basic usage:
 * <x-voice-channel-item :channel="$channel" :server="$server" />
 *
 * @example With connected users:
 * <x-voice-channel-item
 *     :channel="$channel"
 *     :server="$server"
 *     :userCount="3"
 *     :connectedUsers="$usersInChannel"
 * />
 *
 * @example Currently connected with edit capability:
 * <x-voice-channel-item
 *     :channel="$channel"
 *     :server="$server"
 *     :isConnected="true"
 *     :userCount="5"
 *     :canEdit="$canManageChannels"
 *     :connectedUsers="$usersInChannel"
 * />
 */
--}}

@props([
    'channel',
    'server',
    'userCount' => 0,
    'isConnected' => false,
    'canEdit' => false,
    'connectedUsers' => null,
    'editRoute' => null,
])

@php
    // Validate required props
    if (!isset($channel) || !isset($server)) {
        throw new \InvalidArgumentException('voice-channel-item component requires $channel and $server props');
    }

    // Build CSS classes for voice channel item
    $classes = ['voice-channel-item'];
    if ($isConnected) {
        $classes[] = 'voice-connected';
    }
    if ($userCount > 0) {
        $classes[] = 'voice-active';
    }

    // Generate edit route if not provided
    $settingsRoute = $editRoute ?? route('server.admin.settings', $server) . '#channels';

    // Ensure connectedUsers is a collection
    $connectedUsers = $connectedUsers ?? collect();
@endphp

{{-- Voice Channel Clickable Area --}}
<div
    class="{{ implode(' ', $classes) }}"
    onclick="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
    data-channel-id="{{ $channel->id }}"
    data-channel-name="{{ $channel->name }}"
    data-channel-type="voice"
    role="button"
    tabindex="0"
    @keydown.enter="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
    @keydown.space.prevent="joinVoiceChannel({{ $server->id }}, {{ $channel->id }}, '{{ addslashes($channel->name) }}')"
    aria-label="Join {{ $channel->name }} voice channel{{ $userCount > 0 ? ', ' . $userCount . ' users connected' : '' }}"
>
    {{-- Left Side: Icon and Name --}}
    <div class="voice-channel-left">
        {{-- Speaker/Volume Icon --}}
        <span class="voice-channel-icon" aria-hidden="true">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path d="M10 3.75a.75.75 0 00-1.264-.546L4.703 7H3.167a.75.75 0 00-.7.48A6.985 6.985 0 002 10c0 .887.165 1.737.468 2.52.111.29.39.48.7.48h1.535l4.033 3.796A.75.75 0 0010 16.25V3.75zM15.95 5.05a.75.75 0 00-1.06 1.061 5.5 5.5 0 010 7.778.75.75 0 001.06 1.06 7 7 0 000-9.899z" />
                <path d="M13.829 7.172a.75.75 0 00-1.061 1.06 2.5 2.5 0 010 3.536.75.75 0 001.06 1.06 4 4 0 000-5.656z" />
            </svg>
        </span>

        {{-- Channel Name --}}
        <span class="channel-name">{{ $channel->name }}</span>
    </div>

    {{-- Right Side: User Count and Settings --}}
    <div class="voice-channel-right">
        {{-- User Count Badge (shown when users are connected) --}}
        @if($userCount > 0)
            <span
                class="voice-user-count-badge"
                data-channel-id="{{ $channel->id }}"
                aria-label="{{ $userCount }} {{ $userCount === 1 ? 'user' : 'users' }} connected"
            >
                {{ $userCount }}
            </span>
        @else
            {{-- Hidden badge for JavaScript updates --}}
            <span
                class="voice-user-count-badge"
                data-channel-id="{{ $channel->id }}"
                style="display: none;"
                aria-label="0 users connected"
            >
                0
            </span>
        @endif

        {{-- Settings Button (visible on hover for authorized users) --}}
        @if($canEdit)
            <button
                class="channel-settings-btn"
                @click.stop="window.location.href = '{{ $settingsRoute }}'"
                title="Edit Channel"
                aria-label="Edit {{ $channel->name }} settings"
                type="button"
            >
                {{-- Gear/Cog Icon --}}
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.187.447l-1.598-.54a6.993 6.993 0 01-1.929 1.115l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.114a7.05 7.05 0 010-2.227L1.821 7.773a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.187-.447l1.598.54A6.993 6.993 0 017.51 3.456l.33-1.652zM10 13a3 3 0 100-6 3 3 0 000 6z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        @endif
    </div>

    {{-- Screen Reader Only: Connection Status --}}
    @if($isConnected)
        <span class="sidebar-sr-only">Currently connected</span>
    @endif
</div>

{{-- Connected Users List (shown when users are in the channel) --}}
@if($connectedUsers->isNotEmpty())
    <div
        class="voice-users-list"
        role="list"
        aria-label="Users in {{ $channel->name }}"
    >
        @foreach($connectedUsers as $user)
            <div
                class="voice-user-item"
                data-user-id="{{ $user->id }}"
                role="listitem"
            >
                {{-- User Avatar --}}
                <img
                    src="{{ $user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                    alt="{{ $user->display_name }}"
                    class="voice-user-avatar"
                    loading="lazy"
                >

                {{-- User Name --}}
                <span class="voice-user-name">{{ $user->display_name }}</span>

                {{-- Muted Indicator (if user is muted) --}}
                @if(isset($user->is_muted) && $user->is_muted)
                    <span class="voice-user-muted-icon" aria-label="Muted">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            width="14"
                            height="14"
                        >
                            <path d="M9.547 3.062A.75.75 0 0110 3.75v12.5a.75.75 0 01-1.264.546L4.703 13H3.167a.75.75 0 01-.7-.48A6.985 6.985 0 012 10c0-.887.165-1.737.468-2.52a.75.75 0 01.7-.48h1.535l4.033-3.796a.75.75 0 01.811-.142zM13.28 6.22a.75.75 0 10-1.06 1.06L13.94 9l-1.72 1.72a.75.75 0 001.06 1.06L15 10.06l1.72 1.72a.75.75 0 101.06-1.06L16.06 9l1.72-1.72a.75.75 0 00-1.06-1.06L15 7.94l-1.72-1.72z" />
                        </svg>
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{--
Note: The joinVoiceChannel function should be defined globally in the parent view.
Expected signature: function joinVoiceChannel(serverId, channelId, channelName)

This component expects the following CSS classes from sidebar.css:
- .voice-channel-item
- .voice-connected
- .voice-active
- .voice-channel-left
- .voice-channel-icon
- .channel-name
- .voice-channel-right
- .voice-user-count-badge
- .channel-settings-btn
- .voice-users-list
- .voice-user-item
- .voice-user-avatar
- .voice-user-name
- .voice-user-muted-icon
- .sidebar-sr-only
--}}
