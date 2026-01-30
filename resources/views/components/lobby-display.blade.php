@props(['user', 'isOwnProfile' => false])

{{--
    Lobby Display Component (Display-Only)

    A simplified component for displaying user lobbies on profile pages.
    Does NOT include lobby creation functionality - users must go to /lobbies to create lobbies.

    Props:
    - user: The User model whose lobbies to display
    - isOwnProfile: Boolean indicating if viewing own profile (enables delete + create link)

    API Endpoint: GET /api/users/{user}/lobbies
    Response format: { success: true, data: [lobby objects], has_active_lobby: bool }
--}}

<div
    x-data="lobbyDisplay({{ $user->id }}, {{ json_encode($isOwnProfile) }})"
    x-init="init()"
    class="lobby-display"
>
    {{-- Header Section --}}
    <div class="card">
        <div class="lobby-header-row">
            <h3 class="card-header lobby-title">
                @if($isOwnProfile)
                    Your Active Lobbies
                @else
                    {{ $user->display_name }}'s Lobbies
                @endif
            </h3>

            @if($isOwnProfile)
                <a
                    href="{{ route('lobbies.index') }}"
                    class="btn btn-primary btn-sm"
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 13px;"
                >
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>Create Lobby</span>
                </a>
            @endif
        </div>

        {{-- Loading State --}}
        <div x-show="loading && activeLobbies.length === 0" x-cloak class="lobby-loading-state">
            <div class="lobby-spinner"></div>
            <p class="lobby-loading-text">Loading lobbies...</p>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && activeLobbies.length === 0" x-cloak class="lobby-empty-state">
            <div class="lobby-empty-icon">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
            </div>
            <p class="lobby-empty-text">
                @if($isOwnProfile)
                    No active lobbies yet
                @else
                    {{ $user->display_name }} has no active lobbies
                @endif
            </p>
            @if($isOwnProfile)
                <a href="{{ route('lobbies.index') }}" class="lobby-create-link">
                    <span>Create a lobby to get started</span>
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            @else
                <p class="lobby-check-back-text">
                    Check back later to join their games
                </p>
            @endif
        </div>

        {{-- Active Lobbies Grid --}}
        <div
            x-show="activeLobbies.length > 0"
            x-cloak
            class="lobby-grid"
        >
            <template x-for="lobby in activeLobbies" :key="lobby.id">
                <div
                    class="lobby-card"
                    @mouseenter="$el.classList.add('lobby-card-hover')"
                    @mouseleave="$el.classList.remove('lobby-card-hover')"
                >
                    {{-- Game Banner Image --}}
                    <div class="lobby-card-banner">
                        <img
                            :src="'https://cdn.cloudflare.steamstatic.com/steam/apps/' + lobby.game_id + '/header.jpg'"
                            :alt="lobby.game_name || 'Game'"
                            class="lobby-card-banner-img"
                            onerror="this.src='https://cdn.cloudflare.steamstatic.com/steam/apps/730/header.jpg'"
                        >
                        {{-- Gradient Overlay --}}
                        <div class="lobby-card-banner-gradient"></div>

                        {{-- Delete Button (Own Profile Only) --}}
                        @if($isOwnProfile)
                            <button
                                @click.stop="deleteLobby(lobby.id)"
                                type="button"
                                class="lobby-delete-btn"
                                title="Delete lobby"
                            >
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        @endif

                        {{-- Expiration Badge --}}
                        <div class="lobby-expiry-badge" :class="getExpiryBadgeClass(lobby)">
                            <template x-if="lobby.is_persistent || !lobby.expires_at">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Persistent
                                </span>
                            </template>
                            <template x-if="!lobby.is_persistent && lobby.expires_at">
                                <span x-text="formatTimeRemaining(lobby.expires_at)"></span>
                            </template>
                        </div>
                    </div>

                    {{-- Card Content --}}
                    <div class="lobby-card-content">
                        {{-- Game Name and Join Method --}}
                        <div style="margin-bottom: 12px;">
                            <h4 class="lobby-game-name" x-text="lobby.game_name || 'Unknown Game'"></h4>
                            <p class="lobby-join-method" x-text="lobby.display_format || formatJoinMethod(lobby.join_method)"></p>
                        </div>

                        {{-- Join Information Display --}}
                        <div class="lobby-join-info">
                            <span class="lobby-join-text" x-text="lobby.join_link || 'No join info'"></span>

                            {{-- Copy Button --}}
                            <button
                                @click.stop="copyToClipboard(lobby.join_link, lobby.id)"
                                type="button"
                                class="lobby-copy-btn"
                                :class="copiedLobbyId === lobby.id ? 'lobby-copy-btn-success' : ''"
                                :title="copiedLobbyId === lobby.id ? 'Copied!' : 'Copy to clipboard'"
                            >
                                <svg x-show="copiedLobbyId !== lobby.id" width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                </svg>
                                <svg x-show="copiedLobbyId === lobby.id" x-cloak width="12" height="12" fill="currentColor" viewBox="0 0 20 20" style="color: #23a559;">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Action Buttons --}}
                        <div style="margin-top: 12px;">
                            {{-- Join Button (for steam_lobby and steam_connect only) --}}
                            <template x-if="lobby.join_method === 'steam_lobby' || lobby.join_method === 'steam_connect'">
                                <a
                                    :href="lobby.join_link"
                                    target="_blank"
                                    class="lobby-join-btn"
                                >
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                        <path d="M9.5 16.5l7-4.5-7-4.5v9z"/>
                                    </svg>
                                    <span>Join via Steam</span>
                                </a>
                            </template>

                            {{-- Info Text (for other join methods) --}}
                            <template x-if="lobby.join_method !== 'steam_lobby' && lobby.join_method !== 'steam_connect'">
                                <div class="lobby-info-text">
                                    <span x-show="lobby.join_method === 'lobby_code'">Copy the code above and paste it in-game</span>
                                    <span x-show="lobby.join_method === 'server_address'">Copy the address above and add to your server list</span>
                                    <span x-show="lobby.join_method === 'join_command'">Copy the command above and run it in-game</span>
                                    <span x-show="lobby.join_method === 'private_match'">Use the match details above to join</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Component Styles --}}
<style>
/* Spinner animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.lobby-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--color-border-primary, #3f3f46);
    border-top-color: var(--accent-primary, #667eea);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

[x-cloak] { display: none !important; }

/* Header Row */
.lobby-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.lobby-title {
    margin-bottom: 0 !important;
}

/* Loading State */
.lobby-loading-state {
    padding: 40px;
    text-align: center;
}

.lobby-loading-text {
    color: var(--color-text-muted, #71717a);
    margin-top: 12px;
}

/* Empty State */
.lobby-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px;
    background-color: var(--color-bg-primary, #0e0e10);
    border-radius: 12px;
    border: 2px dashed var(--color-border-primary, #3f3f46);
}

.lobby-empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    color: var(--color-text-muted, #71717a);
}

.lobby-empty-text {
    color: var(--color-text-muted, #71717a);
    font-size: 15px;
    margin: 0 0 12px 0;
}

.lobby-create-link {
    color: var(--accent-primary, #667eea);
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color 0.2s ease;
}

.lobby-create-link:hover {
    text-decoration: underline;
    color: var(--accent-secondary, #818cf8);
}

.lobby-check-back-text {
    color: var(--color-text-secondary, #b3b3b5);
    font-size: 14px;
    margin: 0;
}

/* Lobby Grid */
.lobby-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

/* Lobby Card */
.lobby-card {
    background-color: var(--color-bg-primary, #111214);
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    transition: all 0.25s ease;
    border: 1px solid var(--color-border-secondary, transparent);
}

.lobby-card-hover {
    background-color: var(--color-bg-secondary, #1a1b1e);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

/* Card Banner */
.lobby-card-banner {
    position: relative;
    width: 100%;
    height: 80px;
    overflow: hidden;
    background-color: var(--color-bg-elevated, #2b2d31);
}

.lobby-card-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.7;
}

.lobby-card-banner-gradient {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40px;
    background: linear-gradient(to top, var(--color-bg-primary, #111214) 0%, transparent 100%);
}

/* Delete Button */
.lobby-delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background-color: rgba(0, 0, 0, 0.6);
    color: #ef4444;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    backdrop-filter: blur(4px);
}

.lobby-delete-btn:hover {
    background-color: #ef4444;
    color: #fff;
}

/* Expiry Badge */
.lobby-expiry-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    backdrop-filter: blur(4px);
}

.lobby-expiry-badge-green {
    background-color: rgba(35, 165, 89, 0.9);
    color: #fff;
}

.lobby-expiry-badge-red {
    background-color: rgba(239, 68, 68, 0.9);
    color: #fff;
}

.lobby-expiry-badge-gray {
    background-color: rgba(113, 113, 122, 0.9);
    color: #fff;
}

/* Card Content */
.lobby-card-content {
    padding: 14px 18px 18px 18px;
}

.lobby-game-name {
    margin: 0;
    color: var(--color-text-primary, #f2f3f5);
    font-weight: 600;
    font-size: 15px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lobby-join-method {
    margin: 4px 0 0 0;
    color: var(--color-text-muted, #b5bac1);
    font-size: 13px;
}

/* Join Info Box */
.lobby-join-info {
    background-color: var(--color-bg-secondary, #1e1f22);
    padding: 10px 12px;
    border-radius: 10px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 11px;
    color: var(--color-text-secondary, #dbdee1);
    word-break: break-all;
    position: relative;
    padding-right: 36px;
    border: 1px solid var(--color-border-secondary, transparent);
}

.lobby-join-text {
    display: block;
    line-height: 1.4;
}

/* Copy Button */
.lobby-copy-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 26px;
    height: 26px;
    border-radius: 4px;
    background-color: var(--color-bg-elevated, #2b2d31);
    color: var(--color-text-muted, #b5bac1);
    border: 1px solid var(--color-border-secondary, transparent);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.lobby-copy-btn:hover {
    background-color: var(--color-surface-hover, #4e5058);
    color: var(--color-text-primary, #fff);
}

.lobby-copy-btn-success {
    background-color: #23a559 !important;
    color: #fff !important;
}

/* Join Button */
.lobby-join-btn {
    width: 100%;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background-color: #23a559;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.17s ease;
}

.lobby-join-btn:hover {
    background-color: #1a8a47;
}

/* Info Text */
.lobby-info-text {
    background-color: var(--color-bg-secondary, #1e1f22);
    padding: 10px 12px;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
    color: var(--color-text-muted, #b5bac1);
    border: 1px solid var(--color-border-secondary, transparent);
}

/* Mobile Responsive Styles */
@media (max-width: 640px) {
    .lobby-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .lobby-card-content {
        padding: 10px 14px 14px 14px;
    }

    .lobby-game-name {
        font-size: 15px;
    }

    .lobby-join-btn {
        padding: 12px 16px;
        font-size: 15px;
    }
}

/* Touch-friendly hover states (disable on touch devices) */
@media (hover: none) {
    .lobby-card-hover {
        background-color: var(--color-bg-primary, #111214) !important;
    }
}

/* Toast animation styles */
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

/* =============================================================================
   Light Theme Overrides for Lobby Display
   ============================================================================= */

[data-theme="light"] .lobby-empty-state {
    background-color: var(--color-surface);
    border-color: var(--color-border-primary);
}

[data-theme="light"] .lobby-card {
    background-color: var(--color-surface) !important;
    border-color: var(--color-border-secondary) !important;
}

[data-theme="light"] .lobby-card-hover {
    background-color: var(--color-surface-hover) !important;
}

[data-theme="light"] .lobby-card-banner {
    background-color: var(--color-bg-tertiary) !important;
}

[data-theme="light"] .lobby-card-banner-gradient {
    background: linear-gradient(to top, var(--color-surface) 0%, transparent 100%) !important;
}

[data-theme="light"] .lobby-join-info {
    background-color: var(--color-bg-tertiary) !important;
}

[data-theme="light"] .lobby-info-text {
    background-color: var(--color-bg-tertiary) !important;
}
</style>

{{-- Alpine.js Component --}}
<script>
window.lobbyDisplay = function(userId, isOwnProfile) {
    return {
        // Component State
        userId: userId,
        isOwnProfile: isOwnProfile,
        activeLobbies: [],
        loading: true,
        copiedLobbyId: null,

        // Timer for real-time countdown updates
        countdownTimer: null,

        /**
         * Initialize component
         */
        init() {
            console.log('[LobbyDisplay] Initializing...', {
                userId: this.userId,
                isOwnProfile: this.isOwnProfile
            });

            this.loadActiveLobbies();

            // Set up real-time countdown updates (every second)
            this.countdownTimer = setInterval(() => {
                // Check for expired lobbies
                this.activeLobbies = this.activeLobbies.filter(lobby => {
                    if (!lobby.expires_at) return true; // Persistent lobbies stay
                    return new Date(lobby.expires_at) > new Date();
                });
            }, 1000);
        },

        /**
         * Load active lobbies for the user
         */
        async loadActiveLobbies() {
            this.loading = true;

            try {
                const response = await fetch(`/api/users/${this.userId}/lobbies`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load lobbies (${response.status})`);
                }

                const data = await response.json();

                if (data.success) {
                    this.activeLobbies = data.data || [];
                    console.log('[LobbyDisplay] Loaded lobbies:', this.activeLobbies.length);
                } else {
                    console.warn('[LobbyDisplay] API returned unsuccessful response:', data);
                    this.activeLobbies = [];
                }
            } catch (error) {
                console.error('[LobbyDisplay] Error loading active lobbies:', error);
                this.activeLobbies = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Delete a lobby (own profile only)
         */
        async deleteLobby(lobbyId) {
            if (!this.isOwnProfile) {
                console.warn('[LobbyDisplay] Cannot delete - not own profile');
                return;
            }

            if (!confirm('Are you sure you want to delete this lobby?')) {
                return;
            }

            try {
                const response = await fetch(`/api/lobbies/${lobbyId}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to delete lobby');
                }

                // Remove from local array immediately for instant UI feedback
                this.activeLobbies = this.activeLobbies.filter(lobby => lobby.id !== lobbyId);

                this.showToast('Lobby deleted successfully', 'success');
                console.log('[LobbyDisplay] Lobby deleted:', lobbyId);

            } catch (error) {
                console.error('[LobbyDisplay] Error deleting lobby:', error);
                this.showToast('Failed to delete lobby. Please try again.', 'error');
            }
        },

        /**
         * Format time remaining for display
         */
        formatTimeRemaining(expiresAt) {
            if (!expiresAt) {
                return 'Persistent';
            }

            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            if (diff <= 0) {
                return 'Expired';
            }

            const totalMinutes = Math.floor(diff / 60000);
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                return `${minutes}m`;
            } else {
                const seconds = Math.floor((diff % 60000) / 1000);
                return `${seconds}s`;
            }
        },

        /**
         * Format join method for display (fallback if display_format not available)
         */
        formatJoinMethod(joinMethod) {
            const labels = {
                steam_lobby: 'Steam Lobby',
                steam_connect: 'Steam Connect',
                lobby_code: 'Lobby Code',
                server_address: 'Server Address',
                join_command: 'Join Command',
                private_match: 'Private Match',
            };
            return labels[joinMethod] || joinMethod || 'Unknown';
        },

        /**
         * Get time remaining in minutes for badge color logic
         */
        getTimeRemainingMinutes(expiresAt) {
            if (!expiresAt) {
                return null;
            }

            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            return Math.floor(diff / 60000);
        },

        /**
         * Get expiry badge CSS class based on time remaining
         */
        getExpiryBadgeClass(lobby) {
            if (lobby.is_persistent || !lobby.expires_at) {
                return 'lobby-expiry-badge-green';
            }

            const minutes = this.getTimeRemainingMinutes(lobby.expires_at);

            if (minutes === null || minutes <= 0) {
                return 'lobby-expiry-badge-red';
            } else if (minutes <= 5) {
                return 'lobby-expiry-badge-red';
            } else {
                return 'lobby-expiry-badge-gray';
            }
        },

        /**
         * Copy text to clipboard
         */
        async copyToClipboard(text, lobbyId) {
            if (!text) {
                this.showToast('Nothing to copy', 'warning');
                return;
            }

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }

                // Show "Copied!" feedback
                this.copiedLobbyId = lobbyId;
                setTimeout(() => {
                    this.copiedLobbyId = null;
                }, 2000);

                this.showToast('Copied to clipboard!', 'success');

            } catch (error) {
                console.error('[LobbyDisplay] Failed to copy:', error);
                this.showToast('Failed to copy. Please copy manually.', 'error');
            }
        },

        /**
         * Show toast notification
         */
        showToast(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#667eea'
            };

            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                max-width: 400px;
                font-size: 14px;
            `;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };
};
</script>
