/**
 * Lobby Join Button Component (Alpine.js)
 *
 * Provides reactive UI for joining game lobbies from various locations
 * in the app (server member lists, friends list, team member lists).
 *
 * Features:
 * - Real-time lobby status updates via Laravel Echo
 * - Auto-refresh every 30 seconds
 * - Countdown timer with minute-by-minute updates
 * - Multiple join methods (Steam, lobby codes, server addresses)
 * - Clipboard integration
 * - Toast notifications
 * - Graceful error handling
 * - Analytics tracking (optional)
 *
 * @author Glyph Development Team
 * @version 1.0.0
 */

/**
 * Alpine.js component factory for lobby join buttons
 *
 * @param {Object} config - Component configuration
 * @param {number} config.userId - User ID to display lobbies for
 * @param {Array} config.initialLobbies - Initial lobby data array
 * @returns {Object} Alpine.js reactive component data
 */
const lobbyJoinButtonComponent = function(config) {
    return {
        // Component Configuration
        userId: config.userId,
        lobbies: config.initialLobbies || [],

        // UI State
        showLobbyTooltip: false,

        // Timers
        updateInterval: null,
        refreshInterval: null,

        // Echo Channel
        echoChannel: null,

        /**
         * Initialize component
         * Sets up timers and subscribes to real-time events
         */
        init() {
            console.log('[LobbyJoinButton] Initializing for user:', this.userId, 'Lobbies:', this.lobbies.length);

            // Process initial lobbies data
            this.processLobbiesData(this.lobbies);

            // Start countdown timer (updates every minute)
            this.startTimerUpdates();

            // Subscribe to real-time lobby events via Echo
            this.subscribeToLobbyEvents();

            // Auto-refresh lobby data every 30 seconds
            this.refreshInterval = setInterval(() => {
                this.refreshLobbies();
            }, 30000);

            console.log('[LobbyJoinButton] Initialization complete');
        },

        /**
         * Process lobbies data to add computed properties
         * @param {Array} lobbies - Array of lobby objects
         */
        processLobbiesData(lobbies) {
            this.lobbies = lobbies.map(lobby => ({
                ...lobby,
                is_expired: this.checkIfExpired(lobby),
                is_expiring_soon: this.checkIfExpiringSoon(lobby),
            }));
        },

        /**
         * Check if lobby has expired
         * @param {Object} lobby - Lobby object
         * @returns {boolean}
         */
        checkIfExpired(lobby) {
            if (!lobby.expires_at) return false;

            const expiresAt = new Date(lobby.expires_at);
            const now = new Date();
            return expiresAt <= now;
        },

        /**
         * Check if lobby is expiring soon (< 5 minutes)
         * @param {Object} lobby - Lobby object
         * @returns {boolean}
         */
        checkIfExpiringSoon(lobby) {
            if (!lobby.time_remaining_minutes) return false;
            return lobby.time_remaining_minutes < 5;
        },

        /**
         * Handle lobby join click
         * Copies link to clipboard and attempts to open Steam protocol
         * @param {Object} lobby - Lobby object
         */
        async joinLobby(lobby) {
            console.log('[LobbyJoinButton] Join lobby clicked:', lobby);

            // Check if lobby has expired
            if (lobby.is_expired || this.checkIfExpired(lobby)) {
                this.showToast('This lobby has expired', 'error');
                // Refresh lobbies to update state
                await this.refreshLobbies();
                return;
            }

            const joinLink = lobby.join_link;

            if (!joinLink) {
                console.error('[LobbyJoinButton] No join link available:', lobby);
                this.showToast('Unable to generate join link', 'error');
                return;
            }

            // Track analytics (if available)
            this.trackLobbyJoin(lobby);

            // Copy to clipboard
            try {
                const copied = await this.copyToClipboard(joinLink);

                if (!copied) {
                    console.warn('[LobbyJoinButton] Clipboard copy failed');
                    this.showToast('Failed to copy link', 'warning');
                }
            } catch (err) {
                console.error('[LobbyJoinButton] Clipboard error:', err);
                // Continue anyway, user might still be able to use the protocol link
            }

            // Handle different join methods
            switch (lobby.join_method) {
                case 'steam_lobby':
                case 'steam_connect':
                    // Attempt to open Steam protocol
                    try {
                        window.location.href = joinLink;
                        this.showToast(
                            `Opening Steam for ${lobby.game_name}... If nothing happens, paste the copied link manually`,
                            'info',
                            5000
                        );
                    } catch (err) {
                        console.error('[LobbyJoinButton] Failed to open Steam protocol:', err);
                        this.showToast('Link copied! Paste it in Steam to join', 'success');
                    }
                    break;

                case 'lobby_code':
                    this.showToast(`Lobby code copied! Paste it in ${lobby.game_name} to join`, 'success');
                    break;

                case 'server_address':
                    this.showToast('Server address copied! Use it to connect', 'success');
                    break;

                case 'join_command':
                    this.showToast('Command copied! Paste it in game console', 'success');
                    break;

                case 'private_match':
                    this.showToast('Match info copied! Use it to join', 'success');
                    break;

                default:
                    this.showToast('Link copied to clipboard', 'success');
            }
        },

        /**
         * Copy text to clipboard using modern API with legacy fallback
         * @param {string} text - Text to copy
         * @returns {Promise<boolean>} - True if successful
         */
        async copyToClipboard(text) {
            // Use global clipboard utility if available
            if (window.clipboardUtils && window.clipboardUtils.copy) {
                try {
                    return await window.clipboardUtils.copy(text);
                } catch (err) {
                    console.error('[LobbyJoinButton] Clipboard utils failed:', err);
                }
            }

            // Fallback to navigator.clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (err) {
                    console.error('[LobbyJoinButton] Navigator clipboard failed:', err);
                    return false;
                }
            }

            // Last resort: legacy method
            return this.copyToClipboardLegacy(text);
        },

        /**
         * Legacy clipboard copy method
         * @param {string} text - Text to copy
         * @returns {boolean} - True if successful
         */
        copyToClipboardLegacy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.left = '-9999px';
            textarea.style.opacity = '0';
            textarea.setAttribute('readonly', '');

            document.body.appendChild(textarea);

            try {
                textarea.select();
                textarea.setSelectionRange(0, text.length);
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);
                return successful;
            } catch (error) {
                console.error('[LobbyJoinButton] Legacy clipboard failed:', error);
                if (document.body.contains(textarea)) {
                    document.body.removeChild(textarea);
                }
                return false;
            }
        },

        /**
         * Refresh lobbies data from API
         * Fetches latest lobby status for the user
         */
        async refreshLobbies() {
            try {
                console.log('[LobbyJoinButton] Refreshing lobbies for user:', this.userId);

                const response = await fetch(`/api/users/${this.userId}/lobbies`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success && Array.isArray(data.data)) {
                    console.log('[LobbyJoinButton] Lobbies refreshed:', data.data.length);
                    this.processLobbiesData(data.data);
                } else {
                    console.warn('[LobbyJoinButton] Invalid response format:', data);
                }
            } catch (error) {
                console.error('[LobbyJoinButton] Failed to refresh lobbies:', error);
                // Don't show error toast on background refresh - fail silently
            }
        },

        /**
         * Start countdown timer updates
         * Updates every minute to decrement time remaining
         */
        startTimerUpdates() {
            // Clear existing interval if any
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }

            // Update every 60 seconds (1 minute)
            this.updateInterval = setInterval(() => {
                console.log('[LobbyJoinButton] Updating timers');

                this.lobbies = this.lobbies.map(lobby => {
                    // Skip if no expiration
                    if (!lobby.time_remaining_minutes && lobby.time_remaining_minutes !== 0) {
                        return lobby;
                    }

                    // Decrement time remaining
                    const newTimeRemaining = Math.max(0, lobby.time_remaining_minutes - 1);

                    return {
                        ...lobby,
                        time_remaining_minutes: newTimeRemaining,
                        is_expired: newTimeRemaining === 0,
                        is_expiring_soon: newTimeRemaining < 5 && newTimeRemaining > 0,
                    };
                });

                // Remove expired lobbies after a grace period
                this.lobbies = this.lobbies.filter(lobby => {
                    if (lobby.is_expired && lobby.time_remaining_minutes === 0) {
                        console.log('[LobbyJoinButton] Removing expired lobby:', lobby.id);
                        return false;
                    }
                    return true;
                });
            }, 60000); // 60 seconds
        },

        /**
         * Subscribe to real-time lobby events via Laravel Echo
         * Listens for lobby created, deleted, and expired events
         */
        subscribeToLobbyEvents() {
            // Check if Echo is available
            if (typeof window.Echo === 'undefined' || !window.Echo) {
                console.warn('[LobbyJoinButton] Laravel Echo not available. Real-time updates disabled.');
                return;
            }

            try {
                console.log('[LobbyJoinButton] Subscribing to lobby events for user:', this.userId);

                // Subscribe to user's private channel
                this.echoChannel = window.Echo.private(`user.${this.userId}`);

                // Listen for lobby created event
                this.echoChannel.listen('.lobby.created', (event) => {
                    console.log('[LobbyJoinButton] Lobby created event:', event);
                    this.handleLobbyCreated(event);
                });

                // Listen for lobby deleted event
                this.echoChannel.listen('.lobby.deleted', (event) => {
                    console.log('[LobbyJoinButton] Lobby deleted event:', event);
                    this.handleLobbyDeleted(event);
                });

                // Listen for lobby expired event
                this.echoChannel.listen('.lobby.expired', (event) => {
                    console.log('[LobbyJoinButton] Lobby expired event:', event);
                    this.handleLobbyExpired(event);
                });

                console.log('[LobbyJoinButton] Successfully subscribed to lobby events');
            } catch (error) {
                console.error('[LobbyJoinButton] Failed to subscribe to Echo events:', error);
            }
        },

        /**
         * Handle lobby created event
         * @param {Object} event - Event data from Laravel Echo
         */
        handleLobbyCreated(event) {
            // Refresh lobbies to get the new lobby with full data
            this.refreshLobbies();
        },

        /**
         * Handle lobby deleted event
         * @param {Object} event - Event data from Laravel Echo
         */
        handleLobbyDeleted(event) {
            const lobbyId = event.lobby_id || event.id;

            if (!lobbyId) {
                console.warn('[LobbyJoinButton] Lobby deleted event missing lobby_id');
                return;
            }

            // Remove lobby from list
            this.lobbies = this.lobbies.filter(lobby => lobby.id !== lobbyId);

            console.log('[LobbyJoinButton] Lobby removed:', lobbyId);
        },

        /**
         * Handle lobby expired event
         * @param {Object} event - Event data from Laravel Echo
         */
        handleLobbyExpired(event) {
            const lobbyId = event.lobby_id || event.id;

            if (!lobbyId) {
                console.warn('[LobbyJoinButton] Lobby expired event missing lobby_id');
                return;
            }

            // Mark lobby as expired
            this.lobbies = this.lobbies.map(lobby => {
                if (lobby.id === lobbyId) {
                    return {
                        ...lobby,
                        is_expired: true,
                        time_remaining_minutes: 0,
                    };
                }
                return lobby;
            });

            console.log('[LobbyJoinButton] Lobby marked as expired:', lobbyId);
        },

        /**
         * Format time remaining for display
         * @param {number|null} minutes - Minutes remaining
         * @returns {string} Formatted time string
         */
        formatTimeRemaining(minutes) {
            if (minutes === null || minutes === undefined) {
                return '';
            }

            if (minutes < 1) {
                return '<1m';
            }

            if (minutes >= 60) {
                const hours = Math.floor(minutes / 60);
                const remainingMinutes = minutes % 60;
                return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}m` : `${hours}h`;
            }

            return `${minutes}m`;
        },

        /**
         * Show toast notification
         * Uses global toast system if available, falls back to alert
         * @param {string} message - Notification message
         * @param {string} type - Notification type (success, info, warning, error)
         * @param {number} duration - Duration in milliseconds (default: 3000)
         */
        showToast(message, type = 'info', duration = 3000) {
            console.log(`[LobbyJoinButton] Toast [${type}]:`, message);

            // Use global toast notification system if available
            if (window.showGlobalToast && typeof window.showGlobalToast === 'function') {
                window.showGlobalToast(message, type, duration);
                return;
            }

            // Fallback: Create simple toast notification
            this.createSimpleToast(message, type, duration);
        },

        /**
         * Create simple toast notification (fallback)
         * @param {string} message - Notification message
         * @param {string} type - Notification type
         * @param {number} duration - Duration in milliseconds
         */
        createSimpleToast(message, type, duration) {
            const toast = document.createElement('div');
            toast.className = `lobby-toast lobby-toast-${type}`;
            toast.textContent = message;

            // Add to body
            document.body.appendChild(toast);

            // Trigger animation
            setTimeout(() => {
                toast.classList.add('lobby-toast-show');
            }, 10);

            // Remove after duration
            setTimeout(() => {
                toast.classList.remove('lobby-toast-show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, duration);
        },

        /**
         * Track lobby join attempt (analytics)
         * Optional - only tracks if analytics are available
         * @param {Object} lobby - Lobby object
         */
        trackLobbyJoin(lobby) {
            // Google Analytics
            if (window.gtag && typeof window.gtag === 'function') {
                try {
                    window.gtag('event', 'lobby_join_attempt', {
                        'game_name': lobby.game_name,
                        'join_method': lobby.join_method,
                        'time_remaining': lobby.time_remaining_minutes,
                    });
                } catch (err) {
                    console.error('[LobbyJoinButton] Analytics tracking failed:', err);
                }
            }

            // Custom analytics (if implemented)
            if (window.trackEvent && typeof window.trackEvent === 'function') {
                try {
                    window.trackEvent('lobby_join', {
                        lobbyId: lobby.id,
                        gameName: lobby.game_name,
                        joinMethod: lobby.join_method,
                    });
                } catch (err) {
                    console.error('[LobbyJoinButton] Custom analytics failed:', err);
                }
            }
        },

        /**
         * Cleanup on component destroy
         * Clears intervals and unsubscribes from Echo
         */
        destroy() {
            console.log('[LobbyJoinButton] Destroying component');

            // Clear intervals
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }

            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }

            // Leave Echo channel
            if (this.echoChannel && window.Echo) {
                try {
                    window.Echo.leave(`user.${this.userId}`);
                    console.log('[LobbyJoinButton] Left Echo channel');
                } catch (err) {
                    console.error('[LobbyJoinButton] Failed to leave Echo channel:', err);
                }
            }
        }
    };
};

// Register as Alpine.js component
if (window.Alpine) {
    window.Alpine.data('lobbyJoinButton', lobbyJoinButtonComponent);
    console.log('[LobbyJoinButton] ✅ Registered as Alpine component');
} else {
    console.error('[LobbyJoinButton] ❌ Alpine.js not found. Component not registered.');
}

// Also expose on window for backward compatibility
window.lobbyJoinButton = lobbyJoinButtonComponent;

// Export for ES6 modules (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = lobbyJoinButtonComponent;
}

console.log('[LobbyJoinButton] Component loaded and ready');
