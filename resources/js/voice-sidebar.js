/**
 * Voice Sidebar Real-time Updates
 *
 * Handles WebSocket events for voice channel sidebar integration.
 * Listens for voice.user.* events and updates the UI in real-time
 * to show speaking indicators, mute/deafen status, and user join/leave.
 *
 * @package Glyph
 * @version 1.0.0
 */

class VoiceSidebarManager {
    constructor() {
        this.serverId = null;
        this.echoChannel = null;
        this.isInitialized = false;

        // Bind methods
        this.handleUserJoined = this.handleUserJoined.bind(this);
        this.handleUserLeft = this.handleUserLeft.bind(this);
        this.handleUserSpeaking = this.handleUserSpeaking.bind(this);
        this.handleUserMuted = this.handleUserMuted.bind(this);
        this.handleUserDeafened = this.handleUserDeafened.bind(this);
    }

    /**
     * Initialize WebSocket listeners for a specific server
     *
     * @param {number} serverId - The server ID to listen for events
     */
    init(serverId) {
        if (!serverId) {
            console.warn('VoiceSidebarManager: No server ID provided');
            return;
        }

        // Don't re-initialize for the same server
        if (this.serverId === serverId && this.isInitialized) {
            return;
        }

        // Clean up previous listeners
        if (this.echoChannel) {
            this.cleanup();
        }

        this.serverId = serverId;

        // Check if Echo is available
        if (typeof window.Echo === 'undefined') {
            console.warn('VoiceSidebarManager: Echo not available');
            return;
        }

        try {
            // Subscribe to the private server channel
            this.echoChannel = window.Echo.private(`server.${serverId}`);

            // Listen for voice events
            this.echoChannel
                .listen('.voice.user.joined', this.handleUserJoined)
                .listen('.voice.user.left', this.handleUserLeft)
                .listen('.voice.user.speaking', this.handleUserSpeaking)
                .listen('.voice.user.muted', this.handleUserMuted)
                .listen('.voice.user.deafened', this.handleUserDeafened);

            this.isInitialized = true;
            console.log('VoiceSidebarManager: Initialized for server', serverId);

        } catch (error) {
            console.error('VoiceSidebarManager: Failed to initialize', error);
        }
    }

    /**
     * Handle user joined voice channel event
     *
     * @param {Object} data - Event data containing user and channel info
     */
    handleUserJoined(data) {
        console.log('Voice user joined:', data);

        const channelWrapper = document.querySelector(
            `.voice-channel-wrapper[data-channel-id="${data.channel_id}"]`
        );

        if (!channelWrapper) return;

        // Get the Alpine component
        const alpine = channelWrapper.__x;
        if (alpine) {
            // Add user to the list
            const newUser = {
                id: data.user.id,
                name: data.user.display_name || data.user.username,
                avatar: data.user.avatar_url || '/images/default-avatar.png',
                isSpeaking: false,
                isMuted: false,
                isDeafened: false,
                isStreaming: false
            };

            alpine.$data.users.push(newUser);
            alpine.$data.userCount = alpine.$data.users.length;
            alpine.$data.expanded = true; // Auto-expand when user joins
        }

        // Add joining animation
        setTimeout(() => {
            const userItem = channelWrapper.querySelector(
                `.voice-user-item[data-user-id="${data.user.id}"]`
            );
            if (userItem) {
                userItem.classList.add('joining');
                setTimeout(() => userItem.classList.remove('joining'), 300);
            }
        }, 50);
    }

    /**
     * Handle user left voice channel event
     *
     * @param {Object} data - Event data containing user and channel info
     */
    handleUserLeft(data) {
        console.log('Voice user left:', data);

        const channelWrapper = document.querySelector(
            `.voice-channel-wrapper[data-channel-id="${data.channel_id}"]`
        );

        if (!channelWrapper) return;

        // Add leaving animation
        const userItem = channelWrapper.querySelector(
            `.voice-user-item[data-user-id="${data.user.id}"]`
        );

        if (userItem) {
            userItem.classList.add('leaving');
        }

        // Remove user after animation
        setTimeout(() => {
            const alpine = channelWrapper.__x;
            if (alpine) {
                alpine.$data.users = alpine.$data.users.filter(u => u.id !== data.user.id);
                alpine.$data.userCount = alpine.$data.users.length;
            }
        }, 300);
    }

    /**
     * Handle user speaking status change event
     *
     * @param {Object} data - Event data containing user_id, channel_id, is_speaking
     */
    handleUserSpeaking(data) {
        const channelWrapper = document.querySelector(
            `.voice-channel-wrapper[data-channel-id="${data.channel_id}"]`
        );

        if (!channelWrapper) return;

        // Get the Alpine component
        const alpine = channelWrapper.__x;
        if (alpine) {
            const user = alpine.$data.users.find(u => u.id === data.user_id);
            if (user) {
                user.isSpeaking = data.is_speaking;
            }
        }

        // Also update the DOM directly for immediate visual feedback
        const userItem = channelWrapper.querySelector(
            `.voice-user-item[data-user-id="${data.user_id}"]`
        );

        if (userItem) {
            if (data.is_speaking) {
                userItem.classList.add('speaking');
                const avatarWrapper = userItem.querySelector('.voice-user-avatar-wrapper');
                if (avatarWrapper) {
                    avatarWrapper.classList.add('speaking');
                }
            } else {
                userItem.classList.remove('speaking');
                const avatarWrapper = userItem.querySelector('.voice-user-avatar-wrapper');
                if (avatarWrapper) {
                    avatarWrapper.classList.remove('speaking');
                }
            }
        }
    }

    /**
     * Handle user muted status change event
     *
     * @param {Object} data - Event data containing user info and is_muted
     */
    handleUserMuted(data) {
        console.log('Voice user muted:', data);

        const channelWrapper = document.querySelector(
            `.voice-channel-wrapper[data-channel-id="${data.channel_id}"]`
        );

        if (!channelWrapper) return;

        // Get the Alpine component
        const alpine = channelWrapper.__x;
        if (alpine) {
            const user = alpine.$data.users.find(u => u.id === data.user.id);
            if (user) {
                user.isMuted = data.is_muted;
            }
        }
    }

    /**
     * Handle user deafened status change event
     *
     * @param {Object} data - Event data containing user info and is_deafened
     */
    handleUserDeafened(data) {
        console.log('Voice user deafened:', data);

        const channelWrapper = document.querySelector(
            `.voice-channel-wrapper[data-channel-id="${data.channel_id}"]`
        );

        if (!channelWrapper) return;

        // Get the Alpine component
        const alpine = channelWrapper.__x;
        if (alpine) {
            const user = alpine.$data.users.find(u => u.id === data.user.id);
            if (user) {
                user.isDeafened = data.is_deafened;
            }
        }
    }

    /**
     * Cleanup WebSocket listeners
     */
    cleanup() {
        if (this.echoChannel && window.Echo) {
            window.Echo.leave(`server.${this.serverId}`);
            this.echoChannel = null;
        }
        this.isInitialized = false;
        this.serverId = null;
    }
}

// Create global instance
window.voiceSidebarManager = new VoiceSidebarManager();

// Auto-initialize when server page loads
document.addEventListener('DOMContentLoaded', () => {
    // Look for server ID in the page
    const serverElement = document.querySelector('[data-server-id]');
    if (serverElement) {
        const serverId = parseInt(serverElement.dataset.serverId, 10);
        if (serverId) {
            window.voiceSidebarManager.init(serverId);
        }
    }
});

// Export for ES modules
export default VoiceSidebarManager;
