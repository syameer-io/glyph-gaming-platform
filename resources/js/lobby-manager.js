/**
 * Lobby Manager Component (Alpine.js)
 *
 * Manages multi-game lobbies on user profiles.
 * Provides UI for creating, viewing, and deleting game lobbies.
 *
 * Note: Alpine.js is already available globally via bootstrap.js
 */

// Register the Alpine.js component globally
window.lobbyManager = function(userId, isOwnProfile) {
    return {
        // Component State
        userId: userId,
        isOwnProfile: isOwnProfile,
        selectedGame: '',
        selectedJoinMethod: '',
        availableJoinMethods: [],
        activeLobbies: [],
        formData: {},
        loading: false,
        error: null,
        showInstructions: false,
        copiedLobbyId: null,

        // Countdown timers (for real-time updates)
        timers: {},

        /**
         * Initialize component
         */
        init() {
            this.loadActiveLobbies();

            // Set up real-time countdown updates (every second)
            setInterval(() => {
                this.$forceUpdate();
            }, 1000);
        },

        /**
         * Get current method configuration
         */
        get currentMethodConfig() {
            if (!this.selectedJoinMethod || this.availableJoinMethods.length === 0) {
                return null;
            }
            return this.availableJoinMethods.find(m => m.join_method === this.selectedJoinMethod);
        },

        /**
         * Check if form can be saved
         */
        canSave() {
            if (!this.selectedGame || !this.selectedJoinMethod) {
                return false;
            }

            // Validate based on join method
            switch (this.selectedJoinMethod) {
                case 'steam_lobby':
                    return this.formData.steam_lobby_link && this.formData.steam_lobby_link.trim().length > 0;
                case 'steam_connect':
                    return this.formData.server_ip && this.formData.server_port;
                case 'lobby_code':
                    return this.formData.lobby_code && this.formData.lobby_code.trim().length > 0;
                case 'server_address':
                    return this.formData.server_ip && this.formData.server_ip.trim().length > 0;
                case 'join_command':
                    return this.formData.join_command && this.formData.join_command.trim().length > 0;
                case 'private_match':
                    return this.formData.match_name && this.formData.match_name.trim().length > 0;
                default:
                    return false;
            }
        },

        /**
         * Load available join methods for selected game
         */
        async loadGameJoinMethods() {
            if (!this.selectedGame) {
                this.availableJoinMethods = [];
                this.selectedJoinMethod = '';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(`/api/games/${this.selectedGame}/join-methods`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load join methods');
                }

                const data = await response.json();
                this.availableJoinMethods = data.join_methods || data || [];

                // Auto-select first method
                if (this.availableJoinMethods.length > 0) {
                    this.selectedJoinMethod = this.availableJoinMethods[0].join_method;
                    this.updateFormFields();
                }
            } catch (error) {
                console.error('Error loading join methods:', error);
                this.error = 'Failed to load join methods. Please try again.';
                this.availableJoinMethods = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Update form fields when join method changes
         */
        updateFormFields() {
            // Reset form data
            this.formData = {};
            this.error = null;

            // Auto-fill default port for server address methods
            if (this.selectedJoinMethod === 'server_address' && this.currentMethodConfig?.default_port) {
                this.formData.server_port = this.currentMethodConfig.default_port;
            }
        },

        /**
         * Save lobby to database
         */
        async saveLobby() {
            if (!this.canSave()) {
                this.error = 'Please fill in all required fields';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/api/lobbies', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        game_id: parseInt(this.selectedGame),
                        join_method: this.selectedJoinMethod,
                        ...this.formData
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || data.error || 'Failed to save lobby');
                }

                // Show success message
                this.showToast('Lobby created successfully!', 'success');

                // Reload active lobbies
                await this.loadActiveLobbies();

                // Reset form
                this.resetForm();
            } catch (error) {
                console.error('Error saving lobby:', error);
                this.error = error.message || 'Failed to save lobby. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        /**
         * Load active lobbies for user
         */
        async loadActiveLobbies() {
            this.loading = true;

            try {
                const response = await fetch('/api/lobbies/my-lobbies', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load lobbies');
                }

                const data = await response.json();
                this.activeLobbies = data.lobbies || data || [];
            } catch (error) {
                console.error('Error loading active lobbies:', error);
                // Don't show error for loading lobbies (might not be authenticated)
                this.activeLobbies = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * Delete lobby
         */
        async deleteLobby(lobbyId) {
            if (!confirm('Are you sure you want to delete this lobby?')) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(`/api/lobbies/${lobbyId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to delete lobby');
                }

                // Show success message
                this.showToast('Lobby deleted successfully', 'success');

                // Reload active lobbies
                await this.loadActiveLobbies();
            } catch (error) {
                console.error('Error deleting lobby:', error);
                this.error = 'Failed to delete lobby. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        /**
         * Reset form to initial state
         */
        resetForm() {
            this.selectedGame = '';
            this.selectedJoinMethod = '';
            this.availableJoinMethods = [];
            this.formData = {};
            this.error = null;
        },

        /**
         * Get game icon emoji
         */
        getGameIcon(gameId) {
            // Map common game IDs to emojis
            const gameIcons = {
                730: '🔫', // CS2
                570: '⚔️', // Dota 2
                230410: '🤖', // Warframe
                // Add more as needed
            };
            return gameIcons[gameId] || '🎮';
        },

        /**
         * Format join method for display
         */
        formatJoinMethod(joinMethod) {
            const labels = {
                steam_lobby: 'Steam Lobby',
                steam_connect: 'Server Address',
                lobby_code: 'Lobby Code',
                server_address: 'Server Address',
                join_command: 'Join Command',
                private_match: 'Private Match',
            };
            return labels[joinMethod] || joinMethod;
        },

        /**
         * Get join information to display
         */
        getJoinInfo(lobby) {
            switch (lobby.join_method) {
                case 'steam_lobby':
                    return `steam://joinlobby/${lobby.steam_app_id}/${lobby.steam_lobby_id}/${lobby.steam_profile_id}`;
                case 'steam_connect':
                    return `${lobby.server_ip}:${lobby.server_port}`;
                case 'lobby_code':
                    return lobby.lobby_code;
                case 'server_address':
                    return lobby.server_port ? `${lobby.server_ip}:${lobby.server_port}` : lobby.server_ip;
                case 'join_command':
                    return lobby.join_command;
                case 'private_match':
                    return lobby.match_name;
                default:
                    return 'Unknown';
            }
        },

        /**
         * Get join link for lobby (for clickable buttons)
         */
        getJoinLink(lobby) {
            if (lobby.join_method === 'steam_lobby') {
                return `steam://joinlobby/${lobby.steam_app_id}/${lobby.steam_lobby_id}/${lobby.steam_profile_id}`;
            } else if (lobby.join_method === 'steam_connect') {
                return `steam://connect/${lobby.server_ip}:${lobby.server_port}`;
            }
            return '#';
        },

        /**
         * Copy text to clipboard
         */
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

                // Show "Copied!" feedback
                this.copiedLobbyId = lobbyId;
                setTimeout(() => {
                    this.copiedLobbyId = null;
                }, 2000);

                this.showToast('Copied to clipboard!', 'success');
            } catch (error) {
                console.error('Failed to copy:', error);
                this.showToast('Failed to copy. Please copy manually.', 'error');
            }
        },

        /**
         * Format time remaining for display
         */
        formatTimeRemaining(expiresAt) {
            if (!expiresAt) {
                return 'No expiration';
            }

            const now = new Date();
            const expires = new Date(expiresAt);
            const diff = expires - now;

            if (diff <= 0) {
                return 'Expired';
            }

            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            if (minutes > 60) {
                const hours = Math.floor(minutes / 60);
                const remainingMinutes = minutes % 60;
                return `${hours}h ${remainingMinutes}m remaining`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds}s remaining`;
            } else {
                return `${seconds}s remaining`;
            }
        },

        /**
         * Get time remaining in minutes
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

// Add CSS animations if not already present
if (!document.getElementById('lobby-manager-animations')) {
    const style = document.createElement('style');
    style.id = 'lobby-manager-animations';
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

export default window.lobbyManager;
