{{--
    Voice Connected Panel Component (Phase 5)

    A Discord-inspired voice control panel with:
    - Channel info and connection status
    - Connection quality bars (5 bars like mobile signal)
    - Control buttons (mute, deafen, settings)
    - Disconnect button
    - Connection stats popup
    - Call timer

    Props:
    - server: Server model
    - channel: Current channel (optional)
--}}

@props(['server', 'channel' => null])

<div
    id="voice-panel"
    class="voice-panel"
    x-data="voicePanelController()"
    x-show="isConnected || isConnecting"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="transform translate-y-full opacity-0"
    x-transition:enter-end="transform translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="transform translate-y-0 opacity-100"
    x-transition:leave-end="transform translate-y-full opacity-0"
    @voice-connected.window="handleVoiceConnected($event.detail)"
    @voice-disconnected.window="handleVoiceDisconnected()"
    @voice-quality-update.window="updateQuality($event.detail)"
    @voice-mute-changed.window="isMuted = $event.detail.isMuted"
    @voice-deafen-changed.window="isDeafened = $event.detail.isDeafened"
>
    <div class="voice-panel-inner">
        {{-- Left Section: Channel Info --}}
        <div class="voice-panel-info">
            {{-- Connection Status --}}
            <div class="voice-status">
                <span
                    class="status-indicator"
                    :class="{
                        'connected': connectionStatus === 'connected',
                        'connecting': connectionStatus === 'connecting',
                        'reconnecting': connectionStatus === 'reconnecting',
                        'failed': connectionStatus === 'failed'
                    }"
                ></span>
                <span class="status-text" x-text="statusText">Voice Connected</span>
            </div>

            {{-- Channel Name & Quality --}}
            <div class="voice-channel-info">
                <div class="channel-name-row">
                    {{-- Voice Icon --}}
                    <svg class="voice-icon" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                    <span class="channel-name" x-text="channelName">Voice Channel</span>

                    {{-- Connection Quality Bars --}}
                    <div
                        class="connection-quality"
                        @click="showStats = !showStats"
                        :title="'Ping: ' + ping + 'ms'"
                    >
                        <div class="quality-bars" :data-quality="quality">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="ping-text" x-text="ping + 'ms'" x-show="ping > 0"></span>
                    </div>
                </div>

                {{-- Timer --}}
                <div class="call-timer" x-show="callDuration > 0">
                    <svg class="timer-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="formatDuration(callDuration)">00:00</span>
                </div>
            </div>

            {{-- Connection Stats Popup --}}
            <div
                class="voice-stats-popup"
                x-show="showStats"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                @click.outside="showStats = false"
            >
                <div class="stats-header">
                    <span>Connection Statistics</span>
                    <button class="stats-close" @click="showStats = false">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Ping</span>
                    <span class="stat-value" :class="{'good': ping < 50, 'medium': ping >= 50 && ping < 100, 'poor': ping >= 100}" x-text="ping + 'ms'">32ms</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Packet Loss</span>
                    <span class="stat-value" :class="{'good': packetLoss < 1, 'medium': packetLoss >= 1 && packetLoss < 5, 'poor': packetLoss >= 5}" x-text="packetLoss + '%'">0%</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Quality</span>
                    <span class="stat-value quality-badge" :class="quality" x-text="qualityText">Excellent</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Duration</span>
                    <span class="stat-value" x-text="formatDuration(callDuration)">00:00:00</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Server</span>
                    <span class="stat-value" x-text="serverName">{{ $server->name }}</span>
                </div>
            </div>
        </div>

        {{-- Right Section: Controls --}}
        <div class="voice-panel-controls">
            {{-- Control Buttons Row --}}
            <div class="control-buttons">
                {{-- Mute Button --}}
                <button
                    class="voice-btn"
                    :class="{ 'active': isMuted }"
                    @click="toggleMute()"
                    :title="isMuted ? 'Unmute (M)' : 'Mute (M)'"
                    :aria-label="isMuted ? 'Unmute microphone' : 'Mute microphone'"
                >
                    {{-- Mic Icon --}}
                    <svg x-show="!isMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    {{-- Mic Off Icon --}}
                    <svg x-show="isMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                    </svg>
                </button>

                {{-- Deafen Button --}}
                <button
                    class="voice-btn"
                    :class="{ 'active': isDeafened }"
                    @click="toggleDeafen()"
                    :title="isDeafened ? 'Undeafen (D)' : 'Deafen (D)'"
                    :aria-label="isDeafened ? 'Undeafen audio' : 'Deafen audio'"
                >
                    {{-- Headphones Icon --}}
                    <svg x-show="!isDeafened" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    {{-- Headphones Off Icon --}}
                    <svg x-show="isDeafened" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                    </svg>
                </button>

                {{-- Settings Button --}}
                <button
                    class="voice-btn"
                    @click="$dispatch('open-voice-settings')"
                    title="Voice Settings"
                    aria-label="Open voice settings"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Disconnect Button --}}
            <button
                class="voice-btn disconnect"
                @click="disconnect()"
                title="Disconnect"
                aria-label="Disconnect from voice channel"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
/**
 * Voice Panel Controller - Alpine.js Component (Phase 5)
 * Manages the voice connected panel state and interactions
 */
function voicePanelController() {
    return {
        // Connection state
        isConnected: false,
        isConnecting: false,
        connectionStatus: 'disconnected', // connected, connecting, reconnecting, failed
        statusText: 'Voice Connected',

        // Channel info
        channelId: null,
        channelName: 'Voice Channel',
        serverName: '{{ $server->name }}',

        // Audio state
        isMuted: false,
        isDeafened: false,

        // Quality metrics
        quality: 'excellent', // excellent, good, poor, bad
        qualityText: 'Excellent',
        ping: 0,
        packetLoss: 0,

        // UI state
        showStats: false,

        // Timer
        callDuration: 0,
        timerInterval: null,
        startTime: null,

        /**
         * Initialize the component
         */
        init() {
            // Listen for keyboard shortcuts
            document.addEventListener('keydown', this.handleKeyboardShortcut.bind(this));

            // Check if already connected to voice
            this.checkExistingConnection();
        },

        /**
         * Check for existing voice connection
         */
        checkExistingConnection() {
            if (window.voiceChat && window.voiceChat.isConnected) {
                this.isConnected = true;
                this.connectionStatus = 'connected';
                this.channelName = window.voiceChat.currentChannelName || 'Voice Channel';
                this.channelId = window.voiceChat.currentChannelId;
                this.isMuted = window.voiceChat.isMuted || false;
                this.isDeafened = window.voiceChat.isDeafened || false;
                this.startTimer();
            }
        },

        /**
         * Handle voice connected event
         */
        handleVoiceConnected(detail) {
            this.isConnected = true;
            this.isConnecting = false;
            this.connectionStatus = 'connected';
            this.statusText = 'Voice Connected';
            this.channelId = detail.channelId;
            this.channelName = detail.channelName || 'Voice Channel';
            this.isMuted = false;
            this.isDeafened = false;
            this.startTimer();
        },

        /**
         * Handle voice disconnected event
         */
        handleVoiceDisconnected() {
            this.isConnected = false;
            this.isConnecting = false;
            this.connectionStatus = 'disconnected';
            this.statusText = 'Disconnected';
            this.stopTimer();
            this.resetState();
        },

        /**
         * Update connection quality
         */
        updateQuality(detail) {
            this.ping = detail.ping || 0;
            this.packetLoss = detail.packetLoss || 0;

            // Determine quality based on ping
            if (this.ping < 50) {
                this.quality = 'excellent';
                this.qualityText = 'Excellent';
            } else if (this.ping < 100) {
                this.quality = 'good';
                this.qualityText = 'Good';
            } else if (this.ping < 200) {
                this.quality = 'poor';
                this.qualityText = 'Poor';
            } else {
                this.quality = 'bad';
                this.qualityText = 'Bad';
            }
        },

        /**
         * Toggle microphone mute
         */
        async toggleMute() {
            if (window.voiceChat) {
                this.isMuted = await window.voiceChat.toggleMute();
            } else {
                // Fallback for legacy implementation
                this.isMuted = !this.isMuted;
                if (typeof window.toggleMute === 'function') {
                    window.toggleMute();
                }
            }

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('voice-mute-changed', {
                detail: { isMuted: this.isMuted }
            }));
        },

        /**
         * Toggle audio deafen
         */
        async toggleDeafen() {
            if (window.voiceChat) {
                this.isDeafened = await window.voiceChat.toggleDeafen();
                // When deafening, also mute
                if (this.isDeafened && !this.isMuted) {
                    this.isMuted = true;
                }
            } else {
                // Fallback toggle
                this.isDeafened = !this.isDeafened;
                if (this.isDeafened) {
                    this.isMuted = true;
                }
            }

            // Dispatch events for other components
            window.dispatchEvent(new CustomEvent('voice-deafen-changed', {
                detail: { isDeafened: this.isDeafened }
            }));
            window.dispatchEvent(new CustomEvent('voice-mute-changed', {
                detail: { isMuted: this.isMuted }
            }));
        },

        /**
         * Disconnect from voice channel
         */
        async disconnect() {
            if (window.voiceChat) {
                await window.voiceChat.leaveChannel();
            } else if (typeof window.disconnectVoice === 'function') {
                window.disconnectVoice();
            }

            this.handleVoiceDisconnected();

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('voice-disconnected'));
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcut(event) {
            // Don't trigger if typing in an input
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                return;
            }

            if (!this.isConnected) return;

            switch (event.key.toLowerCase()) {
                case 'm':
                    event.preventDefault();
                    this.toggleMute();
                    break;
                case 'd':
                    event.preventDefault();
                    this.toggleDeafen();
                    break;
            }
        },

        /**
         * Start call timer
         */
        startTimer() {
            this.startTime = Date.now();
            this.callDuration = 0;

            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }

            this.timerInterval = setInterval(() => {
                this.callDuration = Math.floor((Date.now() - this.startTime) / 1000);
            }, 1000);
        },

        /**
         * Stop call timer
         */
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        /**
         * Format duration as HH:MM:SS or MM:SS
         */
        formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        /**
         * Reset state to defaults
         */
        resetState() {
            this.channelId = null;
            this.channelName = 'Voice Channel';
            this.isMuted = false;
            this.isDeafened = false;
            this.quality = 'excellent';
            this.qualityText = 'Excellent';
            this.ping = 0;
            this.packetLoss = 0;
            this.callDuration = 0;
            this.showStats = false;
        }
    };
}
</script>
@endPushOnce
