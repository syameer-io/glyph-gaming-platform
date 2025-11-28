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

/**
 * Configure Agora SDK settings - MUST be done before creating any clients
 * This suppresses non-critical errors and CORS issues with stats collectors
 *
 * NOTE: The CORS errors from statscollector-*.agora.io and web-*.statscollector.sd-rtn.com
 * are Agora's telemetry/analytics system. These are NON-BLOCKING and don't affect voice
 * functionality. They appear because browsers block cross-origin requests to Agora's
 * stats servers. This is expected behavior in development environments.
 */

// Set log level to ERROR (3) to minimize console noise
// Levels: 0=DEBUG, 1=INFO, 2=WARNING, 3=ERROR, 4=NONE
AgoraRTC.setLogLevel(3);

// Disable log upload to prevent some CORS errors with statscollector-*.agora.io
// This is safe for development/production - logs are local only
AgoraRTC.disableLogUpload();

// Disable area-specific stats collection to reduce CORS errors
// These are optional telemetry features that don't affect core functionality
try {
    // Disable cloud proxy stats (reduces some network requests)
    AgoraRTC.setParameter('ENABLE_REPORT_DATACHANNEL', false);
    AgoraRTC.setParameter('ENABLE_INSTANT_ANALYTICS', false);
} catch (e) {
    // Parameters may not be available in all SDK versions - safe to ignore
}

console.log('[Agora SDK] Configured: log level=ERROR, telemetry reduced');

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
        this.isDeafened = false;
        this.isSpeaking = false;

        // Speaking detection debounce
        this.speakingDebounceTimer = null;
        this.lastSpeakingBroadcast = 0;
        this.speakingBroadcastInterval = 200; // Max 5 updates per second

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

        // Network stats for voice panel (Phase 5)
        this.lastPing = 0;
        this.lastPacketLoss = 0;

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

        // Check secure context on initialization and warn user
        this.warnIfInsecureContext();
    }

    /**
     * Check and warn user if running in insecure context
     * This runs on page load to give early warning
     */
    warnIfInsecureContext() {
        if (!window.isSecureContext) {
            console.warn(
                '%c Voice Chat Warning: Insecure Context Detected',
                'background: #ff6b6b; color: white; font-size: 14px; padding: 8px; border-radius: 4px;',
                '\n\nYour site is running on HTTP, but voice chat requires HTTPS to access the microphone.\n\n' +
                'Current URL:', window.location.href, '\n' +
                'Secure Context:', window.isSecureContext, '\n\n' +
                'To fix this issue:\n' +
                '1. Enable HTTPS in Laragon (Menu > Apache > SSL > Enabled)\n' +
                '2. Generate SSL certificate for your domain\n' +
                '3. Update APP_URL in .env to https://\n' +
                '4. Clear config cache: php artisan config:clear\n\n' +
                'See HTTPS_SETUP_GUIDE.md in project root for detailed instructions.\n\n' +
                'Alternative quick fix (development only): Access site via https://127.0.0.1'
            );

            // Show warning notification to user (delayed so UI is ready)
            setTimeout(() => {
                this.showNotification(
                    'Voice chat requires HTTPS to work. Please enable SSL in Laragon. See browser console for details.',
                    'warning'
                );
            }, 2000);
        } else {
            console.log(
                '%c Voice Chat: Secure Context Detected ✓',
                'background: #51cf66; color: white; font-size: 12px; padding: 4px; border-radius: 4px;',
                '\n\nSecure context is available. Voice chat should work correctly.\n' +
                'Current URL:', window.location.href
            );
        }
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
            // Create Agora client with VP8 codec
            // Using RTC mode for low-latency voice communication
            this.client = AgoraRTC.createClient({
                mode: 'rtc',
                codec: 'vp8',
            });

            // Setup client event listeners
            this.setupClientEvents();

            // Handle SDK exceptions - show user-friendly messages for critical errors
            // Non-critical errors (connection retries, etc.) are silently logged
            this.client.on('exception', (evt) => {
                if (evt.code === 'INVALID_PARAMS') {
                    console.error('[Agora] Critical error:', evt);
                    this.showError('Invalid voice chat parameters. Please try again.');
                } else if (evt.code === 'NOT_SUPPORTED') {
                    console.error('[Agora] Critical error:', evt);
                    this.showError('Your browser does not support voice chat. Please use Chrome, Edge, or Firefox.');
                } else {
                    // Non-critical: connection retries, network fluctuations, etc.
                    // These are handled internally by the SDK
                    console.debug('[Agora] Non-critical event:', evt.code, evt.msg);
                }
            });

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
        this.client.on('network-quality', async (stats) => {
            this.networkQuality.uplink = stats.uplinkNetworkQuality;
            this.networkQuality.downlink = stats.downlinkNetworkQuality;

            // Get RTT stats for voice panel (Phase 5)
            try {
                const rtcStats = this.client.getRTCStats();
                if (rtcStats) {
                    this.lastPing = rtcStats.RTT || 0;
                }
            } catch (e) {
                // Stats not available, use estimates
            }

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
                const isCurrentUser = volume.uid === this.currentUid || volume.uid === 0;

                // volume.level ranges from 0 to 100
                if (volume.level > 5) { // Speaking threshold
                    // Trigger speaking animation in UI
                    if (this.callbacks.onUserSpeaking) {
                        this.callbacks.onUserSpeaking(volume.uid, volume.level);
                    }

                    // Broadcast local user speaking status to server (debounced)
                    if (isCurrentUser && !this.isSpeaking) {
                        this.isSpeaking = true;
                        this.broadcastSpeakingStatus(true);
                    }

                    // Reset speaking timeout for current user
                    if (isCurrentUser) {
                        this.resetSpeakingTimeout();
                    }
                } else if (isCurrentUser && this.isSpeaking) {
                    // User stopped speaking (level dropped below threshold)
                    // Will be handled by timeout for smoother transition
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

        // Note: Exception handler is set up in initializeClient() to avoid duplicates

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

                // Dispatch event for voice panel (Phase 5)
                window.dispatchEvent(new CustomEvent('voice-connected', {
                    detail: {
                        channelId: this.currentChannelId,
                        channelName: this.currentChannelName
                    }
                }));

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

                // Dispatch event for voice panel (Phase 5)
                window.dispatchEvent(new CustomEvent('voice-disconnected'));

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

        // Dispatch event for voice panel (Phase 5)
        // Calculate ping based on RTT stats or estimate from quality
        const estimatedPing = quality.overall === 0 ? 0 :
                              quality.overall <= 2 ? 25 :
                              quality.overall <= 4 ? 80 : 150;

        window.dispatchEvent(new CustomEvent('voice-quality-update', {
            detail: {
                quality: quality.status,
                ping: this.lastPing || estimatedPing,
                packetLoss: this.lastPacketLoss || 0
            }
        }));

        // Show warning if quality is poor
        if (quality.overall >= 5 && this.isConnected) {
            this.showNotification('Poor network quality detected. Audio may be unstable.', 'warning');
        }
    }

    /**
     * Check if browser supports secure context for getUserMedia
     *
     * @returns {Object} Support status and error message
     */
    checkSecureContext() {
        // Check if running in secure context (HTTPS or localhost)
        if (!window.isSecureContext) {
            return {
                supported: false,
                error: 'Voice chat requires a secure connection (HTTPS). Your site is running on HTTP.\n\n' +
                       'To fix this:\n' +
                       '1. Enable HTTPS in Laragon (Menu > Apache > SSL > Enabled)\n' +
                       '2. Generate SSL certificate for your domain\n' +
                       '3. Update APP_URL in .env to use https://\n' +
                       '4. Clear config cache: php artisan config:clear\n\n' +
                       'See HTTPS_SETUP_GUIDE.md for detailed instructions.'
            };
        }

        // Check if getUserMedia API is available
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return {
                supported: false,
                error: 'Your browser does not support voice chat. Please use Chrome, Edge, Firefox, or Safari.'
            };
        }

        return { supported: true };
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

        // Check secure context BEFORE attempting to join
        const secureCheck = this.checkSecureContext();
        if (!secureCheck.supported) {
            console.error('Secure context check failed:', secureCheck.error);
            this.showError(secureCheck.error);
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

            // Enhanced error handling with specific messages
            if (error.code === 'NOT_SUPPORTED') {
                // This error occurs when getUserMedia is not available
                errorMessage = 'Voice chat is not supported in this context.\n\n' +
                              'This usually happens when:\n' +
                              '1. Site is running on HTTP instead of HTTPS\n' +
                              '2. Browser does not support WebRTC\n\n' +
                              'Solution: Enable HTTPS in Laragon.\n' +
                              'See HTTPS_SETUP_GUIDE.md for instructions.';
            } else if (error.code === 'PERMISSION_DENIED' || error.name === 'NotAllowedError') {
                errorMessage = 'Microphone permission denied.\n\n' +
                              'Please allow microphone access in your browser settings and try again.\n\n' +
                              'Chrome: Click the camera icon in address bar\n' +
                              'Edge: Site settings > Microphone > Allow';
            } else if (error.code === 'DEVICE_NOT_FOUND' || error.name === 'NotFoundError') {
                errorMessage = 'No microphone found.\n\n' +
                              'Please:\n' +
                              '1. Connect a microphone to your computer\n' +
                              '2. Ensure it is enabled in system settings\n' +
                              '3. Try again';
            } else if (error.code === 'NOT_READABLE' || error.name === 'NotReadableError') {
                errorMessage = 'Microphone is already in use by another application.\n\n' +
                              'Please close other apps using your microphone and try again.';
            } else if (error.code === 'INVALID_PARAMS') {
                errorMessage = 'Invalid channel parameters. Please contact support.';
            } else if (error.message) {
                errorMessage = error.message;
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
     * Toggle audio deafen (stop hearing others)
     *
     * @returns {Promise<boolean>} New deafen state
     */
    async toggleDeafen() {
        if (!this.isConnected) {
            console.warn('Not connected to voice channel');
            this.showNotification('You must be in a voice channel to deafen/undeafen', 'warning');
            return this.isDeafened;
        }

        try {
            const newDeafenState = !this.isDeafened;

            // Mute/unmute all remote audio tracks
            for (const user of this.client.remoteUsers) {
                if (user.audioTrack) {
                    if (newDeafenState) {
                        user.audioTrack.setVolume(0);
                    } else {
                        user.audioTrack.setVolume(100);
                    }
                }
            }

            this.isDeafened = newDeafenState;
            console.log('Audio', newDeafenState ? 'deafened' : 'undeafened');

            // Notify backend to update session deafen status
            await this.notifyBackendDeafenToggle(this.currentChannelId, newDeafenState);

            this.showNotification(
                newDeafenState ? 'Audio deafened' : 'Audio enabled',
                'info'
            );

            return newDeafenState;

        } catch (error) {
            console.error('Failed to toggle deafen:', error);
            this.showError('Failed to toggle audio. Please try again.');
            return this.isDeafened;
        }
    }

    /**
     * Notify backend of deafen toggle
     *
     * @param {number} channelId - Voice channel ID
     * @param {boolean} isDeafened - New deafen state
     */
    async notifyBackendDeafenToggle(channelId, isDeafened) {
        try {
            const response = await fetch('/voice/deafen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    channel_id: channelId,
                    is_deafened: isDeafened
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            console.log('Backend deafen status updated');

        } catch (error) {
            console.error('Failed to notify backend of deafen toggle:', error);
            // Don't show error to user, deafen still works locally
        }
    }

    /**
     * Broadcast speaking status to server (debounced)
     *
     * @param {boolean} isSpeaking - Whether user is speaking
     */
    broadcastSpeakingStatus(isSpeaking) {
        const now = Date.now();

        // Debounce: only broadcast if enough time has passed
        if (now - this.lastSpeakingBroadcast < this.speakingBroadcastInterval) {
            return;
        }

        this.lastSpeakingBroadcast = now;

        // Fire and forget - don't await
        fetch('/voice/speaking', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                channel_id: this.currentChannelId,
                is_speaking: isSpeaking
            })
        }).catch(error => {
            // Silently fail - speaking indicator is non-critical
            console.debug('Speaking status broadcast failed:', error);
        });
    }

    /**
     * Reset speaking timeout - called when user is actively speaking
     */
    resetSpeakingTimeout() {
        // Clear existing timeout
        if (this.speakingDebounceTimer) {
            clearTimeout(this.speakingDebounceTimer);
        }

        // Set timeout to mark as not speaking after 500ms of silence
        this.speakingDebounceTimer = setTimeout(() => {
            if (this.isSpeaking) {
                this.isSpeaking = false;
                this.broadcastSpeakingStatus(false);
            }
        }, 500);
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
            isDeafened: this.isDeafened,
            isSpeaking: this.isSpeaking,
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
