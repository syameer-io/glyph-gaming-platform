{{--
/**
 * Voice User Item Component
 *
 * Displays a user connected to a voice channel in the sidebar.
 * Shows avatar with speaking indicator, username, and status icons
 * for mute/deafen/streaming states.
 *
 * @component
 * @package Glyph
 * @version 1.0.0
 *
 * @param \App\Models\User $user - The user in the voice channel
 * @param bool $isSpeaking - Whether the user is currently speaking (default: false)
 * @param bool $isMuted - Whether the user is muted (default: false)
 * @param bool $isDeafened - Whether the user is deafened (default: false)
 * @param bool $isStreaming - Whether the user is streaming/screen sharing (default: false)
 * @param bool $hasVideo - Whether the user has video enabled (default: false)
 * @param int $channelId - The voice channel ID
 *
 * @example Basic usage:
 * <x-voice-user-item :user="$user" :channelId="$channel->id" />
 *
 * @example With speaking indicator:
 * <x-voice-user-item
 *     :user="$user"
 *     :channelId="$channel->id"
 *     :isSpeaking="true"
 * />
 *
 * @example Full state:
 * <x-voice-user-item
 *     :user="$user"
 *     :channelId="$channel->id"
 *     :isSpeaking="false"
 *     :isMuted="true"
 *     :isStreaming="true"
 * />
 */
--}}

@props([
    'user',
    'isSpeaking' => false,
    'isMuted' => false,
    'isDeafened' => false,
    'isStreaming' => false,
    'hasVideo' => false,
    'channelId',
])

@php
    // Build CSS classes
    $itemClasses = ['voice-user-item'];
    if ($isSpeaking) {
        $itemClasses[] = 'speaking';
    }

    $avatarWrapperClasses = ['voice-user-avatar-wrapper'];
    if ($isSpeaking) {
        $avatarWrapperClasses[] = 'speaking';
    }
@endphp

<div
    class="{{ implode(' ', $itemClasses) }}"
    data-user-id="{{ $user->id }}"
    data-channel-id="{{ $channelId }}"
    role="listitem"
    tabindex="0"
    aria-label="{{ $user->display_name }}{{ $isSpeaking ? ', speaking' : '' }}{{ $isMuted ? ', muted' : '' }}{{ $isDeafened ? ', deafened' : '' }}{{ $isStreaming ? ', streaming' : '' }}"
>
    {{-- User Avatar with Speaking Ring and Status Badge --}}
    <div class="{{ implode(' ', $avatarWrapperClasses) }}">
        <img
            src="{{ $user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
            alt="{{ $user->display_name }}"
            class="voice-user-avatar"
            loading="lazy"
        >

        {{-- Muted Badge on Avatar (bottom-right) --}}
        @if($isMuted && !$isDeafened)
            <div class="voice-muted-badge" title="Muted">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.547 3.062A.75.75 0 0110 3.75v12.5a.75.75 0 01-1.264.546L4.703 13H3.167a.75.75 0 01-.7-.48A6.985 6.985 0 012 10c0-.887.165-1.737.468-2.52a.75.75 0 01.7-.48h1.535l4.033-3.796a.75.75 0 01.811-.142zM13.28 6.22a.75.75 0 10-1.06 1.06L13.94 9l-1.72 1.72a.75.75 0 001.06 1.06L15 10.06l1.72 1.72a.75.75 0 101.06-1.06L16.06 9l1.72-1.72a.75.75 0 00-1.06-1.06L15 7.94l-1.72-1.72z"/>
                </svg>
            </div>
        @endif

        {{-- Deafened Badge on Avatar (bottom-right, takes priority over muted) --}}
        @if($isDeafened)
            <div class="voice-deafened-badge" title="Deafened">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3.75a6.25 6.25 0 00-6.25 6.25v.002c0 .414.336.748.75.748h.75a.75.75 0 00.75-.75v-.001a4 4 0 118 0v.001a.75.75 0 00.75.75h.75a.75.75 0 00.75-.748V10a6.25 6.25 0 00-6.25-6.25z"/>
                    <path d="M3.505 14.505a.75.75 0 011.06 0L7.5 17.44l2.935-2.935a.75.75 0 011.06 1.06l-2.934 2.935 2.934 2.935a.75.75 0 01-1.06 1.06L7.5 19.56l-2.935 2.935a.75.75 0 01-1.06-1.06l2.934-2.935-2.934-2.935a.75.75 0 010-1.06z"/>
                </svg>
            </div>
        @endif
    </div>

    {{-- Username --}}
    <span class="voice-user-name">{{ Str::limit($user->display_name, 16) }}</span>

    {{-- Status Icons (right side) --}}
    <div class="voice-user-status-icons">
        {{-- Video Badge --}}
        @if($hasVideo)
            <div class="voice-video-badge" title="Video enabled">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3.25 4A2.25 2.25 0 001 6.25v7.5A2.25 2.25 0 003.25 16h7.5A2.25 2.25 0 0013 13.75v-7.5A2.25 2.25 0 0010.75 4h-7.5zM19 4.75a.75.75 0 00-1.28-.53l-3 3a.75.75 0 00-.22.53v4.5c0 .199.079.39.22.53l3 3a.75.75 0 001.28-.53V4.75z"/>
                </svg>
            </div>
        @endif

        {{-- Screen Share Badge --}}
        @if($isStreaming && !$hasVideo)
            <div class="voice-screen-badge" title="Screen sharing">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 014.25 2h11.5A2.25 2.25 0 0118 4.25v8.5A2.25 2.25 0 0115.75 15H4.25A2.25 2.25 0 012 12.75v-8.5zm11.47 4.72a.75.75 0 011.06 0l2 2a.75.75 0 010 1.06l-2 2a.75.75 0 11-1.06-1.06l.72-.72H9.75a.75.75 0 010-1.5h4.44l-.72-.72a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                    <path d="M9.25 16.5a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5z"/>
                </svg>
            </div>
        @endif

        {{-- LIVE Badge --}}
        @if($isStreaming)
            <span class="voice-live-badge">LIVE</span>
        @endif
    </div>

    {{-- Screen Reader Only --}}
    <span class="voice-sr-only">
        {{ $user->display_name }}
        @if($isSpeaking) is speaking @endif
        @if($isMuted) is muted @endif
        @if($isDeafened) is deafened @endif
        @if($isStreaming) is streaming @endif
    </span>
</div>
