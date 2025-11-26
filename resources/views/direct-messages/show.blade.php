@extends('layouts.app')

@section('title', 'Chat with ' . $otherParticipant->display_name . ' - Glyph')

@push('styles')
<style>
    /* Custom scrollbar styling */
    .dm-scrollable::-webkit-scrollbar {
        width: 8px;
    }

    .dm-scrollable::-webkit-scrollbar-track {
        background: #18181b;
    }

    .dm-scrollable::-webkit-scrollbar-thumb {
        background-color: #3f3f46;
        border-radius: 4px;
    }

    .dm-scrollable::-webkit-scrollbar-thumb:hover {
        background-color: #52525b;
    }

    /* Message styling */
    .dm-message {
        display: flex;
        gap: 16px;
        padding: 8px 16px;
        transition: background-color 0.1s;
    }

    .dm-message:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .dm-message:hover .message-actions {
        opacity: 1 !important;
    }

    .dm-message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        flex-shrink: 0;
        object-fit: cover;
    }

    .dm-message-header {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 4px;
    }

    .dm-message-author {
        font-weight: 600;
        color: #efeff1;
    }

    .dm-message-timestamp {
        font-size: 12px;
        color: #71717a;
    }

    .dm-message-content {
        color: #b3b3b5;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    .dm-message-edited {
        font-size: 12px;
        color: #71717a;
        font-style: italic;
    }

    /* Chat input */
    .dm-chat-input-container {
        padding: 16px;
        background-color: #0e0e10;
        border-top: 1px solid #3f3f46;
    }

    .dm-chat-input {
        width: 100%;
        padding: 12px 16px;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        color: #efeff1;
        font-size: 16px;
        resize: none;
        font-family: inherit;
        max-height: 120px;
    }

    .dm-chat-input:focus {
        outline: none;
        border-color: #667eea;
    }

    .dm-chat-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Kebab menu */
    .dm-kebab-menu {
        position: relative;
        display: inline-block;
    }

    .dm-kebab-button {
        background: none;
        border: none;
        color: #71717a;
        font-size: 16px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .dm-kebab-button:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }

    .dm-kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        min-width: 100px;
        z-index: 1000;
        display: none;
    }

    .dm-kebab-dropdown.active {
        display: block;
    }

    .dm-kebab-option {
        display: block;
        width: 100%;
        padding: 8px 12px;
        background: none;
        border: none;
        color: #ffffff;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .dm-kebab-option:hover {
        background-color: #3f3f46;
    }

    .dm-kebab-option:first-child {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .dm-kebab-option:last-child {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .dm-kebab-option.danger {
        color: #f87171;
    }

    .dm-kebab-option.danger:hover {
        background-color: #dc2626;
        color: #ffffff;
    }

    /* Typing indicator */
    .dm-typing-indicator {
        padding: 8px 16px;
        font-size: 13px;
        color: #71717a;
        font-style: italic;
        display: none;
    }

    .dm-typing-indicator.active {
        display: block;
    }

    /* Avatar with status */
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
        border: 2px solid #18181b;
    }

    .avatar-wrapper .status-dot.online {
        background-color: #10b981;
    }

    .avatar-wrapper .status-dot.offline {
        background-color: #6b7280;
    }

    /* Conversation list styling */
    .conversation-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.15s;
        text-decoration: none;
        color: inherit;
    }

    .conversation-item:hover {
        background-color: #27272a;
    }

    .conversation-item.selected {
        background-color: #3f3f46;
    }

    .conversation-item.unread {
        background-color: rgba(102, 126, 234, 0.1);
    }

    /* Load more button */
    .load-more-btn {
        width: 100%;
        padding: 12px;
        background-color: #27272a;
        border: none;
        border-radius: 8px;
        color: #efeff1;
        cursor: pointer;
        font-size: 14px;
        margin-bottom: 16px;
        transition: background-color 0.15s;
    }

    .load-more-btn:hover {
        background-color: #3f3f46;
    }

    .load-more-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Empty messages */
    .dm-empty-messages {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        padding: 48px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dm-sidebar {
            display: none;
        }

        .dm-chat-area {
            width: 100% !important;
        }
    }
</style>
@endpush

@section('content')
<div style="display: flex; height: 100vh;">
    <!-- Left Sidebar: Conversations List (same as index) -->
    <div class="dm-sidebar" style="width: 320px; background-color: #18181b; border-right: 1px solid #3f3f46; display: flex; flex-direction: column;">
        <!-- Header -->
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 style="font-size: 20px; font-weight: 600; color: #efeff1; margin: 0;">Messages</h2>
                <a href="{{ route('dm.index') }}" class="btn btn-secondary btn-sm" style="padding: 6px 12px; text-decoration: none;">
                    Back
                </a>
            </div>
        </div>

        <!-- Conversations List -->
        <div class="dm-scrollable" style="flex: 1; overflow-y: auto; padding: 8px;">
            @php
                $allConversations = \App\Models\Conversation::forUser(auth()->id())
                    ->with(['userOne.profile', 'userTwo.profile', 'latestMessage.sender'])
                    ->get()
                    ->map(function ($conv) {
                        $conv->other_participant = $conv->getOtherParticipant(auth()->user());
                        $conv->unread_count = $conv->getUnreadCountFor(auth()->id());
                        return $conv;
                    });
            @endphp
            @foreach($allConversations as $conv)
                <a href="{{ route('dm.show', $conv) }}"
                   class="conversation-item {{ $conv->id === $conversation->id ? 'selected' : '' }} {{ $conv->unread_count > 0 ? 'unread' : '' }}">
                    <div class="avatar-wrapper">
                        <img
                            src="{{ $conv->other_participant->profile->avatar_url ?? '/images/default-avatar.png' }}"
                            alt="{{ $conv->other_participant->display_name }}"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                        >
                        <span class="status-dot {{ $conv->other_participant->profile->status === 'online' ? 'online' : 'offline' }}" style="width: 10px; height: 10px;"></span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: #efeff1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $conv->other_participant->display_name }}
                        </div>
                        @if($conv->latestMessage)
                            <div style="font-size: 12px; color: #71717a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ Str::limit($conv->latestMessage->content, 25) }}
                            </div>
                        @endif
                    </div>
                    @if($conv->unread_count > 0)
                        <span style="background-color: #ef4444; color: white; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 9999px;">
                            {{ $conv->unread_count }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <!-- Chat Area -->
    <div class="dm-chat-area" style="flex: 1; display: flex; flex-direction: column; background-color: #0e0e10;">
        <!-- Chat Header -->
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46; background-color: #18181b; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="{{ route('dm.index') }}" style="color: #71717a; text-decoration: none; padding: 8px; border-radius: 6px; display: none;" class="mobile-back-btn">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="avatar-wrapper">
                    <img
                        src="{{ $otherParticipant->profile->avatar_url ?? '/images/default-avatar.png' }}"
                        alt="{{ $otherParticipant->display_name }}"
                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                    >
                    <span class="status-dot {{ $otherParticipant->profile->status === 'online' ? 'online' : 'offline' }}"></span>
                </div>
                <div>
                    <a href="{{ route('profile.show', $otherParticipant->username) }}" style="font-weight: 600; color: #efeff1; text-decoration: none; display: block;">
                        {{ $otherParticipant->display_name }}
                    </a>
                    <span style="font-size: 13px; color: #71717a;">
                        @if($otherParticipant->profile->status === 'online')
                            Online
                        @else
                            Offline
                        @endif
                    </span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <a href="{{ route('profile.show', $otherParticipant->username) }}" class="btn btn-secondary btn-sm" style="text-decoration: none;">
                    View Profile
                </a>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="messages-container" class="dm-scrollable" style="flex: 1; overflow-y: auto; padding: 16px;">
            @if($messages->count() >= 50)
                <button id="load-more-btn" class="load-more-btn" onclick="loadMoreMessages()">
                    Load older messages
                </button>
            @endif

            @forelse($messages as $message)
                <div class="dm-message" data-message-id="{{ $message->id }}" data-sender-id="{{ $message->sender_id }}">
                    <img
                        src="{{ $message->sender->profile->avatar_url ?? '/images/default-avatar.png' }}"
                        alt="{{ $message->sender->display_name }}"
                        class="dm-message-avatar"
                    >
                    <div style="flex: 1;">
                        <div class="dm-message-header">
                            <span class="dm-message-author">{{ $message->sender->display_name }}</span>
                            <span class="dm-message-timestamp" data-timestamp="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, g:i A') }}</span>
                            @if($message->is_edited)
                                <span class="dm-message-edited">(edited)</span>
                            @endif
                        </div>
                        <div class="dm-message-content">{{ $message->content }}</div>
                    </div>
                    @if($message->sender_id === auth()->id())
                        <div class="message-actions" style="opacity: 0; transition: opacity 0.2s;">
                            <div class="dm-kebab-menu">
                                <button class="dm-kebab-button" onclick="toggleMessageMenu({{ $message->id }})" title="More options">&#8942;</button>
                                <div class="dm-kebab-dropdown" id="message-menu-{{ $message->id }}">
                                    <button class="dm-kebab-option" onclick="editMessage({{ $message->id }})">Edit</button>
                                    <button class="dm-kebab-option danger" onclick="deleteMessage({{ $message->id }})">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="dm-empty-messages">
                    <svg style="width: 80px; height: 80px; color: #3f3f46; margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 style="color: #efeff1; margin-bottom: 8px;">Start the conversation</h3>
                    <p style="color: #71717a;">Send a message to {{ $otherParticipant->display_name }}</p>
                </div>
            @endforelse
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="dm-typing-indicator">
            <span id="typing-user"></span> is typing...
        </div>

        <!-- Chat Input -->
        <div class="dm-chat-input-container">
            <form id="message-form" onsubmit="sendMessage(event)">
                <textarea
                    id="message-input"
                    class="dm-chat-input"
                    placeholder="Message {{ $otherParticipant->display_name }}..."
                    rows="1"
                    maxlength="2000"
                    onkeydown="handleKeyDown(event)"
                    oninput="handleTyping()"
                ></textarea>
            </form>
        </div>
    </div>
</div>

<!-- Edit Message Modal -->
<div id="edit-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.8); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background-color: #18181b; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; border: 1px solid #3f3f46;">
        <h3 style="margin-bottom: 16px; color: #efeff1;">Edit Message</h3>
        <form id="edit-form" onsubmit="saveEdit(event)">
            <textarea
                id="edit-content"
                style="width: 100%; min-height: 80px; padding: 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 8px; color: #efeff1; font-size: 16px; resize: vertical; font-family: inherit;"
                placeholder="Edit your message..."
                maxlength="2000"
                required
            ></textarea>
            <div style="display: flex; gap: 12px; margin-top: 16px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const messagesContainer = document.getElementById('messages-container');
const messageInput = document.getElementById('message-input');
const typingIndicator = document.getElementById('typing-indicator');
const typingUser = document.getElementById('typing-user');
const currentUserId = {{ auth()->id() }};
const conversationId = {{ $conversation->id }};
const otherUserName = @json($otherParticipant->display_name);
const otherUserId = {{ $otherParticipant->id }};

let currentEditingMessage = null;
let typingTimeout = null;
let isTyping = false;
let oldestMessageId = {{ $messages->first()?->id ?? 'null' }};
let hasMoreMessages = {{ $messages->count() >= 50 ? 'true' : 'false' }};

// Initialize Echo listener for DM
// IMPORTANT: Backend broadcasts to dm.user.{userId} channel, NOT dm.{conversationId}
// This ensures users only receive messages on their own private channel

let dmChannelSubscribed = false;

function setupDMChannel() {
    if (dmChannelSubscribed) {
        console.log('[DM] Channel already subscribed, skipping...');
        return;
    }

    if (!window.Echo) {
        console.warn('[DM] Echo not available yet, waiting for initialization...');
        return;
    }

    console.log('[DM] Setting up Echo listener for user DM channel:', currentUserId);
    console.log('[DM] Current conversation ID:', conversationId);

    dmChannelSubscribed = true;

    // Subscribe to user's personal DM channel (matches backend broadcast channel)
    const dmChannel = window.Echo.private(`dm.user.${currentUserId}`);

    dmChannel.listen('.dm.message.posted', (e) => {
        console.log('[DM] Received message broadcast:', e);
        // Only process messages for THIS conversation
        if (e.conversation_id === conversationId) {
            // Avoid duplicating messages we sent ourselves
            if (e.message.sender_id !== currentUserId) {
                appendMessage(e.message);
                markAsRead();
            }
        }
    })
    .listen('.dm.message.edited', (e) => {
        console.log('[DM] Received edit broadcast:', e);
        // Only process edits for THIS conversation
        if (e.conversation_id === conversationId) {
            updateMessage(e.message);
        }
    })
    .listen('.dm.message.deleted', (e) => {
        console.log('[DM] Received delete broadcast:', e);
        // Only process deletions for THIS conversation
        if (e.conversation_id === conversationId) {
            removeMessage(e.message_id);
        }
    })
    .listen('.dm.user.typing', (e) => {
        console.log('[DM] Received typing broadcast:', e);
        // Only show typing for THIS conversation and from OTHER user
        if (e.conversation_id === conversationId && e.user.id !== currentUserId) {
            showTypingIndicator(e.user.display_name, e.is_typing);
        }
    })
    .listen('.dm.read', (e) => {
        console.log('[DM] Received read receipt:', e);
        // Could update read receipts UI here
    })
    .error((error) => {
        console.error('[DM] Echo channel error:', error);
    });

    console.log('[DM] Channel subscription initiated');
}

// Try to set up channel immediately if Echo is ready
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    const pusherConnection = window.Echo.connector.pusher.connection;

    // Check if already connected
    if (pusherConnection.state === 'connected') {
        console.log('[DM] Echo already connected, setting up channel...');
        setupDMChannel();
    } else {
        // Wait for connection
        console.log('[DM] Waiting for Echo connection (current state:', pusherConnection.state, ')');
        pusherConnection.bind('connected', () => {
            console.log('[DM] WebSocket connected, setting up channel...');
            setupDMChannel();
        });
    }

    // Log connection status changes
    pusherConnection.bind('disconnected', () => {
        console.warn('[DM] WebSocket disconnected');
    });

    pusherConnection.bind('unavailable', () => {
        console.error('[DM] WebSocket unavailable - real-time messaging disabled');
    });

    pusherConnection.bind('failed', () => {
        console.error('[DM] WebSocket connection failed - real-time messaging disabled');
    });
} else {
    // Echo not initialized yet, listen for the custom event from bootstrap.js
    console.log('[DM] Echo not initialized yet, waiting for echo:connected event...');

    window.addEventListener('echo:connected', () => {
        console.log('[DM] Received echo:connected event, setting up channel...');
        setupDMChannel();
    });

    // Also handle case where Echo becomes available but hasn't connected yet
    window.addEventListener('echo:failed', () => {
        console.error('[DM] Echo initialization failed - real-time messaging will not work');
    });
}

// Handle key down for sending messages
function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage(event);
    }
}

// Handle typing indicator
function handleTyping() {
    if (!isTyping) {
        isTyping = true;
        sendTypingIndicator(true);
    }

    // Clear existing timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }

    // Set new timeout to stop typing indicator
    typingTimeout = setTimeout(() => {
        isTyping = false;
        sendTypingIndicator(false);
    }, 2000);
}

// Send typing indicator to server
function sendTypingIndicator(typing) {
    fetch(`/dm/${conversationId}/typing`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ is_typing: typing })
    }).catch(err => console.log('Typing indicator error:', err));
}

// Show typing indicator
function showTypingIndicator(userName, isTyping) {
    if (isTyping) {
        typingUser.textContent = userName;
        typingIndicator.classList.add('active');
    } else {
        typingIndicator.classList.remove('active');
    }
}

// Send message
function sendMessage(event) {
    event.preventDefault();

    const content = messageInput.value.trim();
    if (!content) return;

    // Disable input while sending
    messageInput.disabled = true;

    // Stop typing indicator
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    isTyping = false;
    sendTypingIndicator(false);

    fetch(`/dm/${conversationId}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appendMessage(data.message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
        } else {
            alert(data.error || 'Failed to send message');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    })
    .finally(() => {
        messageInput.disabled = false;
        messageInput.focus();
    });
}

// Append message to container
function appendMessage(message) {
    // Check if message already exists
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) {
        console.log('Message already exists, skipping:', message.id);
        return;
    }

    // Remove empty state if present
    const emptyState = messagesContainer.querySelector('.dm-empty-messages');
    if (emptyState) {
        emptyState.remove();
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = 'dm-message';
    messageDiv.dataset.messageId = message.id;
    messageDiv.dataset.senderId = message.sender_id;

    // Use formatTimestamp for consistency with server-rendered messages
    const time = formatTimestamp(message.created_at);

    const editedTag = message.is_edited ? '<span class="dm-message-edited">(edited)</span>' : '';
    const actionsHtml = message.sender_id === currentUserId ? `
        <div class="message-actions" style="opacity: 0; transition: opacity 0.2s;">
            <div class="dm-kebab-menu">
                <button class="dm-kebab-button" onclick="toggleMessageMenu(${message.id})" title="More options">&#8942;</button>
                <div class="dm-kebab-dropdown" id="message-menu-${message.id}">
                    <button class="dm-kebab-option" onclick="editMessage(${message.id})">Edit</button>
                    <button class="dm-kebab-option danger" onclick="deleteMessage(${message.id})">Delete</button>
                </div>
            </div>
        </div>
    ` : '';

    messageDiv.innerHTML = `
        <img src="${message.sender.avatar_url || '/images/default-avatar.png'}" alt="${escapeHtml(message.sender.display_name)}" class="dm-message-avatar">
        <div style="flex: 1;">
            <div class="dm-message-header">
                <span class="dm-message-author">${escapeHtml(message.sender.display_name)}</span>
                <span class="dm-message-timestamp" data-timestamp="${message.created_at}">${time}</span>
                ${editedTag}
            </div>
            <div class="dm-message-content">${escapeHtml(message.content)}</div>
        </div>
        ${actionsHtml}
    `;

    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Update message (for edits)
function updateMessage(message) {
    const messageElement = document.querySelector(`[data-message-id="${message.id}"]`);
    if (!messageElement) return;

    const messageContent = messageElement.querySelector('.dm-message-content');
    const messageHeader = messageElement.querySelector('.dm-message-header');

    messageContent.textContent = message.content;

    // Update or add edited tag
    let editedTag = messageHeader.querySelector('.dm-message-edited');
    if (message.is_edited) {
        if (!editedTag) {
            editedTag = document.createElement('span');
            editedTag.className = 'dm-message-edited';
            editedTag.textContent = '(edited)';
            messageHeader.appendChild(editedTag);
        }
    }
}

// Remove message
function removeMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.remove();
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle message menu
function toggleMessageMenu(messageId) {
    document.querySelectorAll('.dm-kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'message-menu-' + messageId) {
            dropdown.classList.remove('active');
        }
    });

    const menu = document.getElementById('message-menu-' + messageId);
    menu.classList.toggle('active');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dm-kebab-menu')) {
        document.querySelectorAll('.dm-kebab-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});

// Edit message
function editMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) return;

    const messageContent = messageElement.querySelector('.dm-message-content').textContent;
    const editModal = document.getElementById('edit-modal');
    const editContent = document.getElementById('edit-content');

    currentEditingMessage = messageId;
    editContent.value = messageContent;
    editModal.style.display = 'flex';
    editContent.focus();

    // Close the menu
    const menu = document.getElementById('message-menu-' + messageId);
    if (menu) menu.classList.remove('active');
}

// Cancel edit
function cancelEdit() {
    const editModal = document.getElementById('edit-modal');
    editModal.style.display = 'none';
    currentEditingMessage = null;
}

// Save edit
function saveEdit(event) {
    event.preventDefault();

    if (!currentEditingMessage) return;

    const editContent = document.getElementById('edit-content');
    const newContent = editContent.value.trim();

    if (!newContent) {
        alert('Message cannot be empty');
        return;
    }

    const editForm = document.getElementById('edit-form');
    const submitBtn = editForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    fetch(`/dm/${conversationId}/messages/${currentEditingMessage}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content: newContent })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateMessage(data.message);
            cancelEdit();
        } else {
            alert(data.error || 'Failed to edit message');
        }
    })
    .catch(error => {
        console.error('Error editing message:', error);
        alert('Failed to edit message. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save';
    });
}

// Delete message
function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) return;

    fetch(`/dm/${conversationId}/messages/${messageId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            removeMessage(data.message_id);
        } else {
            alert(data.error || 'Failed to delete message');
        }
    })
    .catch(error => {
        console.error('Error deleting message:', error);
        alert('Failed to delete message. Please try again.');
    });
}

// Mark conversation as read
function markAsRead() {
    fetch(`/dm/${conversationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).catch(err => console.log('Mark as read error:', err));
}

// Load more messages (infinite scroll)
function loadMoreMessages() {
    if (!hasMoreMessages || !oldestMessageId) return;

    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = 'Loading...';
    }

    fetch(`/dm/${conversationId}/messages/more?before_id=${oldestMessageId}`, {
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages.length > 0) {
            // Get reference to first message for scroll position
            const firstMessage = messagesContainer.querySelector('.dm-message');
            const scrollOffset = firstMessage ? firstMessage.offsetTop : 0;

            // Prepend messages
            data.messages.forEach(message => {
                prependMessage(message);
            });

            // Update oldest message ID
            oldestMessageId = data.messages[0].id;

            // Update has more flag
            hasMoreMessages = data.has_more;

            // Maintain scroll position
            if (firstMessage) {
                const newOffset = firstMessage.offsetTop;
                messagesContainer.scrollTop = newOffset - scrollOffset;
            }
        }

        if (!hasMoreMessages && loadMoreBtn) {
            loadMoreBtn.remove();
        }
    })
    .catch(error => {
        console.error('Error loading more messages:', error);
    })
    .finally(() => {
        if (loadMoreBtn && hasMoreMessages) {
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = 'Load older messages';
        }
    });
}

// Prepend message (for loading older messages)
function prependMessage(message) {
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) return;

    const messageDiv = document.createElement('div');
    messageDiv.className = 'dm-message';
    messageDiv.dataset.messageId = message.id;
    messageDiv.dataset.senderId = message.sender_id;

    // Use formatTimestamp for consistency with server-rendered messages
    const time = formatTimestamp(message.created_at);

    const editedTag = message.is_edited ? '<span class="dm-message-edited">(edited)</span>' : '';
    const actionsHtml = message.sender_id === currentUserId ? `
        <div class="message-actions" style="opacity: 0; transition: opacity 0.2s;">
            <div class="dm-kebab-menu">
                <button class="dm-kebab-button" onclick="toggleMessageMenu(${message.id})" title="More options">&#8942;</button>
                <div class="dm-kebab-dropdown" id="message-menu-${message.id}">
                    <button class="dm-kebab-option" onclick="editMessage(${message.id})">Edit</button>
                    <button class="dm-kebab-option danger" onclick="deleteMessage(${message.id})">Delete</button>
                </div>
            </div>
        </div>
    ` : '';

    messageDiv.innerHTML = `
        <img src="${message.sender.avatar_url || '/images/default-avatar.png'}" alt="${escapeHtml(message.sender.display_name)}" class="dm-message-avatar">
        <div style="flex: 1;">
            <div class="dm-message-header">
                <span class="dm-message-author">${escapeHtml(message.sender.display_name)}</span>
                <span class="dm-message-timestamp" data-timestamp="${message.created_at}">${time}</span>
                ${editedTag}
            </div>
            <div class="dm-message-content">${escapeHtml(message.content)}</div>
        </div>
        ${actionsHtml}
    `;

    // Insert after load more button or at beginning
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.after(messageDiv);
    } else {
        messagesContainer.prepend(messageDiv);
    }
}

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Convert all server-rendered timestamps to local time
// This ensures consistency between server-rendered and JS-appended messages
function convertTimestampsToLocalTime() {
    document.querySelectorAll('.dm-message-timestamp[data-timestamp]').forEach(el => {
        const isoTimestamp = el.dataset.timestamp;
        if (isoTimestamp) {
            const localTime = formatTimestamp(isoTimestamp);
            el.textContent = localTime;
        }
    });
}

// Format ISO timestamp to local time string
function formatTimestamp(isoString) {
    const date = new Date(isoString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Convert timestamps on page load
convertTimestampsToLocalTime();

// Scroll to bottom on load
messagesContainer.scrollTop = messagesContainer.scrollHeight;

// Close edit modal when clicking outside
document.getElementById('edit-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        cancelEdit();
    }
});

// Close edit modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && currentEditingMessage) {
        cancelEdit();
    }
});

// Mark messages as read on page load
markAsRead();

// Responsive: Show mobile back button
if (window.innerWidth <= 768) {
    const mobileBackBtn = document.querySelector('.mobile-back-btn');
    if (mobileBackBtn) {
        mobileBackBtn.style.display = 'block';
    }
}
</script>
@endpush
