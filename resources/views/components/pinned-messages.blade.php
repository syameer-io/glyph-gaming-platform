{{--
    Discord-style Pinned Messages Popover Component
    Phase 3: Server Header & Navigation

    A popover showing pinned messages with:
    - List of pinned messages
    - Unpin action (with permission)
    - Jump to message action
    - Empty state

    @param Channel $channel - The current channel
    @param Server $server - The current server
--}}

@props([
    'channel',
    'server',
])

@php
    // Get pinned messages for this channel
    $pinnedMessages = $channel->messages()
        ->where('is_pinned', true)
        ->with(['user.profile'])
        ->orderBy('pinned_at', 'desc')
        ->limit(50)
        ->get();

    $canUnpin = auth()->user()->isServerAdmin($server->id);
@endphp

<div
    {{ $attributes->merge(['class' => 'pinned-messages-popover']) }}
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak
>
    {{-- Header --}}
    <div class="pinned-header">
        <h3 class="pinned-title">Pinned Messages</h3>
        @if($pinnedMessages->count() > 0)
            <span class="pinned-count">{{ $pinnedMessages->count() }}</span>
        @endif
    </div>

    {{-- Pinned Messages List --}}
    <div class="pinned-list">
        @forelse($pinnedMessages as $message)
            <div class="pinned-message" data-message-id="{{ $message->id }}">
                <img
                    src="{{ $message->user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                    alt="{{ $message->user->display_name }}"
                    class="pinned-avatar"
                >
                <div class="pinned-content">
                    <div class="pinned-meta">
                        <span class="pinned-author">{{ $message->user->display_name }}</span>
                        <span class="pinned-time">{{ $message->created_at->format('M j, Y') }}</span>
                    </div>
                    <div class="pinned-text">{{ Str::limit($message->content, 200) }}</div>
                </div>
                <div class="pinned-actions">
                    {{-- Jump to Message --}}
                    <button
                        class="pinned-action-btn"
                        title="Jump to message"
                        onclick="jumpToMessage({{ $message->id }})"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>

                    {{-- Unpin (Admin Only) --}}
                    @if($canUnpin)
                        <button
                            class="pinned-action-btn"
                            title="Unpin message"
                            onclick="unpinMessage({{ $message->id }})"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="pinned-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <div class="pinned-empty-title">No pinned messages</div>
                <div class="pinned-empty-text">Pin important messages to find them later</div>
            </div>
        @endforelse
    </div>
</div>

<script>
function jumpToMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        messageElement.style.animation = 'highlight-pulse 1.5s ease';
        setTimeout(() => {
            messageElement.style.animation = '';
        }, 1500);
    }
}

async function unpinMessage(messageId) {
    if (!confirm('Are you sure you want to unpin this message?')) {
        return;
    }

    try {
        const response = await fetch(`/api/messages/${messageId}/unpin`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            // Remove the message from the list
            const messageElement = document.querySelector(`.pinned-message[data-message-id="${messageId}"]`);
            if (messageElement) {
                messageElement.remove();
            }

            // Update count or show empty state
            const pinnedList = document.querySelector('.pinned-list');
            if (pinnedList && pinnedList.querySelectorAll('.pinned-message').length === 0) {
                pinnedList.innerHTML = `
                    <div class="pinned-empty">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        <div class="pinned-empty-title">No pinned messages</div>
                        <div class="pinned-empty-text">Pin important messages to find them later</div>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Failed to unpin message:', error);
    }
}
</script>

<style>
@keyframes highlight-pulse {
    0% {
        background-color: transparent;
    }
    25% {
        background-color: rgba(88, 101, 242, 0.2);
    }
    75% {
        background-color: rgba(88, 101, 242, 0.2);
    }
    100% {
        background-color: transparent;
    }
}
</style>
