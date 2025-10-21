@extends('layouts.app')

@section('title', '#' . $channel->name . ' - ' . $server->name)

@push('styles')
<style>
    .message {
        display: flex;
        gap: 16px;
        padding: 8px 16px;
        transition: background-color 0.1s;
    }
    
    .message:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .message:hover .message-actions {
        opacity: 1 !important;
    }
    
    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .message-header {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 4px;
    }
    
    .message-author {
        font-weight: 600;
        color: #efeff1;
    }
    
    .message-timestamp {
        font-size: 12px;
        color: #71717a;
    }
    
    .message-content {
        color: #b3b3b5;
        line-height: 1.5;
        word-wrap: break-word;
    }
    
    .chat-input-container {
        padding: 16px;
        background-color: #0e0e10;
    }
    
    .chat-input {
        width: 100%;
        padding: 12px 16px;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        color: #efeff1;
        font-size: 16px;
        resize: none;
    }
    
    .chat-input:focus {
        outline: none;
        border-color: #667eea;
    }

    .kebab-menu {
        position: relative;
        display: inline-block;
    }

    .kebab-button {
        background: none;
        border: none;
        color: #71717a;
        font-size: 16px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .kebab-button:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }

    .kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        min-width: 120px;
        z-index: 1000;
        display: none;
    }

    .kebab-dropdown.active {
        display: block;
    }

    .kebab-option {
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

    .kebab-option:hover {
        background-color: #3f3f46;
    }

    .kebab-option:first-child {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .kebab-option:last-child {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .kebab-option.danger {
        color: #f87171;
    }

    .kebab-option.danger:hover {
        background-color: #dc2626;
        color: #ffffff;
    }

    .back-button:hover {
        background-color: #3f3f46 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
<div style="display: flex; height: 100vh;">
    <!-- Server Sidebar -->
    <div style="width: 240px; background-color: #18181b; display: flex; flex-direction: column;">
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46;">
            <h3 style="font-size: 16px; margin: 0;">{{ $server->name }}</h3>
            @if(auth()->user()->isServerAdmin($server->id))
                <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div>
                        <span style="font-size: 12px; color: #71717a;">Invite Code: </span>
                        <code style="font-size: 12px; background-color: #0e0e10; padding: 2px 6px; border-radius: 4px;">{{ $server->invite_code }}</code>
                    </div>
                    <a href="{{ route('server.admin.settings', $server) }}" class="btn btn-secondary btn-sm">
                        Server Settings
                    </a>
                </div>
            @endif
        </div>

        <!-- Channels List -->
        <div style="flex: 1; overflow-y: auto; padding: 8px;">
            <div style="margin-bottom: 16px;">
                <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 8px;">Text Channels</p>
                @foreach($server->channels->where('type', 'text') as $ch)
                    <a href="{{ route('channel.show', [$server, $ch]) }}" 
                       class="sidebar-link {{ $channel->id === $ch->id ? 'active' : '' }}"
                       style="display: block; margin-bottom: 4px;">
                        <span style="color: #71717a; margin-right: 8px;">#</span>
                        {{ $ch->name }}
                    </a>
                @endforeach
            </div>

            <div>
                <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 8px;">Voice Channels</p>
                @foreach($server->channels->where('type', 'voice') as $ch)
                    <div class="sidebar-link" style="opacity: 0.5; cursor: not-allowed;">
                        <span style="color: #71717a; margin-right: 8px;">🔊</span>
                        {{ $ch->name }}
                    </div>
                @endforeach
            </div>
        </div>

        <!-- User Section -->
        <div style="padding: 16px; border-top: 1px solid #3f3f46; background-color: #0e0e10;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" 
                     style="width: 32px; height: 32px; border-radius: 50%;">
                <div style="flex: 1;">
                    <div style="font-size: 14px; font-weight: 600;">{{ auth()->user()->display_name }}</div>
                    <div style="font-size: 12px; color: #71717a;">{{ auth()->user()->username }}</div>
                </div>
                <div class="kebab-menu">
                    <button class="kebab-button" onclick="toggleKebabMenu('user-settings')" style="padding: 4px;">⚙️</button>
                    <div class="kebab-dropdown" id="kebab-user-settings">
                        @if($server->creator_id === auth()->id())
                            <form method="POST" action="{{ route('server.destroy', $server) }}" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kebab-option danger" onclick="return confirm('Are you sure you want to delete this server? This action cannot be undone and will delete all channels, messages, and remove all members.')">
                                    Delete Server
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('server.leave', $server) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="kebab-option danger" onclick="return confirm('Are you sure you want to leave this server?')">
                                    Leave Server
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div style="flex: 1; display: flex; flex-direction: column; background-color: #0e0e10;">
        <!-- Channel Header -->
        <div style="padding: 16px; border-bottom: 1px solid #3f3f46; background-color: #18181b; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="{{ route('dashboard') }}" class="back-button" style="display: flex; align-items: center; gap: 8px; color: #71717a; text-decoration: none; padding: 8px 12px; border-radius: 6px; transition: background-color 0.2s, color 0.2s;">
                    <span style="font-size: 16px;">←</span>
                    <span style="font-size: 14px; font-weight: 500;">Dashboard</span>
                </a>
                <span style="color: #3f3f46; font-size: 16px;">|</span>
                <a href="{{ route('server.show', $server) }}" style="color: #71717a; text-decoration: none; font-size: 16px; transition: color 0.2s;" onmouseover="this.style.color='#efeff1'" onmouseout="this.style.color='#71717a'">
                    {{ $server->name }}
                </a>
                <span style="color: #3f3f46; font-size: 16px;">></span>
                <h3 style="margin: 0; font-size: 18px; color: #efeff1;">
                    <span style="color: #71717a; margin-right: 8px;">#</span>
                    {{ $channel->name }}
                </h3>
            </div>
        </div>

        <!-- Messages -->
        <div id="messages-container" style="flex: 1; overflow-y: auto; padding: 16px;">
            @forelse($messages as $message)
                <div class="message" data-message-id="{{ $message->id }}" data-user-id="{{ $message->user->id }}">
                    <img src="{{ $message->user->profile->avatar_url }}" alt="{{ $message->user->display_name }}" class="message-avatar">
                    <div style="flex: 1;">
                        <div class="message-header">
                            <span class="message-author">{{ $message->user->display_name }}</span>
                            <span class="message-timestamp">{{ $message->created_at->format('h:i A') }}</span>
                            @if($message->is_edited)
                                <span class="message-edited" style="font-size: 12px; color: #71717a; font-style: italic;">(Edited)</span>
                            @endif
                        </div>
                        <div class="message-content">{{ $message->content }}</div>
                    </div>
                    @if($message->user->id === auth()->id())
                        <div class="message-actions" style="opacity: 0; transition: opacity 0.2s;">
                            <div class="kebab-menu">
                                <button class="kebab-button" onclick="toggleMessageMenu({{ $message->id }})" style="padding: 4px 8px; font-size: 14px;">⋮</button>
                                <div class="kebab-dropdown" id="message-menu-{{ $message->id }}">
                                    <button class="kebab-option" onclick="editMessage({{ $message->id }})">Edit</button>
                                    <button class="kebab-option danger" onclick="deleteMessage({{ $message->id }})">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; color: #71717a; padding: 48px;">
                    <p>This is the beginning of #{{ $channel->name }}</p>
                </div>
            @endforelse
        </div>

        <!-- Input -->
        <div class="chat-input-container">
            <form id="message-form" onsubmit="sendMessage(event)">
                <textarea 
                    id="message-input"
                    class="chat-input" 
                    placeholder="Message #{{ $channel->name }}"
                    rows="1"
                    maxlength="2000"
                    onkeydown="handleKeyDown(event)"></textarea>
            </form>
        </div>
    </div>

    <!-- Members Sidebar -->
    <div style="width: 240px; background-color: #18181b; padding: 16px; overflow-y: auto;">
        <p style="font-size: 12px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-bottom: 16px;">
            Members — {{ $server->members->count() }}
        </p>
        
        @php
            // Group members by their highest role
            $membersByRole = collect();
            
            foreach($server->members as $member) {
                $highestRole = $member->roles()
                    ->wherePivot('server_id', $server->id)
                    ->orderBy('position', 'desc')
                    ->first();
                
                if ($highestRole) {
                    $roleKey = $highestRole->name;
                    if (!$membersByRole->has($roleKey)) {
                        $membersByRole->put($roleKey, collect());
                    }
                    $membersByRole->get($roleKey)->push($member);
                } else {
                    // Members with no custom roles go to default "Member" role
                    $defaultRole = $server->roles()->where('name', 'Member')->first();
                    if ($defaultRole) {
                        $roleKey = 'Member';
                        if (!$membersByRole->has($roleKey)) {
                            $membersByRole->put($roleKey, collect());
                        }
                        $membersByRole->get($roleKey)->push($member);
                    }
                }
            }
            
            // Get roles for proper ordering
            $allRoles = $server->roles()->orderBy('position', 'desc')->get();
        @endphp

        @foreach($allRoles as $role)
            @if($membersByRole->has($role->name) && $membersByRole->get($role->name)->count() > 0)
                @php $roleMembers = $membersByRole->get($role->name); @endphp
                <p style="font-size: 12px; color: #71717a; margin-bottom: 8px; {{ $loop->index > 0 ? 'margin-top: 16px;' : '' }}">
                    {{ strtoupper($role->name) }} — {{ $roleMembers->count() }}
                </p>
                @foreach($roleMembers as $member)
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <img src="{{ $member->profile->avatar_url }}" alt="{{ $member->display_name }}" 
                             style="width: 32px; height: 32px; border-radius: 50%;">
                        <div style="flex: 1;" data-member-id="{{ $member->id }}">
                            <div style="font-size: 14px; color: {{ $role->color }};">
                                {{ $member->display_name }}
                                <div class="gaming-status-indicator w-2 h-2 {{ $member->profile->current_game ? 'bg-green-500' : 'bg-gray-500' }} rounded-full" 
                                     title="{{ $member->profile->current_game ? 'Playing ' . $member->profile->current_game['name'] : 'Not playing' }}"></div>
                            </div>
                            
                            {{-- Gaming Status (Phase 2: Real-time updates via data-user-status) --}}
                            <div data-user-status="{{ $member->id }}" class="{{ $member->profile->current_game ? '' : 'hidden' }}">
                                @if($member->profile->current_game)
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <div class="text-sm">
                                            <div class="text-green-400">{{ $member->profile->current_game['name'] }}</div>
                                            @if(isset($member->profile->current_game['server_name']) || isset($member->profile->current_game['map']))
                                                <div class="text-gray-400 text-xs">
                                                    @if($member->profile->current_game['server_name'])
                                                        {{ $member->profile->current_game['server_name'] }}
                                                    @endif
                                                    @if($member->profile->current_game['map'])
                                                        {{ $member->profile->current_game['server_name'] ? ' • ' : '' }}{{ $member->profile->current_game['map'] }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Fallback status when not gaming --}}
                            @if(!$member->profile->current_game)
                                <div style="font-size: 12px; color: #71717a;">
                                    <span class="status-indicator {{ $member->profile->status === 'online' ? 'status-online' : 'status-offline' }}"></span>
                                    {{ ucfirst($member->profile->status) }}
                                </div>
                            @endif
                            
                            {{-- Join lobby button (Phase 4) --}}
                            @if($member->id !== auth()->id())
                                @php
                                    // Priority system:
                                    // 1. User-provided lobby link (highest priority)
                                    // 2. Server IP from Steam API (community servers)
                                    // 3. Not joinable (matchmaking/offline)

                                    $hasLobbyLink = $member->profile && $member->profile->hasActiveLobby();
                                    $hasServerIP = $member->profile->current_game && isset($member->profile->current_game['connect']) && !empty($member->profile->current_game['connect']);
                                    $isJoinable = $hasLobbyLink || $hasServerIP;

                                    $joinUrl = null;
                                    $buttonText = 'Not Joinable';
                                    $buttonClass = 'btn-secondary';
                                    $isDisabled = true;

                                    if ($hasLobbyLink) {
                                        $joinUrl = $member->profile->steam_lobby_link;
                                        $buttonText = '🚀 Join Lobby';
                                        $buttonClass = 'btn-success';
                                        $isDisabled = false;
                                    } elseif ($hasServerIP) {
                                        $joinUrl = 'steam://connect/' . $member->profile->current_game['connect'];
                                        $buttonText = '🎮 Join Server';
                                        $buttonClass = 'btn-primary';
                                        $isDisabled = false;
                                    }
                                @endphp

                                @if($isJoinable)
                                    <a href="{{ $joinUrl }}" class="btn {{ $buttonClass }} btn-sm" style="margin-top: 4px; font-size: 11px; padding: 4px 8px; text-decoration: none; display: inline-block;">
                                        {{ $buttonText }}
                                    </a>
                                @elseif($member->profile->current_game)
                                    <button class="btn {{ $buttonClass }} btn-sm" style="margin-top: 4px; font-size: 11px; padding: 4px 8px; opacity: 0.5;" disabled title="Player is in matchmaking or offline">
                                        ⚠️ {{ $buttonText }}
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        @endforeach
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
                required></textarea>
            <div style="display: flex; gap: 12px; margin-top: 16px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/app.js'])
<script>
const messagesContainer = document.getElementById('messages-container');
const messageInput = document.getElementById('message-input');
const currentUserId = {{ auth()->id() }};
const serverId = {{ $server->id }};
const channelId = {{ $channel->id }};

// Initialize Echo listener for chat messages
if (window.Echo) {
    console.log('Setting up Echo listener for channel:', `server.${serverId}.channel.${channelId}`);

    const chatChannel = window.Echo.private(`server.${serverId}.channel.${channelId}`);

    chatChannel.listen('.message.posted', (e) => {
        console.log('Received message broadcast:', e);
        appendMessage(e.message);
    })
    .listen('.message.edited', (e) => {
        console.log('Received message edit broadcast:', e);
        updateMessage(e.message);
    })
    .listen('.message.deleted', (e) => {
        console.log('Received message delete broadcast:', e);
        removeMessage(e.message_id);
    })
    .error((error) => {
        console.error('Echo channel error:', error);
    });

    // Initialize Echo listener for server-wide events (lobby notifications)
    console.log('Setting up Echo listener for server-wide events:', `server.${serverId}`);

    const serverChannel = window.Echo.private(`server.${serverId}`);

    serverChannel.listen('.user.lobby.updated', (e) => {
        console.log('Received lobby updated broadcast:', e);

        // Don't show notification for own lobby creation
        if (e.user_id !== currentUserId) {
            showLobbyNotification(e);
        }

        // Update member list UI regardless
        updateMemberLobbyStatus(e.user_id, e.lobby_link, e.display_name);
    })
    .listen('.user.lobby.cleared', (e) => {
        console.log('Received lobby cleared broadcast:', e);

        // Don't show notification for own lobby clear
        if (e.user_id !== currentUserId) {
            showLobbyCleared(e);
        }

        // Update member list UI regardless
        updateMemberLobbyStatus(e.user_id, null, e.display_name);
    })
    .error((error) => {
        console.error('Echo server channel error:', error);
    });

    // Add connection status logging
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('WebSocket connected successfully');
    });

    window.Echo.connector.pusher.connection.bind('disconnected', () => {
        console.log('WebSocket disconnected');
    });

    window.Echo.connector.pusher.connection.bind('error', (error) => {
        console.error('WebSocket connection error:', error);
    });

} else {
    console.error('Echo not initialized!');
}

function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage(event);
    }
}

function sendMessage(event) {
    event.preventDefault();
    
    const content = messageInput.value.trim();
    if (!content) return;
    
    // Disable input while sending
    messageInput.disabled = true;
    
    fetch(`/servers/${serverId}/channels/${channelId}/messages`, {
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
            // Add message locally for immediate feedback to sender
            appendMessage(data.message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
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

function appendMessage(message) {
    // Check if message already exists to prevent duplicates
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) {
        console.log('Message already exists, skipping:', message.id);
        return;
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    messageDiv.dataset.messageId = message.id;
    messageDiv.dataset.userId = message.user.id;
    
    const time = new Date(message.created_at).toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    
    const editedTag = message.is_edited ? `<span class="message-edited" style="font-size: 12px; color: #71717a; font-style: italic;">(Edited)</span>` : '';
    const actionsHtml = message.user.id === currentUserId ? `
        <div class="message-actions" style="opacity: 0; transition: opacity 0.2s;">
            <div class="kebab-menu">
                <button class="kebab-button" onclick="toggleMessageMenu(${message.id})" style="padding: 4px 8px; font-size: 14px;">⋮</button>
                <div class="kebab-dropdown" id="message-menu-${message.id}">
                    <button class="kebab-option" onclick="editMessage(${message.id})">Edit</button>
                    <button class="kebab-option danger" onclick="deleteMessage(${message.id})">Delete</button>
                </div>
            </div>
        </div>
    ` : '';
    
    messageDiv.innerHTML = `
        <img src="${message.user.avatar_url}" alt="${message.user.display_name}" class="message-avatar">
        <div style="flex: 1;">
            <div class="message-header">
                <span class="message-author">${message.user.display_name}</span>
                <span class="message-timestamp">${time}</span>
                ${editedTag}
            </div>
            <div class="message-content">${escapeHtml(message.content)}</div>
        </div>
        ${actionsHtml}
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Scroll to bottom on load
messagesContainer.scrollTop = messagesContainer.scrollHeight;

function toggleKebabMenu(menuId) {
    // Close all other kebab menus
    document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'kebab-' + menuId) {
            dropdown.classList.remove('active');
        }
    });
    
    // Toggle the clicked menu
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.toggle('active');
}

function closeKebabMenu(menuId) {
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.remove('active');
}

// Close kebab menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.kebab-menu')) {
        document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});

// Message editing and deletion variables
let currentEditingMessage = null;

// Toggle message menu
function toggleMessageMenu(messageId) {
    // Close all other message menus
    document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'message-menu-' + messageId) {
            dropdown.classList.remove('active');
        }
    });
    
    // Toggle the clicked menu
    const menu = document.getElementById('message-menu-' + messageId);
    menu.classList.toggle('active');
}

// Edit message
function editMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) return;
    
    const messageContent = messageElement.querySelector('.message-content').textContent;
    const editModal = document.getElementById('edit-modal');
    const editContent = document.getElementById('edit-content');
    
    currentEditingMessage = messageId;
    editContent.value = messageContent;
    editModal.style.display = 'flex';
    editContent.focus();
    
    // Close the menu
    document.getElementById('message-menu-' + messageId).classList.remove('active');
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
    
    // Disable form while saving
    const editForm = document.getElementById('edit-form');
    const submitBtn = editForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    const editUrl = `/servers/${serverId}/channels/${channelId}/messages/${currentEditingMessage}`;
    console.log('Edit URL:', editUrl);
    console.log('Edit content:', newContent);
    
    fetch(editUrl, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content: newContent })
    })
    .then(response => {
        console.log('Edit response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Edit response data:', data);
        if (data.success) {
            updateMessage(data.message);
            cancelEdit();
        } else {
            alert(data.error || 'Failed to edit message');
        }
    })
    .catch(error => {
        console.error('Error editing message:', error);
        alert('Failed to edit message. Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save';
    });
}

// Delete message
function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) return;
    
    fetch(`/servers/${serverId}/channels/${channelId}/messages/${messageId}`, {
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

// Update message (for real-time edits)
function updateMessage(message) {
    const messageElement = document.querySelector(`[data-message-id="${message.id}"]`);
    if (!messageElement) return;
    
    const messageContent = messageElement.querySelector('.message-content');
    const messageHeader = messageElement.querySelector('.message-header');
    
    messageContent.textContent = message.content;
    
    // Update or add edited tag
    let editedTag = messageHeader.querySelector('.message-edited');
    if (message.is_edited) {
        if (!editedTag) {
            editedTag = document.createElement('span');
            editedTag.className = 'message-edited';
            editedTag.style.cssText = 'font-size: 12px; color: #71717a; font-style: italic;';
            editedTag.textContent = '(Edited)';
            messageHeader.appendChild(editedTag);
        }
    }
}

// Remove message (for real-time deletion)
function removeMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.remove();
    }
}

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

// ==========================================
// Lobby Notification Functions
// ==========================================

/**
 * Show a lobby notification when a user creates a lobby
 * Displays both a toast message and an interactive popup
 */
function showLobbyNotification(data) {
    const { user_id, display_name, lobby_link, team, message } = data;

    // Create toast notification
    showToast(`${display_name} created a CS2 lobby!`, 'success');

    // Create interactive popup notification
    const notification = createLobbyPopup(user_id, display_name, lobby_link, team);

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        if (notification && notification.parentElement) {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 10000);
}

/**
 * Show a notification when a user clears their lobby
 */
function showLobbyCleared(data) {
    const { display_name } = data;
    showToast(`${display_name} left their lobby`, 'info');
}

/**
 * Create an interactive lobby join popup notification
 */
function createLobbyPopup(userId, displayName, lobbyLink, team) {
    // Check if notification already exists
    const existingNotification = document.getElementById(`lobby-notification-${userId}`);
    if (existingNotification) {
        existingNotification.remove();
    }

    const notification = document.createElement('div');
    notification.id = `lobby-notification-${userId}`;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        z-index: 9999;
        min-width: 320px;
        max-width: 400px;
        animation: slideInRight 0.3s ease-out;
        cursor: pointer;
        transition: all 0.3s ease;
    `;

    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
            <div style="font-weight: 700; font-size: 16px;">CS2 Lobby Available!</div>
            <button onclick="event.stopPropagation(); this.closest('[id^=lobby-notification-]').remove();" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 0; line-height: 1; opacity: 0.7;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">&times;</button>
        </div>
        <div style="font-size: 14px; opacity: 0.95; margin-bottom: 12px;">
            <strong>${escapeHtml(displayName)}</strong> has created a lobby
            ${team ? `<br><span style="opacity: 0.8;">Team: ${escapeHtml(team.name)}</span>` : ''}
        </div>
        <div style="font-size: 13px; background: rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 8px; margin-bottom: 12px;">
            Click to join or dismiss
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="joinLobby('${escapeHtml(lobbyLink)}', '${escapeHtml(displayName)}'); this.closest('[id^=lobby-notification-]').remove();" style="flex: 1; background: white; color: #667eea; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                Join Lobby
            </button>
            <button onclick="this.closest('[id^=lobby-notification-]').remove();" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                Dismiss
            </button>
        </div>
    `;

    document.body.appendChild(notification);
    return notification;
}

/**
 * Join a CS2 lobby with confirmation
 */
function joinLobby(lobbyLink, displayName) {
    if (confirm(`Join ${displayName}'s CS2 lobby?\n\nThis will open Steam and connect you to the game.`)) {
        window.location.href = lobbyLink;
        showToast('Opening Steam...', 'success');
    }
}

/**
 * Update member list UI when a user's lobby status changes
 */
function updateMemberLobbyStatus(userId, lobbyLink, displayName) {
    // Find member in the member list sidebar
    const memberElement = document.querySelector(`[data-member-id="${userId}"]`);

    if (!memberElement) {
        console.log('Member not found in list:', userId);
        return;
    }

    // Remove existing lobby button if present
    const existingButton = memberElement.querySelector('.lobby-join-button');
    if (existingButton) {
        existingButton.remove();
    }

    // If lobby link exists, add join button
    if (lobbyLink) {
        const joinButton = document.createElement('a');
        joinButton.href = lobbyLink;
        joinButton.className = 'btn btn-success btn-sm lobby-join-button';
        joinButton.style.cssText = 'margin-top: 4px; font-size: 11px; padding: 4px 8px; text-decoration: none; display: inline-block;';
        joinButton.textContent = '🚀 Join Lobby';
        joinButton.onclick = function(e) {
            e.preventDefault();
            joinLobby(lobbyLink, displayName);
        };

        memberElement.appendChild(joinButton);
        console.log(`Added lobby button for ${displayName}`);
    } else {
        console.log(`Removed lobby button for ${displayName}`);
    }
}

/**
 * Simple toast notification system
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideInUp 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;

    // Set background color based on type
    const colors = {
        success: '#059669',
        error: '#dc2626',
        info: '#3b82f6',
        warning: '#f59e0b'
    };

    toast.style.backgroundColor = colors[type] || colors.info;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
@endpush

{{-- Include gaming status real-time synchronization (Phase 2) --}}
@include('partials.gaming-status-script')