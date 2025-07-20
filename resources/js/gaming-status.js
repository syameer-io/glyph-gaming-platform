/**
 * Gaming Status Real-time Synchronization (Phase 2)
 * Handles real-time gaming status updates across all pages
 */

class GamingStatusManager {
    constructor() {
        this.userId = null;
        this.userServers = [];
        this.listeners = [];
        this.initialized = false;
    }

    /**
     * Initialize gaming status manager
     * @param {number} userId - Current user ID
     * @param {Array} userServers - Array of server IDs the user belongs to
     */
    init(userId, userServers = []) {
        if (this.initialized) {
            console.warn('GamingStatusManager already initialized');
            return;
        }

        this.userId = userId;
        this.userServers = userServers;
        this.setupEchoListeners();
        this.initialized = true;
        
        console.log('GamingStatusManager initialized for user:', userId, 'servers:', userServers);
    }

    /**
     * Setup Echo listeners for gaming status events
     */
    setupEchoListeners() {
        if (!window.Echo) {
            console.error('Echo not available for gaming status');
            return;
        }

        // Listen to each server's gaming status channel
        this.userServers.forEach(serverId => {
            const channel = window.Echo.private(`server.${serverId}.gaming-status`);
            
            channel.listen('.user.started.playing', (e) => {
                this.handleUserStartedPlaying(e);
            })
            .listen('.user.stopped.playing', (e) => {
                this.handleUserStoppedPlaying(e);
            })
            .listen('.user.changed.game', (e) => {
                this.handleUserChangedGame(e);
            })
            .listen('.user.game.status.changed', (e) => {
                this.handleUserGameStatusChanged(e);
            })
            .error((error) => {
                console.error(`Gaming status channel error for server ${serverId}:`, error);
            });

            this.listeners.push(channel);
        });

        // Also listen to personal gaming status channel
        const personalChannel = window.Echo.private(`user.${this.userId}.gaming-status`);
        
        personalChannel.listen('.user.started.playing', (e) => {
            this.handleUserStartedPlaying(e);
        })
        .listen('.user.stopped.playing', (e) => {
            this.handleUserStoppedPlaying(e);
        })
        .listen('.user.changed.game', (e) => {
            this.handleUserChangedGame(e);
        })
        .listen('.user.game.status.changed', (e) => {
            this.handleUserGameStatusChanged(e);
        });

        this.listeners.push(personalChannel);

        console.log('Gaming status Echo listeners setup complete');
    }

    /**
     * Handle user started playing event
     */
    handleUserStartedPlaying(event) {
        console.log('User started playing:', event);
        
        const { user, game } = event;
        
        // Update status display across the page
        this.updateUserGameStatus(user.id, {
            status: 'playing',
            game: game.name,
            details: this.formatGameDetails(game)
        });

        // Show notification if not current user
        if (user.id !== this.userId) {
            this.showGamingNotification(`${user.display_name} started playing ${game.name}`, 'started');
        }
    }

    /**
     * Handle user stopped playing event
     */
    handleUserStoppedPlaying(event) {
        console.log('User stopped playing:', event);
        
        const { user, previous_game } = event;
        
        // Update status display
        this.updateUserGameStatus(user.id, {
            status: 'offline',
            game: null,
            details: null
        });

        // Show notification if not current user
        if (user.id !== this.userId) {
            this.showGamingNotification(`${user.display_name} stopped playing ${previous_game.name}`, 'stopped');
        }
    }

    /**
     * Handle user changed game event
     */
    handleUserChangedGame(event) {
        console.log('User changed game:', event);
        
        const { user, current_game, previous_game } = event;
        
        // Update status display
        this.updateUserGameStatus(user.id, {
            status: 'playing',
            game: current_game.name,
            details: this.formatGameDetails(current_game)
        });

        // Show notification if not current user
        if (user.id !== this.userId) {
            this.showGamingNotification(
                `${user.display_name} switched from ${previous_game.name} to ${current_game.name}`, 
                'changed'
            );
        }
    }

    /**
     * Handle user game status changed event
     */
    handleUserGameStatusChanged(event) {
        console.log('User game status changed:', event);
        
        const { user, current_status, changes } = event;
        
        // Update status display with new details
        this.updateUserGameStatus(user.id, {
            status: 'playing',
            game: event.game.name,
            details: this.formatGameDetails(current_status)
        });

        // Show subtle notification for status changes
        if (user.id !== this.userId && changes.length > 0) {
            const changeText = changes.join(', ');
            this.showGamingNotification(
                `${user.display_name} changed ${changeText} in ${event.game.name}`, 
                'status-change'
            );
        }
    }

    /**
     * Update user game status in UI elements
     */
    updateUserGameStatus(userId, statusData) {
        // Find all elements that display this user's status
        const statusElements = document.querySelectorAll(`[data-user-status="${userId}"]`);
        
        statusElements.forEach(element => {
            if (statusData.status === 'playing') {
                element.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <div class="text-sm">
                            <div class="text-green-400">${statusData.game}</div>
                            ${statusData.details ? `<div class="text-gray-400 text-xs">${statusData.details}</div>` : ''}
                        </div>
                    </div>
                `;
                element.classList.remove('hidden');
            } else {
                element.innerHTML = '';
                element.classList.add('hidden');
            }
        });

        // Update member list statuses if present
        const memberElements = document.querySelectorAll(`[data-member-id="${userId}"]`);
        memberElements.forEach(element => {
            const statusIndicator = element.querySelector('.gaming-status-indicator');
            if (statusIndicator) {
                if (statusData.status === 'playing') {
                    statusIndicator.className = 'gaming-status-indicator w-2 h-2 bg-green-500 rounded-full';
                    statusIndicator.title = `Playing ${statusData.game}`;
                } else {
                    statusIndicator.className = 'gaming-status-indicator w-2 h-2 bg-gray-500 rounded-full';
                    statusIndicator.title = 'Not playing';
                }
            }
        });
    }

    /**
     * Format game details for display
     */
    formatGameDetails(gameData) {
        const details = [];
        
        if (gameData.server_name) {
            details.push(gameData.server_name);
        }
        
        if (gameData.map) {
            details.push(gameData.map);
        }
        
        if (gameData.game_mode) {
            details.push(gameData.game_mode);
        }
        
        return details.length > 0 ? details.join(' • ') : null;
    }

    /**
     * Show gaming status notification
     */
    showGamingNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `
            fixed top-4 right-4 z-50 max-w-sm bg-gray-800 border border-gray-700 
            rounded-lg shadow-lg p-4 transform transition-all duration-300 translate-x-full
        `;
        
        const iconClass = {
            'started': 'text-green-400',
            'stopped': 'text-gray-400', 
            'changed': 'text-blue-400',
            'status-change': 'text-purple-400'
        }[type] || 'text-gray-400';
        
        notification.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="w-2 h-2 ${iconClass} rounded-full"></div>
                <div class="text-sm text-gray-200">${message}</div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }

    /**
     * Cleanup listeners
     */
    destroy() {
        this.listeners.forEach(channel => {
            if (window.Echo && channel) {
                window.Echo.leaveChannel(channel.name);
            }
        });
        
        this.listeners = [];
        this.initialized = false;
        
        console.log('GamingStatusManager destroyed');
    }
}

// Create global instance
window.GamingStatusManager = new GamingStatusManager();

export default GamingStatusManager;