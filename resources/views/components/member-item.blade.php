{{--
    Discord-style Member Item Component
    Phase 2: Member List Enhancement

    A member list item with:
    - Avatar with status dot overlay (bottom-right)
    - Username with role color
    - Activity display (Steam gaming status)
    - Voice badge
    - Lobby badge
    - Crown icon for server owner
    - Hover interactions with user card popup

    @param User $member - The member user object
    @param Server $server - The current server
    @param Role|null $role - The member's highest role (for color)
    @param bool $isOwner - Whether this member is the server owner
--}}

@props([
    'member',
    'server',
    'role' => null,
    'isOwner' => false,
])

@php
    // Get the current viewer for privacy checks
    $viewer = auth()->user();

    // Privacy-aware status (show 'offline' if hidden)
    $canSeeStatus = $member->profile->shouldShowOnlineStatus($viewer);
    $status = $canSeeStatus ? $member->getCurrentStatus() : 'offline';

    // Privacy-aware activity (hide if privacy enabled)
    $canSeeActivity = $member->profile->shouldShowGamingActivity($viewer);
    $activity = $canSeeActivity ? $member->getDisplayActivity() : null;

    // Privacy-aware lobby check
    $canSeeLobby = $member->profile->shouldShowLobbies($viewer);
    $memberLobbies = $canSeeLobby ? ($member->gameLobbies ?? collect()) : collect();
    $hasActiveLobby = $memberLobbies->filter(fn($l) => $l->isActive())->isNotEmpty();

    // Get role color with fallback
    $roleColor = $role->color ?? '#ffffff';

    // Check if playing a game (privacy-aware)
    $isPlaying = $canSeeActivity ? $member->isPlayingGame() : false;

    // Get custom status details (privacy-aware)
    $customStatus = $canSeeActivity ? $member->getCustomStatus() : null;
@endphp

<div
    class="member-item-enhanced"
    data-user-id="{{ $member->id }}"
    data-status="{{ $status }}"
    x-data="{ showCard: false }"
    @click.stop="showCard = !showCard"
    @keydown.escape.window="showCard = false"
    @keydown.enter="showCard = !showCard"
    tabindex="0"
    role="button"
    aria-label="View {{ $member->display_name }}'s profile"
>
    {{-- Avatar with Status Indicator --}}
    <div class="member-avatar-wrapper">
        <img
            src="{{ $member->profile->avatar_url ?? asset('images/default-avatar.png') }}"
            alt="{{ $member->display_name }}"
            class="member-avatar"
            loading="lazy"
        >
        {{-- Status Dot --}}
        <div class="member-status" data-status="{{ $status }}"></div>
        {{-- Voice Speaking Indicator (hidden by default, shown via JS) --}}
        <div
            class="voice-speaking-indicator"
            style="display: none;"
            data-voice-indicator="{{ $member->id }}"
        ></div>
    </div>

    {{-- Member Info --}}
    <div class="member-info">
        <div class="member-name-row">
            {{-- Crown for Server Owner --}}
            @if($isOwner)
                <span class="owner-crown" title="Server Owner">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 0L10.5 5H14L11.5 8.5L13.5 14H2.5L4.5 8.5L2 5H5.5L8 0Z"/>
                    </svg>
                </span>
            @endif

            {{-- Username --}}
            <span class="member-username" style="color: {{ $roleColor }}">
                {{ $member->display_name }}
            </span>

            {{-- Badges --}}
            <div class="member-badges">
                {{-- Lobby Badge --}}
                @if($hasActiveLobby)
                    <span class="member-badge lobby-badge" title="Has Active Lobby">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 6h-7.59l3.29-3.29L16 2l-4 4-4-4-.71.71L10.59 6H3c-1.1 0-2 .89-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.11-.9-2-2-2zm0 14H3V8h18v12z"/>
                        </svg>
                    </span>
                @endif

                {{-- In Voice Badge (shown via JS) --}}
                <span
                    class="member-badge in-voice-badge"
                    data-user-id="{{ $member->id }}"
                    style="display: none;"
                >
                    IN VOICE
                </span>
            </div>
        </div>

        {{-- Activity --}}
        @if($isPlaying && $member->profile->current_game)
            {{-- Gaming status with icon badge --}}
            <x-gaming-status-badge
                :user="$member"
                variant="inline"
                :show-details="false"
                :show-indicator="false"
            />
        @elseif($activity)
            {{-- Custom status or fallback activity --}}
            <div class="member-activity {{ $isPlaying ? 'member-activity--playing' : 'member-activity--custom' }}">
                @if($customStatus && $customStatus['emoji'])
                    <span class="member-activity-emoji">{{ $customStatus['emoji'] }}</span>
                @endif
                <span>{{ $activity }}</span>
            </div>
        @endif
    </div>

    {{-- Quick Actions (appear on hover) --}}
    <div class="member-actions">
        {{-- Message Button --}}
        <button
            class="member-action-btn"
            @click.stop="window.location.href='{{ route('dm.index') }}?user={{ $member->id }}'"
            title="Send Message"
            aria-label="Send message to {{ $member->display_name }}"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </button>
    </div>

    {{-- User Card Popover (Fixed Position - Discord Style) --}}
    <template x-teleport="body">
        <div
            x-show="showCard"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="showCard = false"
            x-init="$watch('showCard', value => {
                if (value) {
                    $nextTick(() => {
                        const rect = $root.getBoundingClientRect();
                        const card = $el;
                        const cardWidth = 340;

                        // Position to the left of the member sidebar
                        let left = rect.left - cardWidth - 12;

                        // Align TOP of card with TOP of clicked member (Discord style)
                        let top = rect.top;

                        // If card would go off left edge, show on right instead
                        if (left < 8) {
                            left = rect.right + 12;
                        }

                        // Measure actual card height after render
                        requestAnimationFrame(() => {
                            const cardHeight = card.offsetHeight;

                            // If card would go below viewport, push it up
                            if (top + cardHeight > window.innerHeight - 8) {
                                top = window.innerHeight - cardHeight - 8;
                            }

                            // Never go above viewport
                            if (top < 8) top = 8;

                            card.style.left = left + 'px';
                            card.style.top = top + 'px';
                        });

                        // Set initial position
                        card.style.left = left + 'px';
                        card.style.top = top + 'px';
                    });
                }
            })"
            style="position: fixed; z-index: 9999;"
            x-cloak
        >
            <x-user-member-card
                :user="$member"
                :server="$server"
                :roleColor="$roleColor"
            />
        </div>
    </template>
</div>
