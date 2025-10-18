/**
 * Voice Chat Implementation - Agora.io WebRTC Integration
 *
 * Provides real-time voice communication for gaming community channels.
 * Features:
 * - Secure token-based authentication with Agora.io
 * - Auto-reconnection on network disruption
 * - Network quality monitoring (green/yellow/red indicators)
 * - Real-time participant list updates
 * - Mute/unmute functionality with server-side tracking
 * - Comprehensive error handling with user-friendly notifications
 */

import AgoraRTC from 'agora-rtc-sdk-ng';

class VoiceChat {
    constructor() {
        // Agora RTC client instance
        this.client = null;

        // Local audio track (microphone)
        this.localAudioTrack = null;

        // Connection state
        this.isConnected = false;
        this.isConnecting = false;
        this.isMuted = false;

        // Current channel information
        this.currentChannelId = null;
        this.currentChannelName = null;
        this.currentUid = null;
        this.currentToken = null;
        this.tokenExpiresAt = null;

        // Participants tracking
        this.participants = new Map(); // uid -> {id, username, isMuted}

        // Network quality tracking
        this.networkQuality = {
            uplink: 0,
            downlink: 0
        };

        // Auto-reconnection settings
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 2000; // Start with 2 seconds
        this.reconnectTimer = null;

        // UI update callbacks
        this.callbacks = {
            onConnectionStateChange: null,
            onNetworkQualityChange: null,
            onParticipantsUpdate: null,
            onUserJoined: null,
            onUserLeft: null,
            onError: null,
            onNotification: null
        };

        console.log('VoiceChat instance created');
    }

    /**
     * Initialize Agora RTC client with optimized settings
     */
    initializeClient() {
        if (this.client) {
            console.warn('Agora client already initialized');
            return;
        }

        try {
            // Create Agora client with VP8 codec and cloud proxy for reliability
            this.client = AgoraRTC.createClient({
                mode: 'rtc',
                codec: 'vp8',
            });

            // Setup client event listeners
            this.setupClientEvents();

            console.log('Agora RTC client initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Agora client:', error);
            this.showError('Failed to initialize voice chat. Please refresh the page.');
        }
    }

    /**
     * Register callback functions for UI updates
     *
     * @param {string} event - Event name
     * @param {Function} callback - Callback function
     */
    on(event, callback) {
        if (this.callbacks.hasOwnProperty(`on${this.capitalize(event)}`)) {
            this.callbacks[`on${this.capitalize(event)}`] = callback;
        } else {
            console.warn(`Unknown event: ${event}`);
        }
    }

    /**
     * Setup Agora RTC client event listeners
     * Handles user-joined, user-left, network-quality, and connection-state events
     */
    setupClientEvents() {
        if (!this.client) {
            console.error('Client not initialized');
            return;
        }

        // User joined the channel
        this.client.on('user-joined', async (user) => {
            console.log('Remote user joined:', user.uid);

            // Track participant
            this.participants.set(user.uid, {
                uid: user.uid,
                username: `User ${user.uid}`, // Will be updated by backend data
                isMuted: false,
                hasAudio: false
            });

            this.updateParticipantsList();

            if (this.callbacks.onUserJoined) {
                this.callbacks.onUserJoined(user);
            }
        });

        // User left the channel
        this.client.on('user-left', (user, reason) => {
            console.log('Remote user left:', user.uid, 'Reason:', reason);

            // Remove participant
            this.participants.delete(user.uid);
            this.updateParticipantsList();

            if (this.callbacks.onUserLeft) {
                this.callbacks.onUserLeft(user, reason);
            }
        });

        // User published audio track
        this.client.on('user-published', async (user, mediaType) => {
            if (mediaType === 'audio') {
                console.log('User published audio:', user.uid);

                try {
                    // Subscribe to the remote audio track
                    await this.client.subscribe(user, mediaType);
                    console.log('Subscribed to user audio:', user.uid);

                    // Play the remote audio track
                    const remoteAudioTrack = user.audioTrack;
                    remoteAudioTrack.play();

                    // Update participant status
                    if (this.participants.has(user.uid)) {
                        this.participants.get(user.uid).hasAudio = true;
                        this.updateParticipantsList();
                    }

                    this.showNotification(`User ${user.uid} is now speaking`);
                } catch (error) {
                    console.error('Failed to subscribe to user audio:', error);
                }
            }
        });

        // User unpublished audio track
        this.client.on('user-unpublished', (user, mediaType) => {
            if (mediaType === 'audio') {
                console.log('User unpublished audio:', user.uid);

                // Update participant status
                if (this.participants.has(user.uid)) {
                    this.participants.get(user.uid).hasAudio = false;
                    this.updateParticipantsList();
                }
            }
        });

        // Network quality updates (for monitoring connection quality)
        this.client.on('network-quality', (stats) => {
            this.networkQuality.uplink = stats.uplinkNetworkQuality;
            this.networkQuality.downlink = stats.downlinkNetworkQuality;

            this.updateNetworkQuality();
        });

        // Connection state changes (critical for auto-reconnection)
        this.client.on('connection-state-change', (curState, prevState, reason) => {
            console.log('Connection state changed:', {
                from: prevState,
                to: curState,
                reason: reason
            });

            this.handleConnectionStateChange(curState, prevState, reason);
        });

        // Volume indicator for speaking detection
        this.client.enableAudioVolumeIndicator();
        this.client.on('volume-indicator', (volumes) => {
            volumes.forEach((volume) => {
                // volume.level ranges from 0 to 100
                if (volume.level > 5) { // Speaking threshold
                    // Trigger speaking animation in UI
                    if (this.callbacks.onUserSpeaking) {
                        this.callbacks.onUserSpeaking(volume.uid, volume.level);
                    }
                }
            });
        });

        // Token privilege will expire (refresh token before expiry)
        this.client.on('token-privilege-will-expire', async () => {
            console.warn('Token will expire soon, refreshing...');
            await this.refreshToken();
        });

        // Token privilege expired (need immediate refresh)
        this.client.on('token-privilege-did-expire', async () => {
            console.error('Token expired, attempting to refresh and rejoin...');
            await this.handleTokenExpired();
        });

        // Critical errors
        this.client.on('exception', (event) => {
            console.error('Agora exception:', event);

            if (event.code === 'INVALID_PARAMS') {
                this.showError('Invalid voice chat parameters. Please try again.');
            } else if (event.code === 'NOT_SUPPORTED') {
                this.showError('Your browser does not support voice chat. Please use Chrome, Edge, or Firefox.');
            }
        });

        console.log('Client event listeners setup complete');
    }

    /**
     * Handle connection state changes with auto-reconnection logic
     *
     * @param {string} curState - Current connection state
     * @param {string} prevState - Previous connection state
     * @param {string} reason - Reason for state change
     */
    handleConnectionStateChange(curState, prevState, reason) {
        // Update UI based on connection state
        this.updateUI(curState);

        // Notify callback
        if (this.callbacks.onConnectionStateChange) {
            this.callbacks.onConnectionStateChange(curState, prevState, reason);
        }

        // Handle different connection states
        switch (curState) {
            case 'CONNECTED':
                this.isConnected = true;
                this.isConnecting = false;
                this.reconnectAttempts = 0;
                this.reconnectDelay = 2000; // Reset delay

                this.showNotification('Connected to voice channel', 'success');

                // Fetch participants from server
                this.fetchParticipants();
                break;

            case 'CONNECTING':
                this.isConnecting = true;
                this.showNotification('Connecting to voice channel...', 'info');
                break;

            case 'DISCONNECTED':
                this.isConnected = false;
                this.isConnecting = false;

                // Only attempt auto-reconnect if disconnected unexpectedly
                if (reason !== 'LEAVE' && this.currentChannelId) {
                    this.showNotification('Disconnected from voice channel. Attempting to reconnect...', 'warning');
                    this.attemptReconnect();
                } else {
                    this.showNotification('Disconnected from voice channel', 'info');
                }
                break;

            case 'DISCONNECTING':
                this.isConnecting = false;
                this.showNotification('Leaving voice channel...', 'info');
                break;

            case 'RECONNECTING':
                this.isConnected = false;
                this.isConnecting = true;
                this.showNotification('Connection lost. Reconnecting...', 'warning');
                break;
        }
    }

    /**
     * Attempt to reconnect to the voice channel with exponential backoff
     */
    async attemptReconnect() {
        // Clear existing reconnect timer
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }

        // Check if max attempts reached
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.showError('Failed to reconnect to voice channel. Please try joining again.');
            this.cleanup();
            return;
        }

        this.reconnectAttempts++;
        console.log(`Reconnection attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts}`);

        this.showNotification(
            `Reconnecting... (Attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`,
            'warning'
        );

        // Wait with exponential backoff
        this.reconnectTimer = setTimeout(async () => {
            try {
                // Try to rejoin with existing credentials
                if (this.currentChannelName && this.currentUid && this.currentToken) {
                    await this.client.join(
                        this.getAgoraAppId(),
                        this.currentChannelName,
                        this.currentToken,
                        this.currentUid
                    );

                    // Republish local audio if it exists
                    if (this.localAudioTrack) {
                        await this.client.publish([this.localAudioTrack]);
                    }

                    console.log('Reconnection successful');
                    this.showNotification('Reconnected successfully', 'success');
                } else {
                    throw new Error('Missing channel credentials for reconnection');
                }
            } catch (error) {
                console.error('Reconnection failed:', error);

                // Increase delay for next attempt (exponential backoff)
                this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000); // Max 30 seconds

                // Try again
                this.attemptReconnect();
            }
        }, this.reconnectDelay);
    }

    /**
     * Update network quality indicator in UI
     * Quality levels: 0 (unknown), 1-2 (excellent/good - green), 3-4 (poor/bad - yellow), 5-6 (very bad - red)
     */
    updateNetworkQuality() {
        const quality = {
            uplink: this.networkQuality.uplink,
            downlink: this.networkQuality.downlink,
            overall: Math.max(this.networkQuality.uplink, this.networkQuality.downlink)
        };

        // Determine quality status and color
        let status = 'unknown';
        let color = 'gray';

        if (quality.overall === 0) {
            status = 'unknown';
            color = 'gray';
        } else if (quality.overall <= 2) {
            status = 'excellent';
            color = 'green';
        } else if (quality.overall <= 4) {
            status = 'poor';
            color = 'yellow';
        } else {
            status = 'bad';
            color = 'red';
        }

        quality.status = status;
        quality.color = color;

        console.debug('Network quality:', quality);

        // Notify callback for UI update
        if (this.callbacks.onNetworkQualityChange) {
            this.callbacks.onNetworkQualityChange(quality);
        }

        // Show warning if quality is poor
        if (quality.overall >= 5 && this.isConnected) {
            this.showNotification('Poor network quality detected. Audio may be unstable.', 'warning');
        }
    }

    /**
     * Join a voice channel
     *
     * @param {number} channelId - The voice channel ID to join
     * @returns {Promise<boolean>} Success status
     */
    async joinChannel(channelId) {
        if (this.isConnected || this.isConnecting) {
            console.warn('Already connected or connecting to a voice channel');
            this.showNotification('You are already in a voice channel. Please leave first.', 'warning');
            return false;
        }

        try {
            console.log('Joining voice channel:', channelId);
            this.currentChannelId = channelId;

            // Initialize client if not already done
            if (!this.client) {
                this.initializeClient();
            }

            // Get token from backend
            const tokenData = await this.getTokenFromBackend(channelId);

            if (!tokenData.success) {
                throw new Error(tokenData.message || 'Failed to get voice token');
            }

            // Store token data
            this.currentToken = tokenData.token;
            this.currentChannelName = tokenData.channel_name;
            this.currentUid = tokenData.uid;
            this.tokenExpiresAt = tokenData.expires_at;

            console.log('Token received:', {
                channel: this.currentChannelName,
                uid: this.currentUid,
                expires_at: new Date(this.tokenExpiresAt * 1000).toISOString()
            });

            // Join the Agora channel
            await this.client.join(
                this.getAgoraAppId(),
                this.currentChannelName,
                this.currentToken,
                this.currentUid
            );

            console.log('Joined Agora channel successfully');

            // Create and publish local audio track (microphone)
            this.localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack({
                encoderConfig: 'music_standard', // High quality audio for gaming
                AEC: true, // Acoustic Echo Cancellation
                ANS: true, // Automatic Noise Suppression
                AGC: true, // Automatic Gain Control
            });

            console.log('Microphone audio track created');

            // Publish local audio to the channel
            await this.client.publish([this.localAudioTrack]);

            console.log('Local audio track published');

            this.showNotification('Successfully joined voice channel', 'success');

            return true;

        } catch (error) {
            console.error('Failed to join voice channel:', error);

            let errorMessage = 'Failed to join voice channel.';

            if (error.message) {
                errorMessage = error.message;
            } else if (error.code === 'PERMISSION_DENIED') {
                errorMessage = 'Microphone permission denied. Please allow microphone access and try again.';
            } else if (error.code === 'DEVICE_NOT_FOUND') {
                errorMessage = 'No microphone found. Please connect a microphone and try again.';
            } else if (error.code === 'INVALID_PARAMS') {
                errorMessage = 'Invalid channel parameters. Please contact support.';
            }

            this.showError(errorMessage);

            // Cleanup on failure
            await this.cleanup();

            return false;
        }
    }

    /**
     * Leave the current voice channel
     *
     * @returns {Promise<boolean>} Success status
     */
    async leaveChannel() {
        if (!this.isConnected && !this.isConnecting) {
            console.warn('Not connected to any voice channel');
            return false;
        }

        try {
            console.log('Leaving voice channel:', this.currentChannelId);

            // Clear reconnection timer if exists
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }

            // Unpublish and close local audio track
            if (this.localAudioTrack) {
                this.localAudioTrack.stop();
                this.localAudioTrack.close();
                this.localAudioTrack = null;
                console.log('Local audio track closed');
            }

            // Leave the Agora channel
            if (this.client) {
                await this.client.leave();
                console.log('Left Agora channel');
            }

            // Notify backend
            await this.notifyBackendLeave(this.currentChannelId);

            // Reset state
            this.isConnected = false;
            this.isConnecting = false;
            this.isMuted = false;
            this.participants.clear();
            this.reconnectAttempts = 0;

            const channelId = this.currentChannelId;
            this.currentChannelId = null;
            this.currentChannelName = null;
            this.currentUid = null;
            this.currentToken = null;
            this.tokenExpiresAt = null;

            this.showNotification('Left voice channel', 'info');

            // Update UI
            this.updateUI('DISCONNECTED');
            this.updateParticipantsList();

            console.log('Successfully left voice channel:', channelId);

            return true;

        } catch (error) {
            console.error('Failed to leave voice channel:', error);
            this.showError('Failed to leave voice channel properly. You may need to refresh the page.');

            // Force cleanup
            await this.cleanup();

            return false;
        }
    }

    /**
     * Toggle microphone mute/unmute
     *
     * @returns {Promise<boolean>} New mute state
     */
    async toggleMute() {
        if (!this.isConnected) {
            console.warn('Not connected to voice channel');
            this.showNotification('You must be in a voice channel to mute/unmute', 'warning');
            return this.isMuted;
        }

        try {
            const newMuteState = !this.isMuted;

            // Mute/unmute local audio track
            if (this.localAudioTrack) {
                await this.localAudioTrack.setEnabled(!newMuteState);
                this.isMuted = newMuteState;

                console.log('Microphone', newMuteState ? 'muted' : 'unmuted');

                // Notify backend to update session mute status
                await this.notifyBackendMuteToggle(this.currentChannelId, newMuteState);

                this.showNotification(
                    newMuteState ? 'Microphone muted' : 'Microphone unmuted',
                    'info'
                );

                return newMuteState;
            }

            console.warn('No local audio track to mute/unmute');
            return this.isMuted;

        } catch (error) {
            console.error('Failed to toggle mute:', error);
            this.showError('Failed to toggle microphone. Please try again.');
            return this.isMuted;
        }
    }

    /**
     * Update UI based on connection status
     *
     * @param {string} state - Connection state
     */
    updateUI(state) {
        console.debug('Updating UI for state:', state);

        // This method should be overridden or extended by the UI implementation
        // For now, we'll just log and trigger the callback

        const uiState = {
            state: state,
            isConnected: this.isConnected,
            isConnecting: this.isConnecting,
            isMuted: this.isMuted,
            channelId: this.currentChannelId,
            channelName: this.currentChannelName,
            participantCount: this.participants.size
        };

        console.debug('UI state:', uiState);
    }

    /**
     * Update participants list in UI
     */
    updateParticipantsList() {
        const participantsList = Array.from(this.participants.values());

        console.debug('Participants list updated:', participantsList);

        // Notify callback
        if (this.callbacks.onParticipantsUpdate) {
            this.callbacks.onParticipantsUpdate(participantsList);
        }
    }

    /**
     * Fetch participants from backend and merge with local participant data
     */
    async fetchParticipants() {
        if (!this.currentChannelId) {
            console.warn('No current channel to fetch participants for');
            return;
        }

        try {
            const response = await fetch(`/voice/channel/${this.currentChannelId}/participants`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success && data.participants) {
                // Merge backend participant data with local tracking
                data.participants.forEach(participant => {
                    if (this.participants.has(participant.id)) {
                        // Update existing participant with backend data
                        const localData = this.participants.get(participant.id);
                        this.participants.set(participant.id, {
                            ...localData,
                            username: participant.username,
                            display_name: participant.display_name,
                            avatar_url: participant.avatar_url,
                            isMuted: participant.is_muted,
                            joined_at: participant.joined_at
                        });
                    } else {
                        // Add participant from backend (might have joined before us)
                        this.participants.set(participant.id, {
                            uid: participant.id,
                            username: participant.username,
                            display_name: participant.display_name,
                            avatar_url: participant.avatar_url,
                            isMuted: participant.is_muted,
                            hasAudio: false,
                            joined_at: participant.joined_at
                        });
                    }
                });

                this.updateParticipantsList();

                console.log('Participants fetched and merged:', this.participants.size);
            }

        } catch (error) {
            console.error('Failed to fetch participants:', error);
            // Don't show error to user, this is background operation
        }
    }

    /**
     * Get Agora token from backend
     *
     * @param {number} channelId - Voice channel ID
     * @returns {Promise<Object>} Token data
     */
    async getTokenFromBackend(channelId) {
        try {
            const response = await fetch('/voice/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    channel_id: channelId
                })
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('Backend error:', data);
                return {
                    success: false,
                    message: data.message || 'Failed to get voice token'
                };
            }

            return data;

        } catch (error) {
            console.error('Network error getting token:', error);
            return {
                success: false,
                message: 'Network error. Please check your connection and try again.'
            };
        }
    }

    /**
     * Notify backend that user left voice channel
     *
     * @param {number} channelId - Voice channel ID
     */
    async notifyBackendLeave(channelId) {
        try {
            const response = await fetch('/voice/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    channel_id: channelId
                })
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('Failed to notify backend of leave:', data);
            } else {
                console.log('Backend notified of leave:', data);
            }

        } catch (error) {
            console.error('Network error notifying backend of leave:', error);
        }
    }

    /**
     * Notify backend of mute toggle
     *
     * @param {number} channelId - Voice channel ID
     * @param {boolean} isMuted - New mute state
     */
    async notifyBackendMuteToggle(channelId, isMuted) {
        try {
            const response = await fetch('/voice/mute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    channel_id: channelId,
                    is_muted: isMuted
                })
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('Failed to notify backend of mute toggle:', data);
            } else {
                console.log('Backend notified of mute toggle:', data);
            }

        } catch (error) {
            console.error('Network error notifying backend of mute toggle:', error);
        }
    }

    /**
     * Refresh token before expiry
     */
    async refreshToken() {
        if (!this.currentChannelId) {
            console.warn('No current channel to refresh token for');
            return;
        }

        try {
            console.log('Refreshing token...');

            const tokenData = await this.getTokenFromBackend(this.currentChannelId);

            if (!tokenData.success) {
                throw new Error(tokenData.message || 'Failed to refresh token');
            }

            // Renew token in Agora client
            await this.client.renewToken(tokenData.token);

            // Update stored token data
            this.currentToken = tokenData.token;
            this.tokenExpiresAt = tokenData.expires_at;

            console.log('Token refreshed successfully, expires at:',
                new Date(this.tokenExpiresAt * 1000).toISOString());

        } catch (error) {
            console.error('Failed to refresh token:', error);
            this.showError('Session expired. Please rejoin the voice channel.');
            await this.leaveChannel();
        }
    }

    /**
     * Handle token expiration
     */
    async handleTokenExpired() {
        console.error('Token has expired');
        this.showError('Your voice session has expired. Please rejoin the channel.');
        await this.leaveChannel();
    }

    /**
     * Get Agora App ID from meta tag
     *
     * @returns {string} Agora App ID
     */
    getAgoraAppId() {
        const appIdMeta = document.querySelector('meta[name="agora-app-id"]');

        if (!appIdMeta || !appIdMeta.content) {
            throw new Error('Agora App ID not found in page. Please contact support.');
        }

        return appIdMeta.content;
    }

    /**
     * Cleanup resources and reset state
     */
    async cleanup() {
        console.log('Cleaning up voice chat resources...');

        // Clear reconnect timer
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        // Close local audio track
        if (this.localAudioTrack) {
            this.localAudioTrack.stop();
            this.localAudioTrack.close();
            this.localAudioTrack = null;
        }

        // Leave and destroy client
        if (this.client) {
            try {
                await this.client.leave();
            } catch (error) {
                console.warn('Error leaving client during cleanup:', error);
            }
        }

        // Reset state
        this.isConnected = false;
        this.isConnecting = false;
        this.isMuted = false;
        this.currentChannelId = null;
        this.currentChannelName = null;
        this.currentUid = null;
        this.currentToken = null;
        this.tokenExpiresAt = null;
        this.participants.clear();
        this.reconnectAttempts = 0;
        this.reconnectDelay = 2000;

        console.log('Cleanup complete');
    }

    /**
     * Show error notification to user
     *
     * @param {string} message - Error message
     */
    showError(message) {
        console.error('Voice Chat Error:', message);

        if (this.callbacks.onError) {
            this.callbacks.onError(message);
        } else {
            // Fallback to alert if no error handler registered
            alert('Voice Chat Error: ' + message);
        }
    }

    /**
     * Show notification to user
     *
     * @param {string} message - Notification message
     * @param {string} type - Notification type (success, info, warning, error)
     */
    showNotification(message, type = 'info') {
        console.log(`[${type.toUpperCase()}]`, message);

        if (this.callbacks.onNotification) {
            this.callbacks.onNotification(message, type);
        }
    }

    /**
     * Capitalize first letter of string
     *
     * @param {string} str - String to capitalize
     * @returns {string} Capitalized string
     */
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Get current connection info
     *
     * @returns {Object} Connection information
     */
    getConnectionInfo() {
        return {
            isConnected: this.isConnected,
            isConnecting: this.isConnecting,
            isMuted: this.isMuted,
            channelId: this.currentChannelId,
            channelName: this.currentChannelName,
            uid: this.currentUid,
            participantCount: this.participants.size,
            networkQuality: this.networkQuality,
            tokenExpiresAt: this.tokenExpiresAt ? new Date(this.tokenExpiresAt * 1000) : null
        };
    }
}

// Export for use in other modules
export default VoiceChat;

// Also make available globally on window for easy access
window.VoiceChat = VoiceChat;

console.log('Voice Chat module loaded');
