@extends('layouts.app')

@section('title', '#' . $channel->name . ' - ' . $server->name)

@push('head')
    <!-- Agora App ID for Voice Chat (Public - Safe to expose) -->
    <meta name="agora-app-id" content="{{ config('services.agora.app_id') }}">
@endpush

@push('styles')
@vite(['resources/css/voice-panel.css'])
<style>
    /* Hide x-cloak elements until Alpine.js loads */
    [x-cloak] { display: none !important; }

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

    .chat-input-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        padding: 4px 4px 4px 8px;
        transition: border-color 0.15s;
    }

    .chat-input-wrapper:focus-within {
        border-color: #667eea;
    }

    .chat-input {
        flex: 1;
        background: none;
        border: none;
        color: #efeff1;
        font-size: 16px;
        resize: none;
        font-family: inherit;
        max-height: 100px;
        min-height: 40px;
        padding: 8px 0;
        line-height: 1.4;
    }

    .chat-input:focus {
        outline: none;
    }

    .chat-input::placeholder {
        color: #71717a;
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

    /* Voice Chat Styles */
    .voice-channel-link {
        cursor: pointer;
        transition: background-color 0.15s;
    }

    .voice-channel-link:hover {
        background-color: #3f3f46;
        border-radius: 4px;
        padding: 4px 8px;
        margin-left: -8px;
        margin-right: -8px;
    }

    .voice-user-count {
        transition: all 0.2s ease;
    }

    .voice-channel-link.active {
        background-color: #667eea;
        color: #ffffff;
        border-radius: 4px;
        padding: 4px 8px;
        margin-left: -8px;
        margin-right: -8px;
    }

    /* Voice Controls Panel */
    #voice-controls-panel {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    #mute-toggle-btn:hover {
        background-color: #52525b !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    #mute-toggle-btn.muted {
        background-color: #ef4444 !important;
        color: white !important;
    }

    #disconnect-btn:hover {
        background-color: #dc2626 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Network Quality Indicators */
    .network-quality-excellent {
        background-color: #10b981 !important;
    }

    .network-quality-good {
        background-color: #f59e0b !important;
    }

    .network-quality-poor {
        background-color: #ef4444 !important;
    }

    /* Voice Speaking Animation */
    @keyframes pulse-ring {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        50% {
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.3);
        }
        100% {
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }
    }

    .voice-speaking-indicator {
        animation: pulse-ring 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .member-avatar.speaking {
        border: 2px solid #10b981;
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.5);
    }

    /* In Voice Badge Animation */
    .in-voice-badge {
        animation: fadeInScale 0.3s ease-out;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Connection Quality Pulse */
    #voice-connection-indicator.connecting {
        background-color: #f59e0b !important;
        animation: pulse 1s ease-in-out infinite;
    }

    #voice-connection-indicator.disconnected {
        background-color: #ef4444 !important;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Emoji Picker Styles */
    .emoji-container {
        position: relative;
    }

    .emoji-btn {
        background: none;
        border: none;
        color: #71717a;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .emoji-btn:hover {
        color: #efeff1;
        background-color: #3f3f46;
    }

    .emoji-picker {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 0;
        background-color: #27272a;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        padding: 12px;
        display: none;
        z-index: 100;
    }

    .emoji-picker.active {
        display: block;
    }

    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 4px;
    }

    .emoji-item {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.15s;
        background: none;
        border: none;
    }

    .emoji-item:hover {
        background-color: #3f3f46;
    }

    .send-btn {
        background-color: #667eea;
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .send-btn:hover {
        background-color: #5a6fd6;
    }

    .send-btn:disabled {
        background-color: #3f3f46;
        cursor: not-allowed;
    }

    .send-btn svg {
        width: 18px;
        height: 18px;
    }

    /* Member List Styles */
    .member-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .member-item:hover {
        background-color: rgba(79, 84, 92, 0.4);
    }
</style>
@endpush

@push('scripts')
    @vite(['resources/js/voice-chat.js'])
@endpush

@section('content')
<div style="display: flex; height: 100vh;">
    <!-- Server Sidebar -->
    <div style="width: 240px; background-color: #18181b; display: flex; flex-direction: column;">
        {{-- Phase 3: Server Dropdown Header --}}
        <div class="server-header" style="padding: 12px 16px; border-bottom: 1px solid #3f3f46;">
            <x-server-dropdown
                :server="$server"
                :isAdmin="auth()->user()->isServerAdmin($server->id)"
                :isOwner="$server->creator_id === auth()->id()"
            />
        </div>

        <!-- Channels List - Phase 1 UI Improvement -->
        <div class="sidebar-channels-container" style="flex: 1; overflow-y: auto; padding: var(--sidebar-padding-x, 8px);">
            {{-- Text Channels Category --}}
            <x-channel-category
                name="TEXT CHANNELS"
                :serverId="$server->id"
                :canAddChannel="auth()->user()->isServerAdmin($server->id)"
                :addChannelRoute="route('server.admin.settings', $server) . '#channels'"
                type="text"
            >
                @foreach($server->channels->where('type', 'text') as $ch)
                    <x-text-channel-item
                        :channel="$ch"
                        :server="$server"
                        :active="$channel->id === $ch->id"
                        :canEdit="auth()->user()->isServerAdmin($server->id)"
                        :editRoute="route('server.admin.settings', $server) . '#channels'"
                    />
                @endforeach
            </x-channel-category>

            {{-- Voice Channels Category --}}
            <x-channel-category
                name="VOICE CHANNELS"
                :serverId="$server->id"
                :canAddChannel="auth()->user()->isServerAdmin($server->id)"
                :addChannelRoute="route('server.admin.settings', $server) . '#channels'"
                type="voice"
            >
                @foreach($server->channels->where('type', 'voice') as $ch)
                    <x-voice-channel-item
                        :channel="$ch"
                        :server="$server"
                        :canEdit="auth()->user()->isServerAdmin($server->id)"
                        :editRoute="route('server.admin.settings', $server) . '#channels'"
                    />
                @endforeach
            </x-channel-category>
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
    <div
        style="flex: 1; display: flex; flex-direction: column; background-color: #0e0e10;"
        x-data="{
            memberListVisible: localStorage.getItem('memberListVisible') !== 'false'
        }"
        @toggle-member-list.window="memberListVisible = $event.detail.visible"
    >
        {{-- Phase 3: Enhanced Channel Header --}}
        <x-channel-header
            :channel="$channel"
            :server="$server"
            :memberListVisible="true"
        />

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
                <div class="chat-input-wrapper">
                    {{-- Emoji Picker --}}
                    <div class="emoji-container">
                        <button type="button" class="emoji-btn" onclick="toggleEmojiPicker()" title="Add emoji" aria-label="Open emoji picker">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        <div id="emoji-picker" class="emoji-picker">
                            <div class="emoji-grid">
                                @foreach(['😀','😂','😍','🤔','😎','👍','👎','❤️','🔥','✨','🎮','🎯','💯','🙌','😢','😡','🤣','😊','🥰','😋','🤩','😏','😴','🥳','😤','🤗','🤫','🤭','😱','😈','💀','👻','🤡','💩','🙈','🙉','🙊','💪','👏','🤝','✌️','🤞','🖐️','👋','🎉','🏆'] as $emoji)
                                    <button type="button" class="emoji-item" onclick="insertEmoji('{{ $emoji }}')">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <textarea
                        id="message-input"
                        class="chat-input"
                        placeholder="Message #{{ $channel->name }}"
                        rows="1"
                        maxlength="2000"
                        onkeydown="handleKeyDown(event)"
                        oninput="autoResizeTextarea(this)"
                    ></textarea>

                    <button type="submit" class="send-btn" id="send-btn" title="Send message">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Members Sidebar - Phase 2 Enhanced + Phase 3 Toggle -->
    <div
        class="member-list-container"
        x-data="{ visible: localStorage.getItem('memberListVisible') !== 'false' }"
        @toggle-member-list.window="visible = $event.detail.visible"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform translate-x-4"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-4"
        style="width: 240px; background-color: #18181b; padding: 8px; overflow-y: auto;"
    >
        <div class="member-list-header">
            Members — {{ $server->members->count() }}
        </div>

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
                        $membersByRole->put($roleKey, collect([
                            'role' => $highestRole,
                            'members' => collect()
                        ]));
                    }
                    $membersByRole->get($roleKey)['members']->push($member);
                } else {
                    // Members with no custom roles go to default "Member" role
                    $defaultRole = $server->roles()->where('name', 'Member')->first();
                    if ($defaultRole) {
                        $roleKey = 'Member';
                        if (!$membersByRole->has($roleKey)) {
                            $membersByRole->put($roleKey, collect([
                                'role' => $defaultRole,
                                'members' => collect()
                            ]));
                        }
                        $membersByRole->get($roleKey)['members']->push($member);
                    }
                }
            }

            // Get roles for proper ordering
            $allRoles = $server->roles()->orderBy('position', 'desc')->get();
        @endphp

        @foreach($allRoles as $role)
            @if($membersByRole->has($role->name) && $membersByRole->get($role->name)['members']->count() > 0)
                @php
                    $roleData = $membersByRole->get($role->name);
                    $roleMembers = $roleData['members'];
                @endphp

                {{-- Role Header Component --}}
                <x-role-header
                    :roleName="$role->name"
                    :count="$roleMembers->count()"
                    :serverId="$server->id"
                    :roleColor="$role->color ?? '#96989d'"
                >
                    @foreach($roleMembers as $member)
                        {{-- Member Item Component --}}
                        <x-member-item
                            :member="$member"
                            :server="$server"
                            :role="$role"
                            :isOwner="$server->creator_id === $member->id"
                        />
                    @endforeach
                </x-role-header>
            @endif
        @endforeach
    </div>
</div>

{{-- Phase 3: Search Modal --}}
<x-search-modal :server="$server" :channel="$channel" />

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

{{-- Phase 5: Voice Connected Panel --}}
<x-voice-panel :server="$server" :channel="$channel" />

{{-- Phase 5: Voice Settings Popup --}}
<x-voice-settings-popup />
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
let channelSubscribed = false;

function setupChatChannels() {
    if (channelSubscribed) {
        console.log('[Chat] Channels already subscribed, skipping...');
        return;
    }

    if (!window.Echo) {
        console.warn('[Chat] Echo not available yet, waiting for initialization...');
        return;
    }

    console.log('[Chat] Setting up Echo listener for channel:', `server.${serverId}.channel.${channelId}`);
    channelSubscribed = true;

    const chatChannel = window.Echo.private(`server.${serverId}.channel.${channelId}`);

    chatChannel.listen('.message.posted', (e) => {
        console.log('[Chat] Received message broadcast:', e);
        appendMessage(e.message);
    })
    .listen('.message.edited', (e) => {
        console.log('[Chat] Received message edit broadcast:', e);
        updateMessage(e.message);
    })
    .listen('.message.deleted', (e) => {
        console.log('[Chat] Received message delete broadcast:', e);
        removeMessage(e.message_id);
    })
    .error((error) => {
        console.error('[Chat] Echo channel error:', error);
    });

    // Initialize Echo listener for server-wide events (lobby notifications)
    console.log('[Chat] Setting up Echo listener for server-wide events:', `server.${serverId}`);

    const serverChannel = window.Echo.private(`server.${serverId}`);

    serverChannel.listen('.user.lobby.updated', (e) => {
        console.log('[Chat] Received lobby updated broadcast:', e);

        // Don't show notification for own lobby creation
        if (e.user_id !== currentUserId) {
            showLobbyNotification(e);
        }

        // Update member list UI regardless
        updateMemberLobbyStatus(e.user_id, e.lobby_link, e.display_name);
    })
    .listen('.user.lobby.cleared', (e) => {
        console.log('[Chat] Received lobby cleared broadcast:', e);

        // Don't show notification for own lobby clear
        if (e.user_id !== currentUserId) {
            showLobbyCleared(e);
        }

        // Update member list UI regardless
        updateMemberLobbyStatus(e.user_id, null, e.display_name);
    })
    .listen('.user.status.updated', (e) => {
        console.log('[Chat] Received user status update broadcast:', e);
        updateMemberStatus(e);
    })
    .listen('.voice.user.joined', (event) => {
        console.log('[Voice] User joined voice:', event);

        // Extract user data from nested structure
        const userId = event.user?.id || event.user_id;
        const userName = event.user?.display_name || event.user?.username || event.user_name || 'Unknown user';

        // Show "In Voice" badge for the user
        const badge = document.querySelector(`.in-voice-badge[data-user-id="${userId}"]`);
        if (badge) {
            badge.style.display = 'inline-block';
        }

        // Update voice channel user count badge
        const channelCount = document.querySelector(`.voice-user-count-badge[data-channel-id="${event.channel_id}"]`);
        if (channelCount) {
            const currentCount = parseInt(channelCount.textContent) || 0;
            channelCount.textContent = currentCount + 1;
            channelCount.style.display = 'inline-flex';
        }

        // Update Alpine.js voice-channel-wrapper component
        const channelWrapper = document.querySelector(`.voice-channel-wrapper[data-channel-id="${event.channel_id}"]`);
        if (channelWrapper && channelWrapper._x_dataStack) {
            const alpineData = channelWrapper._x_dataStack[0];
            if (alpineData) {
                const newUser = {
                    id: userId,
                    name: userName,
                    avatar: event.user?.avatar_url || '/images/default-avatar.png',
                    isSpeaking: false,
                    isMuted: false,
                    isDeafened: false,
                    isStreaming: false
                };
                alpineData.users.push(newUser);
                alpineData.userCount = alpineData.users.length;
                alpineData.expanded = true;
            }
        }

        // Show notification if not the current user
        if (userId !== currentUserId) {
            showToast(`${userName} joined ${event.channel_name}`, 'info');
        }
    })
    .listen('.voice.user.left', (event) => {
        console.log('[Voice] User left voice:', event);

        // Extract user data from nested structure
        const userId = event.user?.id || event.user_id;
        const userName = event.user?.display_name || event.user?.username || event.user_name || 'Unknown user';

        // Hide "In Voice" badge for the user
        const badge = document.querySelector(`.in-voice-badge[data-user-id="${userId}"]`);
        if (badge) {
            badge.style.display = 'none';
        }

        // Update voice channel user count badge
        const channelCount = document.querySelector(`.voice-user-count-badge[data-channel-id="${event.channel_id}"]`);
        if (channelCount) {
            const currentCount = parseInt(channelCount.textContent) || 0;
            const newCount = Math.max(0, currentCount - 1);
            channelCount.textContent = newCount;

            if (newCount === 0) {
                channelCount.style.display = 'none';
            }
        }

        // Update Alpine.js voice-channel-wrapper component
        const channelWrapper = document.querySelector(`.voice-channel-wrapper[data-channel-id="${event.channel_id}"]`);
        if (channelWrapper && channelWrapper._x_dataStack) {
            const alpineData = channelWrapper._x_dataStack[0];
            if (alpineData) {
                alpineData.users = alpineData.users.filter(u => u.id !== userId);
                alpineData.userCount = alpineData.users.length;
            }
        }

        // Show notification if not the current user
        if (userId !== currentUserId) {
            showToast(`${userName} left ${event.channel_name}`, 'info');
        }
    })
    .listen('.voice.user.speaking', (event) => {
        console.log('[Voice] User speaking status:', event);

        // Update Alpine.js voice-channel-wrapper component
        const channelWrapper = document.querySelector(`.voice-channel-wrapper[data-channel-id="${event.channel_id}"]`);
        if (channelWrapper && channelWrapper._x_dataStack) {
            const alpineData = channelWrapper._x_dataStack[0];
            if (alpineData) {
                const user = alpineData.users.find(u => u.id === event.user_id);
                if (user) {
                    user.isSpeaking = event.is_speaking;
                }
            }
        }

        // Also update DOM directly for immediate visual feedback
        const userItem = document.querySelector(`.voice-user-item[data-user-id="${event.user_id}"][data-channel-id="${event.channel_id}"]`);
        if (userItem) {
            const avatarWrapper = userItem.querySelector('.voice-user-avatar-wrapper');
            if (event.is_speaking) {
                userItem.classList.add('speaking');
                if (avatarWrapper) avatarWrapper.classList.add('speaking');
            } else {
                userItem.classList.remove('speaking');
                if (avatarWrapper) avatarWrapper.classList.remove('speaking');
            }
        }
    })
    .listen('.voice.user.muted', (event) => {
        console.log('[Voice] User mute status changed:', event);

        const userId = event.user?.id || event.user_id;

        const channelWrapper = document.querySelector(`.voice-channel-wrapper[data-channel-id="${event.channel_id}"]`);
        if (channelWrapper && channelWrapper._x_dataStack) {
            const alpineData = channelWrapper._x_dataStack[0];
            if (alpineData) {
                const user = alpineData.users.find(u => u.id === userId);
                if (user) {
                    user.isMuted = event.is_muted;
                }
            }
        }
    })
    .listen('.voice.user.deafened', (event) => {
        console.log('[Voice] User deafen status changed:', event);

        const userId = event.user?.id || event.user_id;

        const channelWrapper = document.querySelector(`.voice-channel-wrapper[data-channel-id="${event.channel_id}"]`);
        if (channelWrapper && channelWrapper._x_dataStack) {
            const alpineData = channelWrapper._x_dataStack[0];
            if (alpineData) {
                const user = alpineData.users.find(u => u.id === userId);
                if (user) {
                    user.isDeafened = event.is_deafened;
                }
            }
        }
    })
    .error((error) => {
        console.error('[Chat] Echo server channel error:', error);
    });

    console.log('[Chat] Channel subscriptions initiated');
}

/**
 * Update member status in the member list (Phase 2)
 * Called when receiving real-time status update broadcasts
 */
function updateMemberStatus(data) {
    const { user_id, status, status_color, activity, has_custom_status, full_custom_status } = data;

    // Find all member items for this user
    const memberItems = document.querySelectorAll(`.member-item-enhanced[data-user-id="${user_id}"]`);

    memberItems.forEach(memberItem => {
        // Update status data attribute
        memberItem.dataset.status = status;

        // Update status indicator
        const statusIndicator = memberItem.querySelector('.member-status');
        if (statusIndicator) {
            statusIndicator.dataset.status = status;
        }

        // Update activity text
        const activityElement = memberItem.querySelector('.member-activity');
        if (activity) {
            if (activityElement) {
                activityElement.textContent = activity;
                activityElement.style.display = '';
            } else {
                // Create activity element if it doesn't exist
                const infoElement = memberItem.querySelector('.member-info');
                if (infoElement) {
                    const newActivity = document.createElement('div');
                    newActivity.className = 'member-activity';
                    newActivity.textContent = activity;
                    infoElement.appendChild(newActivity);
                }
            }
        } else if (activityElement) {
            activityElement.style.display = 'none';
        }
    });

    console.log(`[Status] Updated status for user ${user_id} to ${status}`);
}

// Try to set up channels immediately if Echo is ready
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    const pusherConnection = window.Echo.connector.pusher.connection;

    // Check if already connected
    if (pusherConnection.state === 'connected') {
        console.log('[Chat] Echo already connected, setting up channels...');
        setupChatChannels();
    } else {
        // Wait for connection
        console.log('[Chat] Waiting for Echo connection (current state:', pusherConnection.state, ')');
        pusherConnection.bind('connected', () => {
            console.log('[Chat] WebSocket connected, setting up channels...');
            setupChatChannels();
        });
    }

    // Log connection status changes
    pusherConnection.bind('disconnected', () => {
        console.warn('[Chat] WebSocket disconnected');
    });

    pusherConnection.bind('unavailable', () => {
        console.error('[Chat] WebSocket unavailable - real-time messaging disabled');
    });

    pusherConnection.bind('failed', () => {
        console.error('[Chat] WebSocket connection failed - real-time messaging disabled');
    });

    pusherConnection.bind('error', (error) => {
        console.error('[Chat] WebSocket connection error:', error);
    });
} else {
    // Echo not initialized yet, listen for the custom event from bootstrap.js
    console.log('[Chat] Echo not initialized yet, waiting for echo:connected event...');

    window.addEventListener('echo:connected', () => {
        console.log('[Chat] Received echo:connected event, setting up channels...');
        setupChatChannels();
    });

    window.addEventListener('echo:failed', () => {
        console.error('[Chat] Echo initialization failed - real-time messaging will not work');
    });
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

// ==========================================
// Emoji Picker Functions
// ==========================================

/**
 * Toggle the emoji picker visibility
 */
function toggleEmojiPicker() {
    const picker = document.getElementById('emoji-picker');
    picker.classList.toggle('active');
}

/**
 * Insert emoji at cursor position in textarea
 */
function insertEmoji(emoji) {
    const input = document.getElementById('message-input');
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;

    input.value = text.substring(0, start) + emoji + text.substring(end);
    input.selectionStart = input.selectionEnd = start + emoji.length;
    input.focus();

    // Close emoji picker after selection
    toggleEmojiPicker();

    // Trigger resize
    autoResizeTextarea(input);
}

/**
 * Auto-resize textarea based on content
 */
function autoResizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
}

// Close emoji picker when clicking outside
document.addEventListener('click', function(event) {
    const emojiContainer = document.querySelector('.emoji-container');
    const emojiPicker = document.getElementById('emoji-picker');

    if (emojiContainer && !emojiContainer.contains(event.target) && emojiPicker) {
        emojiPicker.classList.remove('active');
    }
});

// ==========================================
// Voice Chat Functions
// ==========================================

let voiceChat = null;
let currentVoiceChannel = null;
let isMuted = false;

/**
 * Join a voice channel
 */
async function joinVoiceChannel(serverId, channelId, channelName) {
    try {
        // Check if already connected to a voice channel
        if (voiceChat && voiceChat.isConnected) {
            if (currentVoiceChannel === channelId) {
                showToast('You are already connected to this voice channel', 'info');
                return;
            }
            // Disconnect from current channel first
            await disconnectVoice();
        }

        showToast(`Connecting to ${channelName}...`, 'info');

        // Initialize voice chat if not already
        if (!voiceChat) {
            if (typeof window.VoiceChat !== 'undefined') {
                voiceChat = new window.VoiceChat();
            } else {
                console.log('[Voice] VoiceChat module not yet loaded, waiting...');

                // Dispatch custom event for voice chat handler
                window.dispatchEvent(new CustomEvent('voice:join', {
                    detail: { serverId, channelId, channelName }
                }));

                // Show UI feedback in the meantime (Phase 5: dispatch event for new voice panel)
                currentVoiceChannel = channelId;
                window.dispatchEvent(new CustomEvent('voice-connected', {
                    detail: { channelId, channelName }
                }));

                // Mark voice channel as active
                document.querySelectorAll('.voice-channel-link').forEach(link => {
                    link.classList.remove('active');
                });
                const activeLink = document.querySelector(`.voice-channel-link[data-channel-id="${channelId}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }

                showToast(`Connected to ${channelName}`, 'success');
                return;
            }
        }

        // Join the voice channel (voice-chat.js dispatches voice-connected event)
        await voiceChat.joinChannel(channelId);

        // Update local state
        currentVoiceChannel = channelId;

        // Mark voice channel as active
        document.querySelectorAll('.voice-channel-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.querySelector(`.voice-channel-link[data-channel-id="${channelId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Show user's own "In Voice" badge
        const myBadge = document.querySelector(`.in-voice-badge[data-user-id="{{ auth()->id() }}"]`);
        if (myBadge) {
            myBadge.style.display = 'inline-block';
        }

        showToast(`Connected to ${channelName}`, 'success');
    } catch (error) {
        console.error('[Voice] Failed to join channel:', error);
        showToast('Failed to connect to voice channel. Please try again.', 'error');
    }
}

/**
 * Disconnect from voice channel
 */
async function disconnectVoice() {
    try {
        if (voiceChat && voiceChat.isConnected) {
            await voiceChat.leaveChannel();
        }

        // Phase 5: Dispatch event for new voice panel
        window.dispatchEvent(new CustomEvent('voice-disconnected'));

        // Update UI
        document.querySelectorAll('.voice-channel-link').forEach(link => {
            link.classList.remove('active');
        });

        // Hide user's own "In Voice" badge
        const myBadge = document.querySelector(`.in-voice-badge[data-user-id="{{ auth()->id() }}"]`);
        if (myBadge) {
            myBadge.style.display = 'none';
        }

        currentVoiceChannel = null;
        isMuted = false;

        showToast('Disconnected from voice channel', 'info');
    } catch (error) {
        console.error('[Voice] Failed to disconnect:', error);
    }
}

/**
 * Toggle mute state - Phase 5: Updated to work with new voice panel component
 */
async function toggleMute() {
    if (voiceChat) {
        isMuted = await voiceChat.toggleMute();
    } else {
        isMuted = !isMuted;
    }

    // Phase 5: Dispatch event for new voice panel component
    window.dispatchEvent(new CustomEvent('voice-mute-changed', {
        detail: { isMuted: isMuted }
    }));

    if (isMuted) {
        showToast('Microphone muted', 'info');
    } else {
        showToast('Microphone unmuted', 'info');
    }
}

/**
 * Update network quality indicator - Phase 5: Now handled by voice panel component
 */
function updateNetworkQuality(quality) {
    // Legacy function - the new voice panel handles this via voice-quality-update events
    // The voice-chat.js dispatches these events automatically
    console.log('[Voice] Network quality:', quality);
}

/**
 * Leave current voice channel (alias for disconnectVoice)
 */
function leaveVoiceChannel() {
    disconnectVoice();
}
</script>
@endpush

{{-- Include gaming status real-time synchronization (Phase 2) --}}
@include('partials.gaming-status-script')