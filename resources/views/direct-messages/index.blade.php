@extends('layouts.app')

@section('title', 'Messages - Glyph')

@push('styles')
<style>
    /* Custom scrollbar styling */
    .dm-scrollable::-webkit-scrollbar {
        width: 8px;
    }

    .dm-scrollable::-webkit-scrollbar-track {
        background: var(--color-surface);
    }

    .dm-scrollable::-webkit-scrollbar-thumb {
        background-color: var(--color-border-primary);
        border-radius: 4px;
    }

    .dm-scrollable::-webkit-scrollbar-thumb:hover {
        background-color: var(--color-text-faint);
    }

    /* Conversation item styling */
    .conversation-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.15s ease;
        text-decoration: none;
        color: inherit;
    }

    .conversation-item:hover {
        background-color: var(--color-bg-elevated);
    }

    .conversation-item.selected {
        background-color: var(--color-surface-active);
    }

    .conversation-item.unread {
        background-color: rgba(102, 126, 234, 0.1);
    }

    .conversation-item.unread:hover {
        background-color: rgba(102, 126, 234, 0.15);
    }

    /* Avatar with online status */
    .avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .avatar-wrapper .status-dot {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid var(--color-surface);
    }

    .avatar-wrapper .status-dot.online {
        background-color: #10b981;
    }

    .avatar-wrapper .status-dot.offline {
        background-color: #6b7280;
    }

    /* Unread badge */
    .unread-badge {
        background-color: #ef4444;
        color: white;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 9999px;
        min-width: 20px;
        text-align: center;
    }

    /* Modal styling */
    .dm-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .dm-modal-overlay.active {
        display: flex;
    }

    .dm-modal {
        background-color: var(--color-surface);
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 480px;
        border: 1px solid var(--color-border-primary);
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .dm-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .dm-modal-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--color-text-primary);
    }

    .dm-modal-close {
        background: none;
        border: none;
        color: var(--color-text-muted);
        font-size: 24px;
        cursor: pointer;
        padding: 4px;
        line-height: 1;
        transition: color 0.15s;
    }

    .dm-modal-close:hover {
        color: var(--color-text-primary);
    }

    /* Friend search in modal */
    .friend-search-input {
        width: 100%;
        padding: 12px 16px;
        background-color: var(--color-bg-primary);
        border: 2px solid var(--color-border-primary);
        border-radius: 8px;
        color: var(--color-text-primary);
        font-size: 14px;
        margin-bottom: 16px;
        transition: border-color 0.15s;
    }

    .friend-search-input:focus {
        outline: none;
        border-color: #667eea;
    }

    .friend-list {
        overflow-y: auto;
        max-height: 300px;
        flex: 1;
    }

    .friend-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.15s;
    }

    .friend-item:hover {
        background-color: var(--color-bg-elevated);
    }

    /* Keyboard navigation highlight */
    .friend-item.keyboard-focused,
    .conversation-item.keyboard-focused {
        background-color: var(--color-surface-active);
        outline: 2px solid #667eea;
        outline-offset: -2px;
    }

    /* Empty state */
    .dm-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        padding: 48px;
    }

    .dm-empty-state-icon {
        width: 80px;
        height: 80px;
        margin-bottom: 24px;
        opacity: 0.5;
    }

    /* Time formatting */
    .conversation-time {
        font-size: 12px;
        color: var(--color-text-muted);
        white-space: nowrap;
    }

    /* Message preview */
    .message-preview {
        font-size: 13px;
        color: var(--color-text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }

    .message-preview.unread {
        color: var(--color-text-primary);
        font-weight: 500;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .dm-layout {
            flex-direction: column;
        }

        .dm-sidebar {
            width: 100% !important;
            height: auto !important;
            max-height: 40vh;
            border-right: none !important;
            border-bottom: 1px solid var(--color-border-primary);
        }

        .dm-welcome {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div style="display: flex; flex-direction: column; height: 100vh;">
    <!-- Navigation Bar -->
    <x-navbar active-section="messages" />

    <!-- Alert Messages -->
    <div class="container" style="padding-top: 16px;">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Main Layout -->
    <div class="dm-layout" style="display: flex; flex: 1; overflow: hidden;">
        <!-- Left Sidebar: Conversations List -->
        <div class="dm-sidebar" style="width: 320px; background-color: var(--color-surface); border-right: 1px solid var(--color-border-primary); display: flex; flex-direction: column; height: 100%;">
            <!-- Header -->
            <div style="padding: 16px; border-bottom: 1px solid var(--color-border-primary);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h2 style="font-size: 20px; font-weight: 600; color: var(--color-text-primary); margin: 0;">Messages</h2>
                    <button id="newMessageBtn" class="btn btn-primary btn-sm" style="padding: 8px 12px;">
                        New Message
                    </button>
                </div>
                <!-- Search -->
                <div style="position: relative;">
                    <input
                        type="text"
                        id="conversationSearch"
                        placeholder="Search conversations..."
                        style="width: 100%; padding: 10px 16px 10px 40px; background-color: var(--color-bg-primary); border: 1px solid var(--color-border-primary); border-radius: 8px; color: var(--color-text-primary); font-size: 14px;"
                    >
                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--color-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Conversations List -->
            <div id="conversationsList" class="dm-scrollable" style="flex: 1; overflow-y: auto; padding: 8px;">
                @forelse($conversations as $conversation)
                    <a href="{{ route('dm.show', $conversation) }}"
                       class="conversation-item {{ $conversation->unread_count > 0 ? 'unread' : '' }}"
                       data-conversation-id="{{ $conversation->id }}"
                       data-user-name="{{ $conversation->other_participant->display_name }}"
                       data-username="{{ $conversation->other_participant->username }}">
                        <div class="avatar-wrapper">
                            <img
                                src="{{ $conversation->other_participant->profile->avatar_url ?? '/images/default-avatar.png' }}"
                                alt="{{ $conversation->other_participant->display_name }}"
                                style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;"
                            >
                            <span class="status-dot {{ $conversation->other_participant->profile->status === 'online' ? 'online' : 'offline' }}" data-user-status="{{ $conversation->other_participant->id }}"></span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-weight: 600; color: var(--color-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $conversation->other_participant->display_name }}
                                </span>
                                @if($conversation->latestMessage)
                                    <span class="conversation-time">
                                        {{ $conversation->latestMessage->created_at->diffForHumans(null, true, true) }}
                                    </span>
                                @endif
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                @if($conversation->latestMessage)
                                    <span class="message-preview {{ $conversation->unread_count > 0 ? 'unread' : '' }}">
                                        @if($conversation->latestMessage->sender_id === auth()->id())
                                            You:
                                        @endif
                                        {{ Str::limit($conversation->latestMessage->content, 30) }}
                                    </span>
                                @else
                                    <span class="message-preview" style="font-style: italic;">No messages yet</span>
                                @endif
                                @if($conversation->unread_count > 0)
                                    <span class="unread-badge">{{ $conversation->unread_count > 99 ? '99+' : $conversation->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="dm-empty-state" style="padding: 48px 24px;">
                        <svg class="dm-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <h3 style="color: var(--color-text-primary); margin-bottom: 8px;">No conversations yet</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 16px;">Start a conversation with one of your friends</p>
                        <button onclick="openNewMessageModal()" class="btn btn-primary">
                            Start a Conversation
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Panel: Welcome Message -->
        <div class="dm-welcome" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background-color: var(--color-bg-primary); padding: 48px;">
            <svg style="width: 120px; height: 120px; color: var(--color-border-primary); margin-bottom: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
            <h2 style="color: var(--color-text-primary); font-size: 24px; font-weight: 600; margin-bottom: 12px;">Your Messages</h2>
            <p style="color: var(--color-text-muted); text-align: center; max-width: 400px; margin-bottom: 24px;">
                Select a conversation from the sidebar or start a new one to begin messaging your friends.
            </p>
            <button onclick="openNewMessageModal()" class="btn btn-primary" style="padding: 12px 24px;">
                Start a New Conversation
            </button>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div id="newMessageModal" class="dm-modal-overlay">
    <div class="dm-modal">
        <div class="dm-modal-header">
            <h3 class="dm-modal-title">New Message</h3>
            <button class="dm-modal-close" onclick="closeNewMessageModal()">&times;</button>
        </div>
        <input
            type="text"
            id="friendSearchInput"
            class="friend-search-input"
            placeholder="Search friends..."
            autocomplete="off"
        >
        <div id="friendsList" class="friend-list dm-scrollable">
            @php
                $friends = auth()->user()->friends()
                    ->wherePivot('status', 'accepted')
                    ->with('profile')
                    ->get();
            @endphp
            @forelse($friends as $friend)
                <a href="{{ route('dm.with', $friend) }}"
                   class="friend-item"
                   data-friend-name="{{ $friend->display_name }}"
                   data-friend-username="{{ $friend->username }}">
                    <div class="avatar-wrapper">
                        <img
                            src="{{ $friend->profile->avatar_url ?? '/images/default-avatar.png' }}"
                            alt="{{ $friend->display_name }}"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                        >
                        <span class="status-dot {{ $friend->profile->status === 'online' ? 'online' : 'offline' }}" style="width: 10px; height: 10px;" data-user-status="{{ $friend->id }}"></span>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--color-text-primary);">{{ $friend->display_name }}</div>
                        <div style="font-size: 13px; color: var(--color-text-muted);">{{ '@' . $friend->username }}</div>
                    </div>
                    @if($friend->profile->status === 'online')
                        <span style="font-size: 12px; color: #10b981;">Online</span>
                    @endif
                </a>
            @empty
                <div style="text-align: center; padding: 24px; color: var(--color-text-muted);">
                    <p style="margin-bottom: 12px;">No friends yet</p>
                    <a href="{{ route('friends.search') }}" class="btn btn-secondary btn-sm">Find Friends</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const newMessageBtn = document.getElementById('newMessageBtn');
    const newMessageModal = document.getElementById('newMessageModal');
    const friendSearchInput = document.getElementById('friendSearchInput');
    const conversationSearch = document.getElementById('conversationSearch');
    const conversationsList = document.getElementById('conversationsList');
    const friendsList = document.getElementById('friendsList');

    let keyboardFocusIndex = -1;
    let currentFocusableItems = [];

    // New Message Modal Functions
    window.openNewMessageModal = function() {
        newMessageModal.classList.add('active');
        friendSearchInput.value = '';
        filterFriends('');
        friendSearchInput.focus();
        keyboardFocusIndex = -1;
        updateKeyboardFocus();
    };

    window.closeNewMessageModal = function() {
        newMessageModal.classList.remove('active');
        keyboardFocusIndex = -1;
        updateKeyboardFocus();
    };

    if (newMessageBtn) {
        newMessageBtn.addEventListener('click', openNewMessageModal);
    }

    // Close modal when clicking outside
    newMessageModal.addEventListener('click', function(e) {
        if (e.target === newMessageModal) {
            closeNewMessageModal();
        }
    });

    // Friend Search Filter
    friendSearchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        filterFriends(query);
        keyboardFocusIndex = -1;
        updateKeyboardFocus();
    });

    function filterFriends(query) {
        const friendItems = friendsList.querySelectorAll('.friend-item');
        friendItems.forEach(item => {
            const name = item.dataset.friendName?.toLowerCase() || '';
            const username = item.dataset.friendUsername?.toLowerCase() || '';
            if (name.includes(query) || username.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Conversation Search Filter
    conversationSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const conversationItems = conversationsList.querySelectorAll('.conversation-item');
        conversationItems.forEach(item => {
            const name = item.dataset.userName?.toLowerCase() || '';
            const username = item.dataset.username?.toLowerCase() || '';
            if (name.includes(query) || username.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
        keyboardFocusIndex = -1;
        updateKeyboardFocus();
    });

    // Keyboard Navigation
    document.addEventListener('keydown', function(e) {
        // Handle Escape key
        if (e.key === 'Escape') {
            if (newMessageModal.classList.contains('active')) {
                closeNewMessageModal();
            }
            return;
        }

        // Determine which list we're navigating
        let items, container;
        if (newMessageModal.classList.contains('active')) {
            items = Array.from(friendsList.querySelectorAll('.friend-item')).filter(item => item.style.display !== 'none');
            container = friendsList;
        } else if (document.activeElement === conversationSearch || conversationSearch.contains(document.activeElement)) {
            items = Array.from(conversationsList.querySelectorAll('.conversation-item')).filter(item => item.style.display !== 'none');
            container = conversationsList;
        } else {
            return;
        }

        currentFocusableItems = items;

        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            keyboardFocusIndex = Math.min(keyboardFocusIndex + 1, items.length - 1);
            updateKeyboardFocus();
            scrollIntoViewIfNeeded(items[keyboardFocusIndex], container);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            keyboardFocusIndex = Math.max(keyboardFocusIndex - 1, 0);
            updateKeyboardFocus();
            scrollIntoViewIfNeeded(items[keyboardFocusIndex], container);
        } else if (e.key === 'Enter' && keyboardFocusIndex >= 0) {
            e.preventDefault();
            const focusedItem = items[keyboardFocusIndex];
            if (focusedItem && focusedItem.href) {
                window.location.href = focusedItem.href;
            }
        }
    });

    function updateKeyboardFocus() {
        // Remove focus from all items
        document.querySelectorAll('.keyboard-focused').forEach(item => {
            item.classList.remove('keyboard-focused');
        });

        // Add focus to current item
        if (keyboardFocusIndex >= 0 && currentFocusableItems[keyboardFocusIndex]) {
            currentFocusableItems[keyboardFocusIndex].classList.add('keyboard-focused');
        }
    }

    function scrollIntoViewIfNeeded(element, container) {
        if (!element || !container) return;

        const elementRect = element.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();

        if (elementRect.bottom > containerRect.bottom) {
            element.scrollIntoView({ block: 'end', behavior: 'smooth' });
        } else if (elementRect.top < containerRect.top) {
            element.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    }

    // Real-time updates via Echo (if available)
    const userId = {{ auth()->id() }};
    let indexChannelSubscribed = false;

    function setupIndexChannel() {
        if (indexChannelSubscribed) {
            console.log('[DM Index] Channel already subscribed, skipping...');
            return;
        }

        if (!window.Echo) {
            console.warn('[DM Index] Echo not available yet');
            return;
        }

        console.log('[DM Index] Setting up Echo listener for user channel:', userId);
        indexChannelSubscribed = true;

        // Listen for new DM notifications on user's private channel
        window.Echo.private(`user.${userId}`)
            .listen('.dm.new', (e) => {
                console.log('[DM Index] New DM received:', e);
                // Reload to show new conversation or update existing
                location.reload();
            })
            .listen('.dm.read', (e) => {
                console.log('[DM Index] DM read:', e);
                // Update unread badge for specific conversation
                updateConversationUnreadBadge(e.conversation_id, 0);
            });

        console.log('[DM Index] Channel subscription initiated');
    }

    // Setup presence channel for online status tracking
    let presenceChannelSubscribed = false;

    function setupPresenceChannel() {
        if (presenceChannelSubscribed) {
            console.log('[DM Index] Presence channel already subscribed, skipping...');
            return;
        }

        if (!window.Echo) {
            console.warn('[DM Index] Echo not available for presence channel');
            return;
        }

        console.log('[DM Index] Setting up presence channel for online status');
        presenceChannelSubscribed = true;

        window.Echo.join('presence.dm')
            .here((users) => {
                console.log('[DM Index] Online users:', users);
                users.forEach(user => updateUserOnlineStatus(user.id, true));
            })
            .joining((user) => {
                console.log('[DM Index] User came online:', user);
                updateUserOnlineStatus(user.id, true);
            })
            .leaving((user) => {
                console.log('[DM Index] User went offline:', user);
                updateUserOnlineStatus(user.id, false);
            })
            .error((error) => {
                console.error('[DM Index] Presence channel error:', error);
            });
    }

    function updateUserOnlineStatus(userId, isOnline) {
        // Update status dots in conversation list
        const statusDots = document.querySelectorAll(`[data-user-status="${userId}"]`);
        statusDots.forEach(el => {
            el.classList.remove('online', 'offline');
            el.classList.add(isOnline ? 'online' : 'offline');
        });
    }

    // Try to set up channel immediately if Echo is ready
    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
        const pusherConnection = window.Echo.connector.pusher.connection;

        if (pusherConnection.state === 'connected') {
            setupIndexChannel();
            setupPresenceChannel();
        } else {
            pusherConnection.bind('connected', () => {
                setupIndexChannel();
                setupPresenceChannel();
            });
        }
    } else {
        // Echo not initialized yet, listen for the custom event
        window.addEventListener('echo:connected', () => {
            setupIndexChannel();
            setupPresenceChannel();
        });
    }

    function updateConversationUnreadBadge(conversationId, count) {
        const conversationItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
        if (!conversationItem) return;

        const badge = conversationItem.querySelector('.unread-badge');
        if (count > 0) {
            if (badge) {
                badge.textContent = count > 99 ? '99+' : count;
            } else {
                // Create badge if it doesn't exist
                const newBadge = document.createElement('span');
                newBadge.className = 'unread-badge';
                newBadge.textContent = count > 99 ? '99+' : count;
                conversationItem.querySelector('.message-preview').parentElement.appendChild(newBadge);
            }
            conversationItem.classList.add('unread');
        } else {
            if (badge) badge.remove();
            conversationItem.classList.remove('unread');
        }
    }
});
</script>
@endpush
