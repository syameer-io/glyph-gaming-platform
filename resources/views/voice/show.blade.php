@extends('layouts.app')

@section('title', $channel->name . ' - ' . $server->name . ' - Glyph')

@push('head')
    <!-- Agora App ID for Voice Chat (Public - Safe to expose) -->
    <meta name="agora-app-id" content="{{ config('services.agora.app_id') }}">
@endpush

@push('styles')
@vite(['resources/css/voice-panel.css', 'resources/css/voice-view.css'])
@endpush

@push('scripts')
    @vite(['resources/js/voice-chat.js', 'resources/js/voice-view.js'])
@endpush

@section('content')
<div class="voice-channel-page" x-data="voiceChannelView({
    serverId: {{ $server->id }},
    channelId: {{ $channel->id }},
    channelName: '{{ $channel->name }}',
    userId: {{ auth()->id() }},
    participants: {{ json_encode($participants) }},
    friends: {{ json_encode($friends) }},
    activities: {{ json_encode($activities) }}
})">
    <!-- Left Sidebar (Server Channels) -->
    <aside class="voice-view-sidebar">
        {{-- Server Header --}}
        <div class="server-header" style="padding: 12px 16px; border-bottom: 1px solid #3f3f46;">
            <x-server-dropdown
                :server="$server"
                :isAdmin="auth()->user()->isServerAdmin($server->id)"
                :isOwner="$server->creator_id === auth()->id()"
            />
        </div>

        {{-- Channels List --}}
        <div class="sidebar-channels-container" style="flex: 1; overflow-y: auto; padding: 8px;">
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
                        :active="false"
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
                        :isActive="$ch->id === $channel->id"
                    />
                @endforeach
            </x-channel-category>
        </div>

        {{-- User Section --}}
        <div class="sidebar-user-section">
            <div class="sidebar-user-info">
                <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" class="sidebar-user-avatar">
                <div class="sidebar-user-details">
                    <div class="sidebar-user-name">{{ auth()->user()->display_name }}</div>
                    <div class="sidebar-user-username">{{ '@' . auth()->user()->username }}</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Voice View Area -->
    <main class="voice-view-main">
        <!-- Voice Channel Header -->
        <header class="voice-view-header">
            <div class="voice-header-left">
                <a href="{{ route('server.show', $server) }}" class="voice-back-btn" title="Back to Server">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="voice-header-info">
                    <div class="voice-header-channel">
                        <svg class="voice-header-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3c.53 0 1.039.211 1.414.586l3 3A2 2 0 0116.414 8H15a1 1 0 00-1 1v2.586l-2 2L10 11.586V9a1 1 0 00-1-1H7.586A2 2 0 019.586 6.586l3-3A2 2 0 0112 3zM9 13.414l2 2V18a1 1 0 001 1h1.586A2 2 0 0014.414 17.414l3-3A2 2 0 0012 21a2 2 0 01-1.414-.586l-3-3A2 2 0 019 15.414V13.414z"/>
                        </svg>
                        <h1 class="voice-header-name">{{ $channel->name }}</h1>
                    </div>
                    <span class="voice-header-users" x-text="users.length + ' ' + (users.length === 1 ? 'user' : 'users')">
                        {{ count($participants) }} {{ count($participants) === 1 ? 'user' : 'users' }}
                    </span>
                </div>
            </div>
            <div class="voice-header-actions">
                <button class="voice-header-btn" @click="showInviteModal = true" title="Invite Friends">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span>Invite</span>
                </button>
                <button class="voice-header-btn" @click="showActivityModal = true" title="Choose Activity">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Activity</span>
                </button>
                <button class="voice-header-btn" @click="showTextChat = !showTextChat" :class="{ 'active': showTextChat }" title="Toggle Text Chat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Voice User Grid -->
        <div class="voice-user-grid-container">
            <!-- Empty State (when alone) -->
            <div class="voice-empty-state" x-show="users.length <= 1" x-cloak>
                <div class="empty-illustration">
                    <div class="empty-icons">
                        <span class="empty-icon-item">&#127918;</span>
                        <span class="empty-icon-item">&#127942;</span>
                        <span class="empty-icon-item">&#128142;</span>
                    </div>
                </div>
                <h2 class="empty-title">It's quiet here...</h2>
                <p class="empty-subtitle">Invite friends to join your voice channel!</p>
                <div class="empty-actions">
                    <button class="empty-btn primary" @click="showInviteModal = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Invite Friends
                    </button>
                    <button class="empty-btn secondary" @click="showActivityModal = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Choose Activity
                    </button>
                </div>
            </div>

            <!-- User Grid (when users present) -->
            <div class="voice-user-grid" x-show="users.length > 1">
                <template x-for="user in users" :key="user.id">
                    <div class="voice-user-card"
                         :class="{ 'speaking': user.isSpeaking, 'muted': user.isMuted }"
                         @click="showUserPopover(user)">
                        <div class="user-avatar-wrapper" :class="{ 'speaking': user.isSpeaking }">
                            <img :src="user.avatar" :alt="user.name" class="user-avatar">
                            <!-- Speaking Ring -->
                            <div class="speaking-ring" x-show="user.isSpeaking"></div>
                            <!-- Status Icons -->
                            <div class="user-status-icons">
                                <span class="status-icon muted" x-show="user.isMuted" title="Muted">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                        <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </span>
                                <span class="status-icon deafened" x-show="user.isDeafened" title="Deafened">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
                                    </svg>
                                </span>
                                <span class="status-icon streaming" x-show="user.isStreaming" title="Streaming">
                                    LIVE
                                </span>
                            </div>
                        </div>
                        <span class="user-name" x-text="user.name"></span>
                        <span class="user-activity" x-show="user.activity" x-text="'Playing ' + user.activity"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Voice Control Bar -->
        <footer class="voice-control-bar">
            <div class="control-bar-info">
                <div class="connection-status">
                    <span class="status-dot" :class="connectionStatus"></span>
                    <span class="status-text" x-text="connectionStatusText">Voice Connected</span>
                </div>
                <div class="call-timer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="timer-icon">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                    <span x-text="formattedDuration">0:00</span>
                </div>
            </div>

            <div class="control-bar-buttons">
                <button class="control-btn"
                        :class="{ 'active': isMuted }"
                        @click="toggleMute()"
                        :title="isMuted ? 'Unmute' : 'Mute'">
                    <svg x-show="!isMuted" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                    <svg x-show="isMuted" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                        <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span class="btn-label">Mute</span>
                </button>

                <button class="control-btn"
                        :class="{ 'active': isDeafened }"
                        @click="toggleDeafen()"
                        :title="isDeafened ? 'Undeafen' : 'Deafen'">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z"/>
                    </svg>
                    <span class="btn-label">Deafen</span>
                </button>

                <button class="control-btn" disabled title="Video (Coming Soon)">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                    </svg>
                    <span class="btn-label">Video</span>
                </button>

                <button class="control-btn" disabled title="Screen Share (Coming Soon)">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/>
                    </svg>
                    <span class="btn-label">Share</span>
                </button>

                <button class="control-btn" @click="showActivityModal = true" title="Activities">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.58 16.09l-1.09-7.66C20.21 6.46 18.52 5 16.53 5H7.47C5.48 5 3.79 6.46 3.51 8.43l-1.09 7.66C2.2 17.63 3.39 19 4.94 19H9v-3H6.44l.82-6H9v2c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-2h1.74l.82 6H15v3h4.06c1.55 0 2.74-1.37 2.52-2.91zM12 12v-2h-2v2H8l4-4 4 4h-4z"/>
                    </svg>
                    <span class="btn-label">Activity</span>
                </button>

                <button class="control-btn" @click="$dispatch('open-voice-settings')" title="Voice Settings">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                    </svg>
                    <span class="btn-label">Settings</span>
                </button>
            </div>

            <div class="control-bar-right">
                <div class="connection-quality" @click="showStats = !showStats" title="Connection Quality">
                    <div class="quality-bars" :data-quality="networkQuality">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                    <span class="ping-text" x-text="ping + 'ms'">0ms</span>
                </div>
                <button class="disconnect-btn" @click="disconnect()" title="Disconnect">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 9c-1.6 0-3.15.25-4.6.72v3.1c0 .39-.23.74-.56.9-.98.49-1.87 1.12-2.66 1.85-.18.18-.43.28-.7.28-.28 0-.53-.11-.71-.29L.29 13.08c-.18-.17-.29-.42-.29-.7 0-.28.11-.53.29-.71C3.34 8.78 7.46 7 12 7s8.66 1.78 11.71 4.67c.18.18.29.43.29.71 0 .28-.11.53-.29.71l-2.48 2.48c-.18.18-.43.29-.71.29-.27 0-.52-.11-.7-.28-.79-.74-1.69-1.36-2.67-1.85-.33-.16-.56-.5-.56-.9v-3.1C15.15 9.25 13.6 9 12 9z"/>
                    </svg>
                    <span>Disconnect</span>
                </button>
            </div>
        </footer>
    </main>

    <!-- Text Chat Panel (Toggle) -->
    <aside class="voice-text-chat-panel" x-show="showTextChat" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">
        <div class="text-chat-header">
            <h3>Voice Chat</h3>
            <button class="close-chat-btn" @click="showTextChat = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="text-chat-messages" id="voice-text-messages">
            <div class="no-messages">
                <p>No messages yet. Start the conversation!</p>
            </div>
        </div>
        <div class="text-chat-input">
            <input type="text" placeholder="Message #{{ $channel->name }}" @keydown.enter="sendTextMessage($event.target.value); $event.target.value = ''">
            <button class="send-btn" @click="sendTextMessage($refs.chatInput?.value)">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </aside>

    <!-- Invite Friends Modal -->
    <div class="modal-overlay" x-show="showInviteModal" x-cloak @click.self="showInviteModal = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-content invite-modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-header">
                <h2>Invite Friends to {{ $channel->name }}</h2>
                <button class="modal-close" @click="showInviteModal = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="search-input-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" placeholder="Search friends..." x-model="friendSearch" class="friend-search-input">
                </div>
                <div class="friends-list">
                    <template x-for="friend in filteredFriends" :key="friend.id">
                        <div class="friend-item" :class="{ 'selected': selectedFriends.includes(friend.id) }" @click="toggleFriendSelection(friend.id)">
                            <img :src="friend.avatar" :alt="friend.name" class="friend-avatar">
                            <div class="friend-info">
                                <span class="friend-name" x-text="friend.name"></span>
                                <span class="friend-status" x-text="friend.activity || friend.status"></span>
                            </div>
                            <div class="friend-status-dot" :data-status="friend.status"></div>
                            <input type="checkbox" :checked="selectedFriends.includes(friend.id)" class="friend-checkbox">
                        </div>
                    </template>
                    <div class="no-friends" x-show="filteredFriends.length === 0">
                        <p x-show="friends.length === 0">You don't have any friends yet.</p>
                        <p x-show="friends.length > 0 && filteredFriends.length === 0">No friends match your search.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="invite-link-wrapper">
                    <button class="copy-link-btn" @click="copyInviteLink()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Copy Invite Link
                    </button>
                </div>
                <button class="btn-primary" @click="sendInvites()" :disabled="selectedFriends.length === 0">
                    Send Invite <span x-show="selectedFriends.length > 0" x-text="'(' + selectedFriends.length + ')'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Choose Activity Modal -->
    <div class="modal-overlay" x-show="showActivityModal" x-cloak @click.self="showActivityModal = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-content activity-modal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-header">
                <h2>Choose an Activity</h2>
                <button class="modal-close" @click="showActivityModal = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="activity-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" placeholder="Search activities..." x-model="activitySearch">
                </div>
                <div class="activity-section">
                    <h3 class="activity-section-title">Popular</h3>
                    <div class="activity-grid">
                        <template x-for="activity in filteredActivities.filter(a => a.popular)" :key="activity.id">
                            <div class="activity-card" @click="startActivity(activity)">
                                <div class="activity-icon" x-html="getActivityIcon(activity.icon)"></div>
                                <div class="activity-info">
                                    <span class="activity-name" x-text="activity.name"></span>
                                    <span class="activity-desc" x-text="activity.description"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="activity-section">
                    <h3 class="activity-section-title">All Activities</h3>
                    <div class="activity-grid">
                        <template x-for="activity in filteredActivities" :key="activity.id">
                            <div class="activity-card" @click="startActivity(activity)">
                                <div class="activity-icon" x-html="getActivityIcon(activity.icon)"></div>
                                <div class="activity-info">
                                    <span class="activity-name" x-text="activity.name"></span>
                                    <span class="activity-desc" x-text="activity.description"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Stats Popup -->
    <div class="stats-popup" x-show="showStats" x-cloak @click.away="showStats = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <div class="stats-header">
            <span>Connection Stats</span>
            <button @click="showStats = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="stat-row">
            <span class="stat-label">Quality</span>
            <span class="stat-value quality-badge" :class="networkQuality" x-text="networkQuality"></span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Ping</span>
            <span class="stat-value" x-text="ping + 'ms'"></span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Region</span>
            <span class="stat-value">Auto</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Codec</span>
            <span class="stat-value">Opus</span>
        </div>
    </div>
</div>

{{-- Voice Settings Popup (reuse from Phase 5) --}}
<x-voice-settings-popup />

<script>
// Voice Channel View Alpine.js Component
document.addEventListener('alpine:init', () => {
    Alpine.data('voiceChannelView', (config) => ({
        // Configuration
        serverId: config.serverId,
        channelId: config.channelId,
        channelName: config.channelName,
        userId: config.userId,

        // Users
        users: config.participants || [],
        friends: config.friends || [],
        activities: config.activities || [],

        // UI State
        showInviteModal: false,
        showActivityModal: false,
        showTextChat: false,
        showStats: false,

        // Voice State
        isConnected: false,
        isMuted: false,
        isDeafened: false,
        connectionStatus: 'connecting',
        connectionStatusText: 'Connecting...',
        networkQuality: 'excellent',
        ping: 0,
        callDuration: 0,

        // Search/Filter
        friendSearch: '',
        activitySearch: '',
        selectedFriends: [],

        // Timer
        timerInterval: null,

        init() {
            console.log('[VoiceView] Alpine component initializing...');

            // Listen for voice events from voice-chat.js
            this.setupEventListeners();

            // Start call timer
            this.startTimer();

            // Auto-join the voice channel (with wait for voice-chat.js to load)
            this.waitForVoiceChatAndJoin();
        },

        /**
         * Wait for voice-chat.js to load before attempting to join
         * This handles the timing issue where Alpine init runs before voice-chat.js loads
         */
        async waitForVoiceChatAndJoin() {
            const maxAttempts = 50; // 5 seconds max wait
            const interval = 100; // Check every 100ms

            for (let attempt = 0; attempt < maxAttempts; attempt++) {
                if (typeof window.joinVoiceChannel === 'function') {
                    console.log('[VoiceView] voice-chat.js loaded, joining channel...');
                    await this.joinChannel();
                    return;
                }

                // Wait before next attempt
                await new Promise(resolve => setTimeout(resolve, interval));
            }

            console.error('[VoiceView] voice-chat.js failed to load after 5 seconds');
            this.connectionStatus = 'failed';
            this.connectionStatusText = 'Failed to load voice chat';
        },

        setupEventListeners() {
            // Connection events
            window.addEventListener('voice-connected', (e) => {
                this.isConnected = true;
                this.connectionStatus = 'connected';
                this.connectionStatusText = 'Voice Connected';
            });

            window.addEventListener('voice-disconnected', () => {
                this.isConnected = false;
                this.connectionStatus = 'disconnected';
                this.connectionStatusText = 'Disconnected';
            });

            window.addEventListener('voice-mute-changed', (e) => {
                this.isMuted = e.detail.isMuted;
            });

            window.addEventListener('voice-deafen-changed', (e) => {
                this.isDeafened = e.detail.isDeafened;
            });

            window.addEventListener('voice-quality-update', (e) => {
                this.networkQuality = e.detail.quality;
                this.ping = e.detail.ping || 0;
            });

            // Real-time user updates via Laravel Echo
            if (window.Echo) {
                window.Echo.private(`server.${this.serverId}`)
                    .listen('.voice.user.joined', (e) => this.handleUserJoined(e))
                    .listen('.voice.user.left', (e) => this.handleUserLeft(e))
                    .listen('.voice.user.speaking', (e) => this.handleUserSpeaking(e))
                    .listen('.voice.user.muted', (e) => this.handleUserMuted(e))
                    .listen('.voice.user.deafened', (e) => this.handleUserDeafened(e));
            }
        },

        async joinChannel() {
            try {
                console.log('[VoiceView] joinChannel called', {
                    serverId: this.serverId,
                    channelId: this.channelId,
                    channelName: this.channelName
                });

                this.connectionStatus = 'connecting';
                this.connectionStatusText = 'Connecting...';

                if (typeof window.joinVoiceChannel === 'function') {
                    const success = await window.joinVoiceChannel(this.serverId, this.channelId, this.channelName);

                    if (success) {
                        console.log('[VoiceView] Successfully joined voice channel');
                        // The voice-connected event will update the status
                    } else {
                        console.error('[VoiceView] Failed to join voice channel - returned false');
                        this.connectionStatus = 'failed';
                        this.connectionStatusText = 'Connection Failed';
                    }
                } else {
                    console.error('[VoiceView] window.joinVoiceChannel is not available');
                    this.connectionStatus = 'failed';
                    this.connectionStatusText = 'Voice chat unavailable';
                }
            } catch (error) {
                console.error('[VoiceView] Failed to join voice channel:', error);
                this.connectionStatus = 'failed';
                this.connectionStatusText = 'Connection Error';
            }
        },

        async disconnect() {
            try {
                if (typeof window.disconnectVoice === 'function') {
                    await window.disconnectVoice();
                }
                // Redirect back to server
                window.location.href = `/servers/${this.serverId}`;
            } catch (error) {
                console.error('Failed to disconnect:', error);
            }
        },

        async toggleMute() {
            try {
                // Check local connection state first for better UX
                if (!this.isConnected) {
                    console.warn('[VoiceView] Cannot toggle mute - not connected');
                    this.showNotification('You must be connected to voice to mute/unmute', 'warning');
                    return;
                }

                if (typeof window.toggleMute === 'function') {
                    await window.toggleMute();
                }
            } catch (error) {
                console.error('Failed to toggle mute:', error);
            }
        },

        async toggleDeafen() {
            try {
                // Check local connection state first for better UX
                if (!this.isConnected) {
                    console.warn('[VoiceView] Cannot toggle deafen - not connected');
                    this.showNotification('You must be connected to voice to deafen/undeafen', 'warning');
                    return;
                }

                if (typeof window.toggleDeafen === 'function') {
                    await window.toggleDeafen();
                }
            } catch (error) {
                console.error('Failed to toggle deafen:', error);
            }
        },

        startTimer() {
            this.timerInterval = setInterval(() => {
                this.callDuration++;
            }, 1000);
        },

        get formattedDuration() {
            const hours = Math.floor(this.callDuration / 3600);
            const minutes = Math.floor((this.callDuration % 3600) / 60);
            const seconds = this.callDuration % 60;

            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        },

        get filteredFriends() {
            if (!this.friendSearch) return this.friends;
            const search = this.friendSearch.toLowerCase();
            return this.friends.filter(f =>
                f.name.toLowerCase().includes(search) ||
                f.username.toLowerCase().includes(search)
            );
        },

        get filteredActivities() {
            if (!this.activitySearch) return this.activities;
            const search = this.activitySearch.toLowerCase();
            return this.activities.filter(a =>
                a.name.toLowerCase().includes(search) ||
                a.description.toLowerCase().includes(search)
            );
        },

        toggleFriendSelection(friendId) {
            const index = this.selectedFriends.indexOf(friendId);
            if (index > -1) {
                this.selectedFriends.splice(index, 1);
            } else {
                this.selectedFriends.push(friendId);
            }
        },

        async sendInvites() {
            if (this.selectedFriends.length === 0) return;

            try {
                const response = await fetch(`/voice/channel/${this.channelId}/invite`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ user_ids: this.selectedFriends })
                });

                const data = await response.json();
                if (data.success) {
                    this.showNotification(data.message, 'success');
                    this.showInviteModal = false;
                    this.selectedFriends = [];
                } else {
                    this.showNotification(data.message || 'Failed to send invites', 'error');
                }
            } catch (error) {
                console.error('Failed to send invites:', error);
                this.showNotification('Failed to send invites', 'error');
            }
        },

        copyInviteLink() {
            const link = `${window.location.origin}/servers/${this.serverId}/voice/${this.channelId}`;
            navigator.clipboard.writeText(link).then(() => {
                this.showNotification('Invite link copied!', 'success');
            }).catch(() => {
                this.showNotification('Failed to copy link', 'error');
            });
        },

        startActivity(activity) {
            this.showNotification(`Starting ${activity.name}...`, 'info');
            this.showActivityModal = false;
            // TODO: Implement activity start logic
        },

        getActivityIcon(iconName) {
            const icons = {
                'play-circle': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>',
                'puzzle': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5C13 2.12 11.88 1 10.5 1S8 2.12 8 3.5V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5c1.49 0 2.7 1.21 2.7 2.7s-1.21 2.7-2.7 2.7H2V20c0 1.1.9 2 2 2h3.8v-1.5c0-1.49 1.21-2.7 2.7-2.7 1.49 0 2.7 1.21 2.7 2.7V22H17c1.1 0 2-.9 2-2v-4h1.5c1.38 0 2.5-1.12 2.5-2.5S21.88 11 20.5 11z"/></svg>',
                'cards': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.47 4.35l-1.34-.56v9.03l2.43-5.86c.41-1.02-.06-2.19-1.09-2.61zm-19.5 3.7L6.93 20a2.01 2.01 0 001.81 1.26c.26 0 .53-.05.79-.16l7.37-3.05c.75-.31 1.21-1.05 1.23-1.79.01-.26-.04-.55-.13-.81L13 3.5c-.29-.74-1.01-1.24-1.81-1.24-.25 0-.51.05-.76.14L3.06 5.45C2.25 5.77 1.8 6.68 2.04 7.5l-.07.55z"/></svg>',
                'pencil': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
                'question': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>',
                'music': '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>'
            };
            return icons[iconName] || icons['puzzle'];
        },

        showUserPopover(user) {
            // TODO: Show user popover/profile card
            console.log('Show popover for user:', user);
        },

        sendTextMessage(message) {
            if (!message || !message.trim()) return;
            // TODO: Send message to voice channel text chat
            console.log('Send message:', message);
        },

        showNotification(message, type = 'info') {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            } else {
                console.log(`[${type}] ${message}`);
            }
        },

        // Real-time event handlers
        handleUserJoined(event) {
            if (event.channel_id !== this.channelId) return;

            const userId = event.user?.id || event.user_id;
            const existingUser = this.users.find(u => u.id === userId);
            if (!existingUser) {
                this.users.push({
                    id: userId,
                    name: event.user?.display_name || event.user?.username || 'Unknown',
                    avatar: event.user?.avatar_url || '/images/default-avatar.png',
                    isSpeaking: false,
                    isMuted: false,
                    isDeafened: false,
                    isStreaming: false,
                    activity: null
                });
            }
        },

        handleUserLeft(event) {
            if (event.channel_id !== this.channelId) return;

            const userId = event.user?.id || event.user_id;
            this.users = this.users.filter(u => u.id !== userId);
        },

        handleUserSpeaking(event) {
            if (event.channel_id !== this.channelId) return;

            const user = this.users.find(u => u.id === event.user_id);
            if (user) {
                user.isSpeaking = event.is_speaking;
            }
        },

        handleUserMuted(event) {
            if (event.channel_id !== this.channelId) return;

            const userId = event.user?.id || event.user_id;
            const user = this.users.find(u => u.id === userId);
            if (user) {
                user.isMuted = event.is_muted;
            }
        },

        handleUserDeafened(event) {
            if (event.channel_id !== this.channelId) return;

            const userId = event.user?.id || event.user_id;
            const user = this.users.find(u => u.id === userId);
            if (user) {
                user.isDeafened = event.is_deafened;
            }
        },

        destroy() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }
        }
    }));
});
</script>
@endsection
