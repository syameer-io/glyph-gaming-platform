@extends('layouts.app')

@section('title', 'Lobbies - Glyph')

@push('styles')
<style>
    /* Minimal inline styles for Alpine.js specific behaviors */
    [x-cloak] { display: none !important; }

    /* Step Section Animation */
    .step-section {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Loading spinner for inline use */
    .loading-spinner-small {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid var(--color-border-primary);
        border-radius: 50%;
        border-top-color: #667eea;
        animation: lobbySpin 1s ease-in-out infinite;
    }

    /* Owned badge for game cards */
    .owned-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        z-index: 2;
    }

    .selection-checkmark {
        position: absolute;
        top: 8px;
        left: 8px;
        color: #10b981;
        background-color: rgba(0, 0, 0, 0.6);
        border-radius: 50%;
        padding: 4px;
        z-index: 2;
    }

    /* Game card name color fix for light theme */
    .lobby-game-card-name {
        color: white !important;
    }
</style>
@endpush

@section('content')
<x-navbar active-section="lobbies" />

<main>
    <div class="lobby-container" x-data="lobbyPage({{ $user->id }}, {{ json_encode($combinedGames) }}, {{ json_encode($friendIds) }})">
        {{-- Session Alerts --}}
        @if (session('success'))
            <div class="lobby-alert lobby-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="lobby-alert lobby-alert-error">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page Header --}}
        <div class="page-header">
            <h1 class="page-title">Game Lobbies</h1>
        </div>

        {{-- Main Layout --}}
        <div class="lobby-main">
            {{-- Sidebar: Your Active Lobbies --}}
            <div class="lobby-sidebar">
                <div class="lobby-sidebar-section" data-stagger="0">
                    <div class="lobby-sidebar-header">
                        <h3 class="lobby-sidebar-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            Your Active Lobbies
                        </h3>
                        <span class="lobby-count-badge" x-text="activeLobbies.length"></span>
                    </div>

                    {{-- Active Lobbies List --}}
                    <div x-ref="lobbiesList">
                        <template x-if="activeLobbies.length === 0">
                            <div class="lobby-empty-sidebar">
                                <p>No active lobbies</p>
                                <a href="#create-form" class="lobby-create-link">Create your first lobby</a>
                            </div>
                        </template>

                        <template x-for="lobby in activeLobbies" :key="lobby.id">
                            <div class="lobby-active-item">
                                <div class="lobby-item-header">
                                    <div>
                                        <p class="lobby-game-name" x-text="lobby.gaming_preference?.game_name || 'Unknown Game'"></p>
                                        <p class="lobby-join-method-label" x-text="formatJoinMethod(lobby.join_method)"></p>
                                    </div>
                                    <button
                                        type="button"
                                        class="lobby-delete-btn"
                                        @click="deleteLobby(lobby.id)"
                                        title="Delete lobby"
                                    >
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>

                                {{-- Time Remaining --}}
                                <div
                                    class="lobby-time-remaining"
                                    :class="lobby.expires_at && getTimeRemainingMinutes(lobby.expires_at) < 5 ? 'expiring' : ''"
                                >
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    <span
                                        :data-expires="lobby.expires_at"
                                        x-text="formatTimeRemaining(lobby.expires_at)"
                                    ></span>
                                </div>

                                {{-- Join Info --}}
                                <div class="lobby-join-info">
                                    <span x-text="getJoinInfo(lobby)"></span>
                                    <button
                                        type="button"
                                        class="lobby-copy-btn"
                                        @click="copyToClipboard(getJoinInfo(lobby), lobby.id)"
                                        :title="copiedLobbyId === lobby.id ? 'Copied!' : 'Copy'"
                                    >
                                        <svg x-show="copiedLobbyId !== lobby.id" width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                        </svg>
                                        <svg x-show="copiedLobbyId === lobby.id" x-cloak width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="color: #10b981;">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="lobby-content">
                {{-- Create Lobby Section --}}
                <div class="lobby-section" data-stagger="0" id="create-form">
                    <div class="lobby-section-header">
                        <h2 class="lobby-section-title">
                            <span class="lobby-section-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </span>
                            Create a Game Lobby
                        </h2>
                    </div>

                    {{-- Step Indicator --}}
                    <div class="lobby-step-indicator">
                        {{-- Step 1 --}}
                        <div
                            class="lobby-step-item"
                            :class="{
                                'active': currentStep === 1,
                                'completed': currentStep > 1,
                                'pending': currentStep < 1
                            }"
                            @click="currentStep > 1 && goToStep(1)"
                        >
                            <div class="lobby-step-number">
                                <template x-if="currentStep > 1">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= 1">
                                    <span>1</span>
                                </template>
                            </div>
                            <span class="lobby-step-label">Select Game</span>
                        </div>

                        <div class="lobby-step-connector" :class="{ 'completed': currentStep > 1 }"></div>

                        {{-- Step 2 --}}
                        <div
                            class="lobby-step-item"
                            :class="{
                                'active': currentStep === 2,
                                'completed': currentStep > 2,
                                'pending': currentStep < 2
                            }"
                            @click="currentStep > 2 && goToStep(2)"
                        >
                            <div class="lobby-step-number">
                                <template x-if="currentStep > 2">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= 2">
                                    <span>2</span>
                                </template>
                            </div>
                            <span class="lobby-step-label">Join Method</span>
                        </div>

                        <div class="lobby-step-connector" :class="{ 'completed': currentStep > 2 }"></div>

                        {{-- Step 3 --}}
                        <div
                            class="lobby-step-item"
                            :class="{
                                'active': currentStep === 3,
                                'completed': currentStep > 3,
                                'pending': currentStep < 3
                            }"
                        >
                            <div class="lobby-step-number">
                                <span>3</span>
                            </div>
                            <span class="lobby-step-label">Enter Details</span>
                        </div>
                    </div>

                    {{-- Error Alert --}}
                    <div x-show="error" x-cloak class="lobby-alert lobby-alert-error">
                        <span x-text="error"></span>
                    </div>

                    {{-- Success Alert --}}
                    <div x-show="success" x-cloak class="lobby-alert lobby-alert-success">
                        <span x-text="success"></span>
                    </div>

                    {{-- STEP 1: SELECT GAME --}}
                    <div x-show="currentStep === 1" class="step-section">
                        <div style="margin-bottom: 8px;">
                            <h3 style="color: var(--color-text-primary); font-size: 18px; font-weight: 600; margin: 0;">Select a Game</h3>
                        </div>
                        <p style="color: var(--color-text-muted); font-size: 14px; margin: 0 0 16px 0;">Choose the game you want to create a lobby for. Games you own are marked with a badge.</p>

                        <div class="lobby-games-grid">
                            @forelse($combinedGames as $game)
                                <x-lobby-game-card :game="$game" />
                            @empty
                                <div class="lobby-empty" style="grid-column: 1 / -1;">
                                    <svg width="48" height="48" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                    <p>No games with lobby support found.</p>
                                    <p class="lobby-empty-subtitle">Connect your Steam account to see your games.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- STEP 2: SELECT JOIN METHOD --}}
                    <div x-show="currentStep === 2" x-cloak class="step-section">
                        <button type="button" class="lobby-back-button" @click="goBack()">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Back to Games
                        </button>

                        {{-- Selected Game Summary --}}
                        <div class="lobby-selected-summary">
                            <div class="lobby-selected-banner">
                                <img :src="selectedGameImg" :alt="selectedGameName">
                            </div>
                            <div class="lobby-selected-info">
                                <h4 x-text="selectedGameName"></h4>
                                <p>
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Game Selected
                                </p>
                            </div>
                            <a class="lobby-change-link" @click="goToStep(1)">Change</a>
                        </div>

                        <div style="margin-bottom: 8px;">
                            <h3 style="color: var(--color-text-primary); font-size: 18px; font-weight: 600; margin: 0;">Choose Join Method</h3>
                        </div>
                        <p style="color: var(--color-text-muted); font-size: 14px; margin: 0 0 16px 0;">Select how other players will join your lobby.</p>

                        {{-- Loading State --}}
                        <div x-show="loading" x-cloak class="lobby-loading">
                            <div class="lobby-loading-spinner"></div>
                            <p>Loading join methods...</p>
                        </div>

                        {{-- Join Method Cards Grid --}}
                        <div x-show="!loading && availableJoinMethods.length > 0" class="lobby-methods-grid">
                            <template x-for="method in availableJoinMethods" :key="method.join_method">
                                <div
                                    class="lobby-method-card"
                                    :class="{ 'selected': selectedJoinMethod === method.join_method }"
                                    @click="selectJoinMethod(method)"
                                >
                                    <div class="lobby-method-icon" x-html="getMethodIcon(method.join_method)"></div>
                                    <div class="lobby-method-name" x-text="method.display_name"></div>
                                    <div class="lobby-method-desc" x-text="getMethodDescription(method.join_method)"></div>
                                </div>
                            </template>
                        </div>

                        {{-- No Methods Available --}}
                        <div x-show="!loading && availableJoinMethods.length === 0" x-cloak class="lobby-empty">
                            <p>No join methods available for this game.</p>
                            <button type="button" class="lobby-btn lobby-btn-ghost" style="margin-top: 12px;" @click="goToStep(1)">Select a Different Game</button>
                        </div>
                    </div>

                    {{-- STEP 3: ENTER DETAILS --}}
                    <div x-show="currentStep === 3" x-cloak class="step-section">
                        <button type="button" class="lobby-back-button" @click="goBack()">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Back to Join Methods
                        </button>

                        {{-- Selected Summary --}}
                        <div class="lobby-selected-summary">
                            <div class="lobby-selected-banner">
                                <img :src="selectedGameImg" :alt="selectedGameName">
                            </div>
                            <div class="lobby-selected-info">
                                <h4 x-text="selectedGameName"></h4>
                                <p>
                                    <span x-text="selectedMethodData?.display_name || formatJoinMethod(selectedJoinMethod)"></span>
                                </p>
                            </div>
                            <a class="lobby-change-link" @click="goToStep(1)">Start Over</a>
                        </div>

                        <div style="margin: 24px 0 8px 0;">
                            <h3 style="color: var(--color-text-primary); font-size: 18px; font-weight: 600; margin: 0;">Enter Details</h3>
                        </div>
                        <p style="color: var(--color-text-muted); font-size: 14px; margin: 0 0 16px 0;">Provide the information needed for players to join your lobby.</p>

                        {{-- Steam Lobby Link --}}
                        <div x-show="selectedJoinMethod === 'steam_lobby'" x-cloak class="lobby-form-group">
                            <label class="lobby-form-label">Steam Lobby Link</label>
                            <input
                                type="text"
                                x-model="formData.steam_lobby_link"
                                placeholder="steam://joinlobby/..."
                                :disabled="loading"
                                class="lobby-form-control"
                            >
                            <p class="lobby-form-help">Get your lobby link from Steam overlay (Shift+Tab) > View Players > Right-click lobby > Copy Lobby ID</p>
                        </div>

                        {{-- Steam Connect / Server Address --}}
                        <div x-show="selectedJoinMethod === 'steam_connect' || selectedJoinMethod === 'server_address'" x-cloak>
                            <div class="lobby-form-group">
                                <label class="lobby-form-label">Server IP or Domain</label>
                                <input
                                    type="text"
                                    x-model="formData.server_ip"
                                    placeholder="e.g., 192.168.1.1 or play.server.com"
                                    :disabled="loading"
                                    class="lobby-form-control"
                                >
                            </div>
                            <div class="lobby-form-group">
                                <label class="lobby-form-label">Port (Optional)</label>
                                <input
                                    type="number"
                                    x-model="formData.server_port"
                                    placeholder="e.g., 27015"
                                    :disabled="loading"
                                    min="1"
                                    max="65535"
                                    class="lobby-form-control"
                                >
                            </div>
                            <div class="lobby-form-group">
                                <label class="lobby-form-label">Server Password (Optional)</label>
                                <input
                                    type="password"
                                    x-model="formData.server_password"
                                    placeholder="Leave empty if no password"
                                    :disabled="loading"
                                    class="lobby-form-control"
                                >
                            </div>
                        </div>

                        {{-- Lobby Code --}}
                        <div x-show="selectedJoinMethod === 'lobby_code'" x-cloak class="lobby-form-group">
                            <label class="lobby-form-label">Lobby/Party Code</label>
                            <input
                                type="text"
                                x-model="formData.lobby_code"
                                placeholder="e.g., AB12CD"
                                :disabled="loading"
                                maxlength="50"
                                class="lobby-form-control"
                                style="text-transform: uppercase;"
                            >
                            <p class="lobby-form-help">Enter the party code from your game</p>
                        </div>

                        {{-- Join Command --}}
                        <div x-show="selectedJoinMethod === 'join_command'" x-cloak class="lobby-form-group">
                            <label class="lobby-form-label">Join Command</label>
                            <input
                                type="text"
                                x-model="formData.join_command"
                                placeholder="e.g., /join player123"
                                :disabled="loading"
                                class="lobby-form-control"
                            >
                            <p class="lobby-form-help">Enter the in-game command others will use to join</p>
                        </div>

                        {{-- Private Match --}}
                        <div x-show="selectedJoinMethod === 'private_match'" x-cloak>
                            <div class="lobby-form-group">
                                <label class="lobby-form-label">Match/Room Name</label>
                                <input
                                    type="text"
                                    x-model="formData.match_name"
                                    placeholder="Your match or room name"
                                    :disabled="loading"
                                    class="lobby-form-control"
                                >
                            </div>
                            <div class="lobby-form-group">
                                <label class="lobby-form-label">Match Password (Optional)</label>
                                <input
                                    type="password"
                                    x-model="formData.match_password"
                                    placeholder="Leave empty if no password"
                                    :disabled="loading"
                                    class="lobby-form-control"
                                >
                            </div>
                        </div>

                        {{-- Manual Invite --}}
                        <div x-show="selectedJoinMethod === 'manual_invite'" x-cloak class="lobby-form-group">
                            <label class="lobby-form-label">Join Instructions</label>
                            <textarea
                                x-model="formData.manual_instructions"
                                placeholder="Enter instructions for joining (e.g., 'Add me on Steam and I'll invite you')"
                                :disabled="loading"
                                class="lobby-form-control"
                                rows="3"
                            ></textarea>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="lobby-form-group" style="margin-top: 24px; display: flex; gap: 12px;">
                            <button
                                type="button"
                                @click="saveLobby()"
                                :disabled="loading || !canSave()"
                                class="lobby-btn lobby-btn-primary"
                                style="flex: 1;"
                            >
                                <span x-show="!loading">Create Lobby</span>
                                <span x-show="loading" x-cloak>
                                    <span class="loading-spinner-small"></span>
                                    Creating...
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="resetForm()"
                                :disabled="loading"
                                class="lobby-btn lobby-btn-ghost"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    {{-- Initial State (Step 1 visible by default) --}}
                    <template x-if="currentStep === 1 && games.length === 0">
                        <div class="lobby-empty">
                            <svg width="48" height="48" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            <p>No games available.</p>
                            <p class="lobby-empty-subtitle">Connect your Steam account to get started.</p>
                        </div>
                    </template>
                </div>

                {{-- Active Lobbies Feed Section --}}
                <div class="lobby-section" data-stagger="1">
                    <div class="lobby-feed-header">
                        <h2 class="lobby-section-title">
                            <span class="lobby-section-title-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            Active Lobbies from Your Network
                        </h2>
                        <div class="lobby-feed-controls">
                            {{-- Game Filter --}}
                            <select
                                class="lobby-feed-filter"
                                x-model="feedFilter.game"
                                @change="loadFeed()"
                            >
                                <option value="">All Games</option>
                                @foreach($supportedGames as $game)
                                    <option value="{{ $game['game_id'] }}">{{ $game['game_name'] }}</option>
                                @endforeach
                            </select>

                            {{-- Source Filter --}}
                            <select
                                class="lobby-feed-filter"
                                x-model="feedFilter.source"
                                @change="loadFeed()"
                            >
                                <option value="all">All Sources</option>
                                <option value="friends">Friends Only</option>
                                <option value="servers">Server Members</option>
                            </select>

                            {{-- Refresh Button --}}
                            <button
                                type="button"
                                class="lobby-refresh-btn"
                                :class="{ 'loading': feedLoading }"
                                @click="loadFeed()"
                                :disabled="feedLoading"
                            >
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                                <span x-show="!feedLoading">Refresh</span>
                                <span x-show="feedLoading" x-cloak>Loading...</span>
                            </button>
                        </div>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="feedLoading && feedLobbies.length === 0" x-cloak class="lobby-loading">
                        <div class="lobby-loading-spinner"></div>
                        <p>Loading lobbies from your network...</p>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!feedLoading && feedLobbies.length === 0" x-cloak class="lobby-empty">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                        <p>No active lobbies from your network</p>
                        <p class="lobby-empty-subtitle">
                            <template x-if="feedFilter.source === 'friends'">
                                <span>Your friends haven't created any lobbies yet.</span>
                            </template>
                            <template x-if="feedFilter.source === 'servers'">
                                <span>No server members have active lobbies.</span>
                            </template>
                            <template x-if="feedFilter.source === 'all'">
                                <span>When your friends or server members create lobbies, they'll appear here.</span>
                            </template>
                        </p>
                    </div>

                    {{-- Feed Grid --}}
                    <div x-show="feedLobbies.length > 0" class="lobby-feed-grid">
                        <template x-for="lobby in feedLobbies" :key="lobby.id">
                            <div class="lobby-feed-card">
                                {{-- Card Header: User Info --}}
                                <div class="lobby-feed-card-header">
                                    <img
                                        :src="lobby.user.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(lobby.user.display_name) + '&background=667eea&color=fff'"
                                        :alt="lobby.user.display_name"
                                        class="lobby-feed-avatar"
                                    >
                                    <div class="lobby-feed-user-info">
                                        <p class="lobby-feed-user-name" x-text="lobby.user.display_name"></p>
                                        <p class="lobby-feed-user-source">
                                            <span
                                                class="lobby-feed-source-badge"
                                                :class="lobby.source"
                                                x-text="lobby.source === 'friend' ? 'Friend' : 'Server'"
                                            ></span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Card Body: Game & Lobby Info --}}
                                <div class="lobby-feed-card-body">
                                    <h3 class="lobby-feed-game-name" x-text="lobby.game_name"></h3>
                                    <p class="lobby-feed-join-method" x-text="lobby.display_format"></p>
                                    <div
                                        class="lobby-feed-time"
                                        :class="{ 'expiring': lobby.expires_at && getTimeRemainingMinutes(lobby.expires_at) < 5 }"
                                    >
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="formatTimeRemaining(lobby.expires_at)"></span>
                                    </div>
                                </div>

                                {{-- Card Footer: Actions --}}
                                <div class="lobby-feed-card-footer">
                                    {{-- Direct Join (for steam:// links) --}}
                                    <template x-if="canDirectJoin(lobby)">
                                        <a
                                            :href="lobby.join_link"
                                            class="lobby-feed-join-btn"
                                            target="_blank"
                                        >
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                            </svg>
                                            Join Lobby
                                        </a>
                                    </template>

                                    {{-- Copy Info (for non-steam links) --}}
                                    <template x-if="!canDirectJoin(lobby)">
                                        <button
                                            type="button"
                                            class="lobby-feed-join-btn"
                                            @click="copyFeedJoinInfo(lobby)"
                                        >
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                                <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                            </svg>
                                            Copy Join Info
                                        </button>
                                    </template>

                                    {{-- Copy Button --}}
                                    <button
                                        type="button"
                                        class="lobby-feed-copy-btn"
                                        :class="{ 'copied': copiedFeedLobbyId === lobby.id }"
                                        @click="copyFeedJoinInfo(lobby)"
                                        :title="copiedFeedLobbyId === lobby.id ? 'Copied!' : 'Copy to clipboard'"
                                    >
                                        <svg x-show="copiedFeedLobbyId !== lobby.id" width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                        </svg>
                                        <svg x-show="copiedFeedLobbyId === lobby.id" x-cloak width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
