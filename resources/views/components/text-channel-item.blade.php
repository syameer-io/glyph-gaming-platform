{{--
/**
 * Text Channel Item Component
 *
 * A clickable text channel link component for the server sidebar.
 * Displays channel name with appropriate icon (hashtag or lock for private),
 * unread indicators, mention badges, and optional settings button.
 *
 * @component
 * @package Glyph
 * @version 1.0.0
 *
 * @param \App\Models\Channel $channel - The channel model instance
 * @param \App\Models\Server $server - The parent server model instance
 * @param bool $active - Whether this channel is currently selected (default: false)
 * @param bool $unread - Whether the channel has unread messages (default: false)
 * @param int $mentions - Number of unread mentions in the channel (default: 0)
 * @param bool $isPrivate - Whether this is a private/locked channel (default: false)
 * @param bool $canEdit - Whether to show the settings button on hover (default: false)
 * @param string|null $editRoute - Route for editing the channel settings
 *
 * @example Basic usage:
 * <x-text-channel-item :channel="$channel" :server="$server" />
 *
 * @example Active channel with unread mentions:
 * <x-text-channel-item
 *     :channel="$channel"
 *     :server="$server"
 *     :active="$currentChannel->id === $channel->id"
 *     :unread="true"
 *     :mentions="3"
 * />
 *
 * @example Private channel with edit capability:
 * <x-text-channel-item
 *     :channel="$channel"
 *     :server="$server"
 *     :isPrivate="true"
 *     :canEdit="$canManageChannels"
 *     :editRoute="route('server.admin.settings', $server) . '#channels'"
 * />
 */
--}}

@props([
    'channel',
    'server',
    'active' => false,
    'unread' => false,
    'mentions' => 0,
    'isPrivate' => false,
    'canEdit' => false,
    'editRoute' => null,
])

@php
    // Validate required props
    if (!isset($channel) || !isset($server)) {
        throw new \InvalidArgumentException('text-channel-item component requires $channel and $server props');
    }

    // Build CSS classes
    $classes = ['channel-item'];
    if ($active) {
        $classes[] = 'active';
    }
    if ($unread) {
        $classes[] = 'unread';
    }
    if ($isPrivate) {
        $classes[] = 'private';
    }

    // Generate edit route if not provided
    $settingsRoute = $editRoute ?? route('server.admin.settings', $server) . '#channels';
@endphp

<a
    href="{{ route('channel.show', [$server, $channel]) }}"
    class="{{ implode(' ', $classes) }}"
    @if($active) aria-current="page" @endif
    title="{{ $channel->name }}"
    data-channel-id="{{ $channel->id }}"
    data-channel-type="text"
>
    {{-- Channel Icon (Hashtag or Lock) --}}
    <span class="channel-icon" aria-hidden="true">
        @if($isPrivate)
            {{-- Lock Icon for Private Channels --}}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z"
                    clip-rule="evenodd"
                />
            </svg>
        @else
            {{-- Hashtag Icon for Text Channels --}}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M9.493 2.853a.75.75 0 00-1.486-.205L7.545 6H4.198a.75.75 0 000 1.5h3.14l-.69 5H3.302a.75.75 0 000 1.5h3.14l-.435 3.148a.75.75 0 001.486.205L7.955 14h4.997l-.435 3.148a.75.75 0 001.486.205l.461-3.353h3.348a.75.75 0 000-1.5h-3.14l.69-5h3.346a.75.75 0 000-1.5h-3.14l.435-3.147a.75.75 0 00-1.486-.205L14.045 6H9.048l.435-3.147zM8.84 7.5l-.69 5h4.997l.69-5H8.84z"
                    clip-rule="evenodd"
                />
            </svg>
        @endif
    </span>

    {{-- Channel Name --}}
    <span class="channel-name">{{ $channel->name }}</span>

    {{-- Mention Badge (if there are mentions) --}}
    @if($mentions > 0)
        <span
            class="channel-mention-badge {{ $mentions >= 10 ? 'high-priority' : '' }}"
            aria-label="{{ $mentions }} unread {{ $mentions === 1 ? 'mention' : 'mentions' }}"
        >
            {{ $mentions > 99 ? '99+' : $mentions }}
        </span>
    @endif

    {{-- Settings Button (visible on hover for authorized users) --}}
    @if($canEdit)
        <button
            class="channel-settings-btn"
            @click.prevent.stop="window.location.href = '{{ $settingsRoute }}'"
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

    {{-- Screen Reader Only: Unread Status --}}
    @if($unread)
        <span class="sidebar-sr-only">Unread messages</span>
    @endif
</a>
