@extends('layouts.app')

@section('title', 'Lobbies - Glyph')

@push('styles')
<style>
    .lobbies-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 24px;
    }

    .lobbies-sidebar {
        background-color: #18181b;
        border-radius: 12px;
        padding: 24px;
        height: fit-content;
        position: sticky;
        top: 24px;
    }

    .lobbies-content {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .sidebar-header h3 {
        color: #efeff1;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .lobby-count-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 12px;
    }

    .active-lobby-item {
        background-color: #0e0e10;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .active-lobby-item:hover {
        border-color: #667eea;
    }

    .lobby-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .lobby-game-name {
        color: #efeff1;
        font-size: 14px;
        font-weight: 600;
        margin: 0;
    }

    .lobby-join-method {
        color: #b3b3b5;
        font-size: 12px;
        margin: 4px 0 0 0;
    }

    .lobby-delete-btn {
        background: none;
        border: none;
        color: #71717a;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .lobby-delete-btn:hover {
        background-color: #ef4444;
        color: white;
    }

    .lobby-time-remaining {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .lobby-time-remaining.expiring {
        color: #ef4444;
    }

    .lobby-time-remaining.active {
        color: #10b981;
    }

    .lobby-join-info {
        background-color: #18181b;
        padding: 8px 10px;
        border-radius: 4px;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 11px;
        color: #b3b3b5;
        word-break: break-all;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .lobby-copy-btn {
        background: none;
        border: none;
        color: #71717a;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .lobby-copy-btn:hover {
        color: #667eea;
    }

    .empty-lobbies {
        text-align: center;
        padding: 24px;
        color: #71717a;
        font-size: 14px;
    }

    .empty-lobbies p {
        margin: 0 0 12px 0;
    }

    .create-lobby-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .create-lobby-link:hover {
        text-decoration: underline;
    }

    /* Main content styles */
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .content-header h1 {
        color: #efeff1;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }

    .creation-card {
        background-color: #18181b;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #3f3f46;
    }

    .creation-card h2 {
        color: #efeff1;
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 20px 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        color: #b3b3b5;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        background-color: #0e0e10;
        border: 2px solid #3f3f46;
        border-radius: 8px;
        color: #efeff1;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-control:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .form-help {
        font-size: 12px;
        color: #71717a;
        margin-top: 6px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .btn-secondary {
        background-color: #3f3f46;
        color: #efeff1;
    }

    .btn-secondary:hover {
        background-color: #52525b;
    }

    .btn-ghost {
        background: transparent;
        color: #b3b3b5;
        border: 1px solid #3f3f46;
    }

    .btn-ghost:hover {
        background-color: #3f3f46;
        color: #efeff1;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
    }

    .alert-error {
        background-color: rgba(239, 68, 68, 0.15);
        border: 1px solid #ef4444;
        color: #ef4444;
    }

    .alert-success {
        background-color: rgba(16, 185, 129, 0.15);
        border: 1px solid #10b981;
        color: #10b981;
    }

    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #3f3f46;
        border-radius: 50%;
        border-top-color: #667eea;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* =====================================================
       STEP INDICATOR STYLES
       ===================================================== */
    .step-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 32px;
        padding: 0 20px;
    }

    .step-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: default;
    }

    .step-item.active {
        background-color: rgba(102, 126, 234, 0.15);
    }

    .step-item.completed {
        cursor: pointer;
    }

    .step-item.completed:hover {
        background-color: rgba(16, 185, 129, 0.1);
    }

    .step-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .step-item.pending .step-number {
        background-color: #3f3f46;
        color: #71717a;
    }

    .step-item.active .step-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .step-item.completed .step-number {
        background-color: #10b981;
        color: white;
    }

    .step-label {
        font-size: 14px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .step-item.pending .step-label {
        color: #71717a;
    }

    .step-item.active .step-label {
        color: #efeff1;
    }

    .step-item.completed .step-label {
        color: #10b981;
    }

    .step-connector {
        width: 40px;
        height: 2px;
        background-color: #3f3f46;
        transition: background-color 0.3s ease;
    }

    .step-connector.completed {
        background-color: #10b981;
    }

    /* =====================================================
       GAME CARD STYLES
       ===================================================== */
    .games-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .game-card {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #0e0e10;
    }

    .game-card:hover {
        transform: translateY(-4px);
        border-color: #667eea;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    }

    .game-card:focus {
        outline: none;
        border-color: #667eea;
    }

    .game-card.selected {
        border-color: #10b981;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
    }

    .game-card img {
        width: 100%;
        aspect-ratio: 460/215;
        object-fit: cover;
        display: block;
    }

    .game-card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.7) 50%, transparent 100%);
        padding: 24px 12px 12px 12px;
    }

    .game-card-name {
        color: #efeff1;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.3;
    }

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

    /* =====================================================
       JOIN METHOD CARD STYLES
       ===================================================== */
    .methods-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .method-card {
        background-color: #0e0e10;
        border: 2px solid #3f3f46;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
    }

    .method-card:hover {
        border-color: #667eea;
        background-color: #1f1f23;
        transform: translateY(-2px);
    }

    .method-card.selected {
        border-color: #10b981;
        background-color: rgba(16, 185, 129, 0.1);
    }

    .method-card-icon {
        width: 48px;
        height: 48px;
        color: #667eea;
        transition: color 0.2s ease;
    }

    .method-card.selected .method-card-icon {
        color: #10b981;
    }

    .method-card-name {
        color: #efeff1;
        font-size: 14px;
        font-weight: 600;
    }

    .method-card-desc {
        color: #71717a;
        font-size: 12px;
        line-height: 1.4;
    }

    /* =====================================================
       STEP SECTION STYLES
       ===================================================== */
    .step-section {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .step-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }

    .step-header h3 {
        color: #efeff1;
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .step-subtitle {
        color: #71717a;
        font-size: 14px;
        margin: 0 0 16px 0;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background-color: #3f3f46;
        color: #b3b3b5;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .back-button:hover {
        background-color: #52525b;
        color: #efeff1;
    }

    .selected-summary {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background-color: #0e0e10;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .selected-game-banner {
        width: 120px;
        border-radius: 6px;
        overflow: hidden;
    }

    .selected-game-banner img {
        width: 100%;
        display: block;
    }

    .selected-info {
        flex: 1;
    }

    .selected-info h4 {
        color: #efeff1;
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 4px 0;
    }

    .selected-info p {
        color: #10b981;
        font-size: 13px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .change-link {
        color: #667eea;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
    }

    .change-link:hover {
        text-decoration: underline;
    }

    /* =====================================================
       RESPONSIVE STYLES
       ===================================================== */
    @media (max-width: 1024px) {
        .games-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }

        .methods-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .lobbies-container {
            grid-template-columns: 1fr;
        }

        .lobbies-sidebar {
            position: static;
            order: 2;
        }

        .lobbies-content {
            order: 1;
        }

        .content-header h1 {
            font-size: 24px;
        }

        .step-indicator {
            flex-wrap: wrap;
            gap: 8px;
        }

        .step-connector {
            display: none;
        }

        .step-item {
            padding: 8px 12px;
        }

        .games-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .methods-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .selected-summary {
            flex-direction: column;
            text-align: center;
        }

        .selected-game-banner {
            width: 160px;
        }
    }

    @media (max-width: 480px) {
        .games-grid {
            grid-template-columns: 1fr;
        }

        .methods-grid {
            grid-template-columns: 1fr;
        }

        .step-item .step-label {
            display: none;
        }

        .step-item.active .step-label {
            display: block;
        }
    }

    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('dashboard') }}" class="link">Dashboard</a>
                <a href="{{ route('dm.index') }}" class="link">Messages</a>
                <a href="{{ route('friends.index') }}" class="link">Friends</a>
                <a href="{{ route('matchmaking.index') }}" class="link">Matchmaking</a>
                <a href="{{ route('teams.index') }}" class="link">Teams</a>
                <a href="{{ route('lobbies.index') }}" class="link" style="color: #667eea;">Lobbies</a>
                <a href="{{ route('servers.discover') }}" class="link">Servers</a>
                <a href="{{ route('settings') }}" class="link">Settings</a>
                <div class="navbar-user">
                    <a href="{{ route('profile.show', auth()->user()->username) }}">
                        <img src="{{ auth()->user()->profile->avatar_url }}" alt="{{ auth()->user()->display_name }}" class="user-avatar">
                    </a>
                    <span>{{ auth()->user()->display_name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container" x-data="lobbyPage({{ $user->id }}, {{ json_encode($combinedGames) }})">
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

        <div class="lobbies-container">
            <!-- Sidebar: Your Active Lobbies -->
            <div class="lobbies-sidebar">
                <div class="sidebar-header">
                    <h3>Your Active Lobbies</h3>
                    <span class="lobby-count-badge" x-text="activeLobbies.length"></span>
                </div>

                <!-- Active Lobbies List -->
                <div x-ref="lobbiesList">
                    <template x-if="activeLobbies.length === 0">
                        <div class="empty-lobbies">
                            <p>No active lobbies</p>
                            <a href="#create-form" class="create-lobby-link">Create your first lobby</a>
                        </div>
                    </template>

                    <template x-for="lobby in activeLobbies" :key="lobby.id">
                        <div class="active-lobby-item">
                            <div class="lobby-item-header">
                                <div>
                                    <p class="lobby-game-name" x-text="lobby.gaming_preference?.game_name || 'Unknown Game'"></p>
                                    <p class="lobby-join-method" x-text="formatJoinMethod(lobby.join_method)"></p>
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

                            <!-- Time Remaining -->
                            <div
                                class="lobby-time-remaining"
                                :class="lobby.expires_at && getTimeRemainingMinutes(lobby.expires_at) < 5 ? 'expiring' : 'active'"
                            >
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                <span
                                    :data-expires="lobby.expires_at"
                                    x-text="formatTimeRemaining(lobby.expires_at)"
                                ></span>
                            </div>

                            <!-- Join Info -->
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

            <!-- Main Content: Create Lobby -->
            <div class="lobbies-content">
                <div class="content-header">
                    <h1>Game Lobbies</h1>
                </div>

                <div class="creation-card" id="create-form">
                    <h2>Create a Game Lobby</h2>

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <!-- Step 1 -->
                        <div
                            class="step-item"
                            :class="{
                                'active': currentStep === 1,
                                'completed': currentStep > 1,
                                'pending': currentStep < 1
                            }"
                            @click="currentStep > 1 && goToStep(1)"
                        >
                            <div class="step-number">
                                <template x-if="currentStep > 1">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= 1">
                                    <span>1</span>
                                </template>
                            </div>
                            <span class="step-label">Select Game</span>
                        </div>

                        <div class="step-connector" :class="{ 'completed': currentStep > 1 }"></div>

                        <!-- Step 2 -->
                        <div
                            class="step-item"
                            :class="{
                                'active': currentStep === 2,
                                'completed': currentStep > 2,
                                'pending': currentStep < 2
                            }"
                            @click="currentStep > 2 && goToStep(2)"
                        >
                            <div class="step-number">
                                <template x-if="currentStep > 2">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= 2">
                                    <span>2</span>
                                </template>
                            </div>
                            <span class="step-label">Join Method</span>
                        </div>

                        <div class="step-connector" :class="{ 'completed': currentStep > 2 }"></div>

                        <!-- Step 3 -->
                        <div
                            class="step-item"
                            :class="{
                                'active': currentStep === 3,
                                'completed': currentStep > 3,
                                'pending': currentStep < 3
                            }"
                        >
                            <div class="step-number">
                                <span>3</span>
                            </div>
                            <span class="step-label">Enter Details</span>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div x-show="error" x-cloak class="alert alert-error">
                        <span x-text="error"></span>
                    </div>

                    <!-- Success Alert -->
                    <div x-show="success" x-cloak class="alert alert-success">
                        <span x-text="success"></span>
                    </div>

                    <!-- =====================================================
                         STEP 1: SELECT GAME
                         ===================================================== -->
                    <div x-show="currentStep === 1" class="step-section">
                        <div class="step-header">
                            <h3>Select a Game</h3>
                        </div>
                        <p class="step-subtitle">Choose the game you want to create a lobby for. Games you own are marked with a badge.</p>

                        <div class="games-grid">
                            @forelse($combinedGames as $game)
                                <x-lobby-game-card :game="$game" />
                            @empty
                                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #71717a;">
                                    <p>No games with lobby support found. Connect your Steam account to see your games.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- =====================================================
                         STEP 2: SELECT JOIN METHOD
                         ===================================================== -->
                    <div x-show="currentStep === 2" x-cloak class="step-section">
                        <button type="button" class="back-button" @click="goBack()">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Back to Games
                        </button>

                        <!-- Selected Game Summary -->
                        <div class="selected-summary" style="margin-top: 16px;">
                            <div class="selected-game-banner">
                                <img :src="selectedGameImg" :alt="selectedGameName">
                            </div>
                            <div class="selected-info">
                                <h4 x-text="selectedGameName"></h4>
                                <p>
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Game Selected
                                </p>
                            </div>
                            <a class="change-link" @click="goToStep(1)">Change</a>
                        </div>

                        <div class="step-header">
                            <h3>Choose Join Method</h3>
                        </div>
                        <p class="step-subtitle">Select how other players will join your lobby.</p>

                        <!-- Loading State -->
                        <div x-show="loading" x-cloak style="text-align: center; padding: 40px;">
                            <div class="loading-spinner"></div>
                            <p style="color: #71717a; margin-top: 12px;">Loading join methods...</p>
                        </div>

                        <!-- Join Method Cards Grid -->
                        <div x-show="!loading && availableJoinMethods.length > 0" class="methods-grid">
                            <template x-for="method in availableJoinMethods" :key="method.join_method">
                                <div
                                    class="method-card"
                                    :class="{ 'selected': selectedJoinMethod === method.join_method }"
                                    @click="selectJoinMethod(method)"
                                >
                                    <div class="method-card-icon" x-html="getMethodIcon(method.join_method)"></div>
                                    <div class="method-card-name" x-text="method.display_name"></div>
                                    <div class="method-card-desc" x-text="getMethodDescription(method.join_method)"></div>
                                </div>
                            </template>
                        </div>

                        <!-- No Methods Available -->
                        <div x-show="!loading && availableJoinMethods.length === 0" x-cloak style="text-align: center; padding: 40px; color: #71717a;">
                            <p>No join methods available for this game.</p>
                            <button type="button" class="btn btn-ghost" style="margin-top: 12px;" @click="goToStep(1)">Select a Different Game</button>
                        </div>
                    </div>

                    <!-- =====================================================
                         STEP 3: ENTER DETAILS
                         ===================================================== -->
                    <div x-show="currentStep === 3" x-cloak class="step-section">
                        <button type="button" class="back-button" @click="goBack()">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                            </svg>
                            Back to Join Methods
                        </button>

                        <!-- Selected Summary -->
                        <div class="selected-summary" style="margin-top: 16px;">
                            <div class="selected-game-banner">
                                <img :src="selectedGameImg" :alt="selectedGameName">
                            </div>
                            <div class="selected-info">
                                <h4 x-text="selectedGameName"></h4>
                                <p>
                                    <span x-text="selectedMethodData?.display_name || formatJoinMethod(selectedJoinMethod)"></span>
                                </p>
                            </div>
                            <a class="change-link" @click="goToStep(1)">Start Over</a>
                        </div>

                        <div class="step-header" style="margin-top: 24px;">
                            <h3>Enter Details</h3>
                        </div>
                        <p class="step-subtitle">Provide the information needed for players to join your lobby.</p>

                        <!-- Steam Lobby Link -->
                        <div x-show="selectedJoinMethod === 'steam_lobby'" x-cloak class="form-group">
                            <label class="form-label">Steam Lobby Link</label>
                            <input
                                type="text"
                                x-model="formData.steam_lobby_link"
                                placeholder="steam://joinlobby/..."
                                :disabled="loading"
                                class="form-control"
                            >
                            <p class="form-help">Get your lobby link from Steam overlay (Shift+Tab) > View Players > Right-click lobby > Copy Lobby ID</p>
                        </div>

                        <!-- Steam Connect / Server Address -->
                        <div x-show="selectedJoinMethod === 'steam_connect' || selectedJoinMethod === 'server_address'" x-cloak>
                            <div class="form-group">
                                <label class="form-label">Server IP or Domain</label>
                                <input
                                    type="text"
                                    x-model="formData.server_ip"
                                    placeholder="e.g., 192.168.1.1 or play.server.com"
                                    :disabled="loading"
                                    class="form-control"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">Port (Optional)</label>
                                <input
                                    type="number"
                                    x-model="formData.server_port"
                                    placeholder="e.g., 27015"
                                    :disabled="loading"
                                    min="1"
                                    max="65535"
                                    class="form-control"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">Server Password (Optional)</label>
                                <input
                                    type="password"
                                    x-model="formData.server_password"
                                    placeholder="Leave empty if no password"
                                    :disabled="loading"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <!-- Lobby Code -->
                        <div x-show="selectedJoinMethod === 'lobby_code'" x-cloak class="form-group">
                            <label class="form-label">Lobby/Party Code</label>
                            <input
                                type="text"
                                x-model="formData.lobby_code"
                                placeholder="e.g., AB12CD"
                                :disabled="loading"
                                maxlength="50"
                                class="form-control"
                                style="text-transform: uppercase;"
                            >
                            <p class="form-help">Enter the party code from your game</p>
                        </div>

                        <!-- Join Command -->
                        <div x-show="selectedJoinMethod === 'join_command'" x-cloak class="form-group">
                            <label class="form-label">Join Command</label>
                            <input
                                type="text"
                                x-model="formData.join_command"
                                placeholder="e.g., /join player123"
                                :disabled="loading"
                                class="form-control"
                            >
                            <p class="form-help">Enter the in-game command others will use to join</p>
                        </div>

                        <!-- Private Match -->
                        <div x-show="selectedJoinMethod === 'private_match'" x-cloak>
                            <div class="form-group">
                                <label class="form-label">Match/Room Name</label>
                                <input
                                    type="text"
                                    x-model="formData.match_name"
                                    placeholder="Your match or room name"
                                    :disabled="loading"
                                    class="form-control"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label">Match Password (Optional)</label>
                                <input
                                    type="password"
                                    x-model="formData.match_password"
                                    placeholder="Leave empty if no password"
                                    :disabled="loading"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <!-- Manual Invite -->
                        <div x-show="selectedJoinMethod === 'manual_invite'" x-cloak class="form-group">
                            <label class="form-label">Join Instructions</label>
                            <textarea
                                x-model="formData.manual_instructions"
                                placeholder="Enter instructions for joining (e.g., 'Add me on Steam and I'll invite you')"
                                :disabled="loading"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group" style="margin-top: 24px; display: flex; gap: 12px;">
                            <button
                                type="button"
                                @click="saveLobby()"
                                :disabled="loading || !canSave()"
                                class="btn btn-primary"
                                style="flex: 1;"
                            >
                                <span x-show="!loading">Create Lobby</span>
                                <span x-show="loading" x-cloak>
                                    <span class="loading-spinner"></span>
                                    Creating...
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="resetForm()"
                                :disabled="loading"
                                class="btn btn-ghost"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Initial State (Step 1 visible by default) -->
                    <template x-if="currentStep === 1 && games.length === 0">
                        <div style="text-align: center; padding: 40px 20px; color: #71717a;">
                            <svg width="48" height="48" fill="currentColor" viewBox="0 0 20 20" style="margin: 0 auto 16px; opacity: 0.5;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            <p>No games available. Connect your Steam account to get started.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
