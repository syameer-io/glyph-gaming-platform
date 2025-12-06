/**
 * Lobby Page - Alpine.js Component
 * Dedicated page for creating and managing game lobbies
 * Phase 2: Visual Game Selection UI with step-by-step flow
 * Phase 3: Browse Lobbies Feed from friends and server members
 */
window.lobbyPage = function(userId, gamesData = [], friendIdsData = []) {
    return {
        // State
        userId: userId,
        games: gamesData, // All available games passed from server
        friendIds: friendIdsData, // Friend IDs for real-time updates
        selectedGame: '',
        selectedGameName: '',
        selectedGameImg: '',
        selectedJoinMethod: '',
        selectedMethodData: null,
        availableJoinMethods: [],
        activeLobbies: [],
        formData: {
            steam_lobby_link: '',
            server_ip: '',
            server_port: '',
            server_password: '',
            lobby_code: '',
            join_command: '',
            match_name: '',
            match_password: '',
            manual_instructions: ''
        },
        loading: false,
        error: '',
        success: '',
        currentStep: 1,
        copiedLobbyId: null,

        // Phase 3: Feed State
        feedLobbies: [],
        feedLoading: false,
        feedFilter: {
            game: '',
            source: 'all'
        },
        copiedFeedLobbyId: null,
        echoChannels: [],

        // Method descriptions for UI
        methodDescriptions: {
            'steam_lobby': 'Share your Steam lobby link for instant join',
            'steam_connect': 'Connect via server IP using console command',
            'server_address': 'Direct server connection with IP address',
            'lobby_code': 'Share a party/lobby code for easy joining',
            'join_command': 'Use an in-game command to join',
            'private_match': 'Set up a private match with room name',
            'manual_invite': 'Provide custom instructions for joining'
        },

        // Initialize
        init() {
            console.log('[LobbyPage] Initializing for user:', this.userId);
            console.log('[LobbyPage] Games available:', this.games.length);
            console.log('[LobbyPage] Friends for real-time:', this.friendIds.length);
            this.loadActiveLobbies();
            this.loadFeed();
            this.setupRealtimeListeners();

            // Set up 1-second interval for countdown updates
            setInterval(() => {
                if (this.$refs.lobbiesList) {
                    const elements = this.$refs.lobbiesList.querySelectorAll('[data-expires]');
                    elements.forEach(el => {
                        const expires = el.dataset.expires;
                        if (expires) {
                            el.textContent = this.formatTimeRemaining(expires);
                        }
                    });
                }
            }, 1000);
        },

        // =====================================================
        // STEP NAVIGATION
        // =====================================================

        /**
         * Select a game and advance to step 2
         */
        selectGame(gameId, gameName, gameImg) {
            console.log('[LobbyPage] Game selected:', gameId, gameName);
            this.selectedGame = gameId;
            this.selectedGameName = gameName;
            this.selectedGameImg = gameImg;
            this.selectedJoinMethod = '';
            this.selectedMethodData = null;
            this.resetFormData();

            // Load join methods and advance to step 2
            this.loadGameJoinMethods().then(() => {
                this.currentStep = 2;
            });
        },

        /**
         * Select a join method and advance to step 3
         */
        selectJoinMethod(method) {
            console.log('[LobbyPage] Join method selected:', method);
            this.selectedJoinMethod = method.join_method;
            this.selectedMethodData = method;
            this.resetFormData();
            this.currentStep = 3;
        },

        /**
         * Navigate to a specific step
         */
        goToStep(step) {
            console.log('[LobbyPage] Going to step:', step);

            // Validate we can go to this step
            if (step === 1) {
                // Always allowed
                this.currentStep = 1;
            } else if (step === 2) {
                // Need a game selected
                if (this.selectedGame) {
                    this.currentStep = 2;
                }
            } else if (step === 3) {
                // Need both game and method selected
                if (this.selectedGame && this.selectedJoinMethod) {
                    this.currentStep = 3;
                }
            }
        },

        /**
         * Go back to previous step
         */
        goBack() {
            if (this.currentStep > 1) {
                this.currentStep--;
                console.log('[LobbyPage] Going back to step:', this.currentStep);
            }
        },

        /**
         * Get SVG icon for a join method
         */
        getMethodIcon(method) {
            const icons = {
                'steam_lobby': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                </svg>`,
                'steam_connect': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M20 13H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-6c0-.55-.45-1-1-1zM7 19c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM20 3H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1zM7 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                </svg>`,
                'server_address': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M19 15v4H5v-4h14m1-2H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1v-6c0-.55-.45-1-1-1zM7 18.5c-.82 0-1.5-.67-1.5-1.5s.68-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM19 5v4H5V5h14m1-2H4c-.55 0-1 .45-1 1v6c0 .55.45 1 1 1h16c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1zM7 8.5c-.82 0-1.5-.67-1.5-1.5S6.18 5.5 7 5.5s1.5.68 1.5 1.5S7.83 8.5 7 8.5z"/>
                </svg>`,
                'lobby_code': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                </svg>`,
                'join_command': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M20 19V7c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zM6 7h12v2H6V7zm0 4h12v2H6v-2zm0 4h8v2H6v-2z"/>
                </svg>`,
                'private_match': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                </svg>`,
                'manual_invite': `<svg viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>`
            };
            return icons[method] || icons['manual_invite'];
        },

        /**
         * Get description for a join method
         */
        getMethodDescription(method) {
            return this.methodDescriptions[method] || 'Custom join method';
        },

        // =====================================================
        // API CALLS
        // =====================================================

        // Load user's active lobbies
        async loadActiveLobbies() {
            try {
                const response = await fetch('/api/lobbies/my-lobbies', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    this.activeLobbies = data.lobbies || [];
                    console.log('[LobbyPage] Loaded', this.activeLobbies.length, 'active lobbies');
                }
            } catch (error) {
                console.error('[LobbyPage] Failed to load lobbies:', error);
            }
        },

        // Load join methods for selected game
        async loadGameJoinMethods() {
            if (!this.selectedGame) {
                this.availableJoinMethods = [];
                this.selectedJoinMethod = '';
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                console.log('[LobbyPage] Loading join methods for game:', this.selectedGame);
                const response = await fetch(`/api/games/${this.selectedGame}/join-methods`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (data.success && Array.isArray(data.join_methods)) {
                    this.availableJoinMethods = data.join_methods;
                    this.selectedJoinMethod = '';
                    this.selectedMethodData = null;
                    console.log('[LobbyPage] Loaded', this.availableJoinMethods.length, 'join methods');
                } else {
                    this.error = 'No join methods available for this game';
                    this.availableJoinMethods = [];
                }
            } catch (error) {
                console.error('[LobbyPage] Failed to load join methods:', error);
                this.error = 'Failed to load join methods';
                this.availableJoinMethods = [];
            } finally {
                this.loading = false;
            }
        },

        // Create lobby
        async saveLobby() {
            if (!this.canSave()) return;

            this.loading = true;
            this.error = '';
            this.success = '';

            try {
                const payload = {
                    game_id: parseInt(this.selectedGame),
                    join_method: this.selectedJoinMethod,
                    ...this.getRelevantFormData()
                };

                console.log('[LobbyPage] Creating lobby with payload:', payload);

                const response = await fetch('/api/lobbies', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    this.success = 'Lobby created successfully!';
                    this.resetForm();
                    await this.loadActiveLobbies();
                    setTimeout(() => this.success = '', 3000);
                } else {
                    this.error = data.message || 'Failed to create lobby';
                }
            } catch (error) {
                console.error('[LobbyPage] Failed to create lobby:', error);
                this.error = 'Failed to create lobby. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        // Delete lobby
        async deleteLobby(lobbyId) {
            if (!confirm('Are you sure you want to delete this lobby?')) return;

            this.loading = true;
            try {
                const response = await fetch(`/api/lobbies/${lobbyId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadActiveLobbies();
                    this.success = 'Lobby deleted successfully';
                    setTimeout(() => this.success = '', 3000);
                } else {
                    this.error = data.message || 'Failed to delete lobby';
                }
            } catch (error) {
                console.error('[LobbyPage] Failed to delete lobby:', error);
                this.error = 'Failed to delete lobby';
            } finally {
                this.loading = false;
            }
        },

        // =====================================================
        // FORM HELPERS
        // =====================================================

        // Get form data relevant to selected join method
        getRelevantFormData() {
            const data = {};
            switch (this.selectedJoinMethod) {
                case 'steam_lobby':
                    data.steam_lobby_link = this.formData.steam_lobby_link;
                    break;
                case 'steam_connect':
                case 'server_address':
                    data.server_ip = this.formData.server_ip;
                    data.server_port = this.formData.server_port;
                    if (this.formData.server_password) {
                        data.server_password = this.formData.server_password;
                    }
                    break;
                case 'lobby_code':
                    data.lobby_code = this.formData.lobby_code;
                    break;
                case 'join_command':
                    data.join_command = this.formData.join_command;
                    break;
                case 'private_match':
                    data.match_name = this.formData.match_name;
                    if (this.formData.match_password) {
                        data.match_password = this.formData.match_password;
                    }
                    break;
                case 'manual_invite':
                    data.manual_instructions = this.formData.manual_instructions;
                    break;
            }
            return data;
        },

        // Check if form can be saved
        canSave() {
            if (!this.selectedGame || !this.selectedJoinMethod) return false;

            switch (this.selectedJoinMethod) {
                case 'steam_lobby':
                    return !!this.formData.steam_lobby_link;
                case 'steam_connect':
                case 'server_address':
                    return !!this.formData.server_ip;
                case 'lobby_code':
                    return !!this.formData.lobby_code;
                case 'join_command':
                    return !!this.formData.join_command;
                case 'private_match':
                    return !!this.formData.match_name;
                case 'manual_invite':
                    return !!this.formData.manual_instructions;
                default:
                    return false;
            }
        },

        // Reset form
        resetForm() {
            this.selectedGame = '';
            this.selectedGameName = '';
            this.selectedGameImg = '';
            this.selectedJoinMethod = '';
            this.selectedMethodData = null;
            this.availableJoinMethods = [];
            this.resetFormData();
            this.currentStep = 1;
            this.error = '';
        },

        // Reset form data only
        resetFormData() {
            this.formData = {
                steam_lobby_link: '',
                server_ip: '',
                server_port: '',
                server_password: '',
                lobby_code: '',
                join_command: '',
                match_name: '',
                match_password: '',
                manual_instructions: ''
            };
        },

        // =====================================================
        // DISPLAY HELPERS
        // =====================================================

        // Format join method for display
        formatJoinMethod(method) {
            const labels = {
                'steam_lobby': 'Steam Lobby',
                'steam_connect': 'Server IP',
                'server_address': 'Server Address',
                'lobby_code': 'Lobby Code',
                'join_command': 'Join Command',
                'private_match': 'Private Match',
                'manual_invite': 'Manual Invite'
            };
            return labels[method] || method;
        },

        // Get join info text from lobby
        getJoinInfo(lobby) {
            switch (lobby.join_method) {
                case 'steam_lobby':
                    // Construct Steam lobby URL from stored data
                    if (lobby.steam_lobby_id && lobby.steam_app_id) {
                        return `steam://joinlobby/${lobby.steam_app_id}/${lobby.steam_lobby_id}/${lobby.steam_profile_id || ''}`;
                    }
                    return lobby.steam_lobby_link || 'Steam Lobby';
                case 'steam_connect':
                    return lobby.server_ip + (lobby.server_port ? ':' + lobby.server_port : '');
                case 'server_address':
                    return lobby.server_ip + (lobby.server_port ? ':' + lobby.server_port : '');
                case 'lobby_code':
                    return lobby.lobby_code || 'Code';
                case 'join_command':
                    return lobby.join_command || 'Command';
                case 'private_match':
                    return lobby.match_name || 'Private Match';
                case 'manual_invite':
                    return lobby.manual_instructions || 'Manual Invite';
                default:
                    return 'Join';
            }
        },

        // Get time remaining in minutes (for styling)
        getTimeRemainingMinutes(expiresAt) {
            if (!expiresAt) return null;

            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            return Math.floor(diff / 60000);
        },

        // Format time remaining
        formatTimeRemaining(expiresAt) {
            if (!expiresAt) return 'No expiration';

            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            if (diff <= 0) return 'Expired';

            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            const hours = Math.floor(minutes / 60);

            if (hours > 0) {
                return `${hours}h ${minutes % 60}m remaining`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds}s remaining`;
            }
            return `${seconds}s remaining`;
        },

        // Copy to clipboard
        async copyToClipboard(text, lobbyId) {
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
                this.copiedLobbyId = lobbyId;
                setTimeout(() => this.copiedLobbyId = null, 2000);
            } catch (error) {
                console.error('[LobbyPage] Failed to copy:', error);
            }
        },

        // =====================================================
        // PHASE 3: FEED METHODS
        // =====================================================

        /**
         * Load lobby feed from friends and server members
         */
        async loadFeed() {
            this.feedLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.feedFilter.game) {
                    params.append('game_id', this.feedFilter.game);
                }
                if (this.feedFilter.source) {
                    params.append('source', this.feedFilter.source);
                }

                const url = `/api/lobbies/feed${params.toString() ? '?' + params.toString() : ''}`;
                console.log('[LobbyPage] Loading feed:', url);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    this.feedLobbies = data.lobbies || [];
                    console.log('[LobbyPage] Loaded', this.feedLobbies.length, 'feed lobbies');
                } else {
                    console.error('[LobbyPage] Feed load failed:', data.message);
                    this.feedLobbies = [];
                }
            } catch (error) {
                console.error('[LobbyPage] Failed to load feed:', error);
                this.feedLobbies = [];
            } finally {
                this.feedLoading = false;
            }
        },

        /**
         * Set up real-time listeners for lobby updates from friends
         */
        setupRealtimeListeners() {
            // Check if Echo is available
            if (typeof window.Echo === 'undefined') {
                console.warn('[LobbyPage] Echo not available, real-time updates disabled');
                return;
            }

            console.log('[LobbyPage] Setting up real-time listeners for', this.friendIds.length, 'friends');

            // Listen to each friend's lobby channel
            this.friendIds.forEach(friendId => {
                const channelName = `user.${friendId}.lobby`;

                try {
                    const channel = window.Echo.private(channelName)
                        .listen('.lobby.created', (e) => {
                            console.log('[LobbyPage] Friend created lobby:', e);
                            this.handleLobbyCreated(e);
                        })
                        .listen('.lobby.deleted', (e) => {
                            console.log('[LobbyPage] Friend deleted lobby:', e);
                            this.handleLobbyDeleted(e);
                        })
                        .listen('.lobby.expired', (e) => {
                            console.log('[LobbyPage] Friend lobby expired:', e);
                            this.handleLobbyDeleted(e);
                        });

                    this.echoChannels.push({ name: channelName, channel });
                } catch (error) {
                    console.warn('[LobbyPage] Failed to subscribe to channel:', channelName, error);
                }
            });
        },

        /**
         * Handle lobby created event - reload feed
         */
        handleLobbyCreated(event) {
            // Reload feed to get the new lobby with full data
            this.loadFeed();
        },

        /**
         * Handle lobby deleted event - remove from feed
         */
        handleLobbyDeleted(event) {
            const lobbyId = event.lobby_id || event.lobbyId;
            if (lobbyId) {
                this.feedLobbies = this.feedLobbies.filter(lobby => lobby.id !== lobbyId);
                console.log('[LobbyPage] Removed lobby from feed:', lobbyId);
            }
        },

        /**
         * Check if lobby can be joined directly (steam:// link)
         */
        canDirectJoin(lobby) {
            if (!lobby.join_link) return false;
            return lobby.join_link.startsWith('steam://');
        },

        /**
         * Copy feed lobby join info to clipboard
         */
        async copyFeedJoinInfo(lobby) {
            const text = lobby.join_info || lobby.join_link || '';
            if (!text) {
                console.warn('[LobbyPage] No join info to copy');
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
                this.copiedFeedLobbyId = lobby.id;
                setTimeout(() => this.copiedFeedLobbyId = null, 2000);
                console.log('[LobbyPage] Copied join info:', text);
            } catch (error) {
                console.error('[LobbyPage] Failed to copy feed join info:', error);
            }
        },

        /**
         * Clean up Echo channels on destroy
         */
        destroy() {
            console.log('[LobbyPage] Cleaning up', this.echoChannels.length, 'Echo channels');
            this.echoChannels.forEach(({ name }) => {
                try {
                    window.Echo.leave(name);
                } catch (error) {
                    console.warn('[LobbyPage] Failed to leave channel:', name, error);
                }
            });
            this.echoChannels = [];
        }
    };
};

// Add CSS animations if not already present
if (!document.getElementById('lobby-page-animations')) {
    const style = document.createElement('style');
    style.id = 'lobby-page-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

export default window.lobbyPage;
