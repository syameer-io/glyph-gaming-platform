{{--
    Voice Settings Popup Component (Phase 5)

    Quick access voice settings modal with:
    - Input device selector
    - Output device selector
    - Input sensitivity slider
    - Output volume slider
    - Link to full voice settings

    Props: None (uses Alpine.js data)
--}}

<div
    x-data="voiceSettingsPopup()"
    x-show="isOpen"
    x-cloak
    @open-voice-settings.window="openSettings()"
    @keydown.escape.window="closeSettings()"
    class="voice-settings-overlay"
    @click.self="closeSettings()"
>
    <div
        class="voice-settings-popup"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
    >
        {{-- Header --}}
        <div class="settings-header">
            <h3 class="settings-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Voice Settings
            </h3>
            <button class="settings-close-btn" @click="closeSettings()" title="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="settings-content">
            {{-- Input Device --}}
            <div class="settings-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    Input Device
                </label>
                <select
                    class="settings-select"
                    x-model="inputDevice"
                    @change="changeInputDevice()"
                >
                    <template x-for="device in inputDevices" :key="device.deviceId">
                        <option :value="device.deviceId" x-text="device.label || 'Microphone ' + (inputDevices.indexOf(device) + 1)"></option>
                    </template>
                </select>
                <div class="input-level-meter">
                    <div class="input-level-bar" :style="{ width: inputLevel + '%' }"></div>
                </div>
            </div>

            {{-- Output Device --}}
            <div class="settings-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    Output Device
                </label>
                <select
                    class="settings-select"
                    x-model="outputDevice"
                    @change="changeOutputDevice()"
                >
                    <template x-for="device in outputDevices" :key="device.deviceId">
                        <option :value="device.deviceId" x-text="device.label || 'Speaker ' + (outputDevices.indexOf(device) + 1)"></option>
                    </template>
                </select>
            </div>

            {{-- Input Volume --}}
            <div class="settings-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    Input Volume
                    <span class="volume-value" x-text="inputVolume + '%'"></span>
                </label>
                <input
                    type="range"
                    class="settings-slider"
                    min="0"
                    max="100"
                    x-model="inputVolume"
                    @change="changeInputVolume()"
                >
            </div>

            {{-- Output Volume --}}
            <div class="settings-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    Output Volume
                    <span class="volume-value" x-text="outputVolume + '%'"></span>
                </label>
                <input
                    type="range"
                    class="settings-slider"
                    min="0"
                    max="100"
                    x-model="outputVolume"
                    @change="changeOutputVolume()"
                >
            </div>

            {{-- Noise Suppression Toggle --}}
            <div class="settings-group toggle-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Noise Suppression
                </label>
                <button
                    class="toggle-btn"
                    :class="{ 'active': noiseSuppression }"
                    @click="toggleNoiseSuppression()"
                >
                    <span class="toggle-slider"></span>
                </button>
            </div>

            {{-- Echo Cancellation Toggle --}}
            <div class="settings-group toggle-group">
                <label class="settings-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                    Echo Cancellation
                </label>
                <button
                    class="toggle-btn"
                    :class="{ 'active': echoCancellation }"
                    @click="toggleEchoCancellation()"
                >
                    <span class="toggle-slider"></span>
                </button>
            </div>
        </div>

    </div>
</div>

@pushOnce('scripts')
<script>
/**
 * Voice Settings Popup Controller - Alpine.js Component (Phase 5)
 * Manages quick voice settings access
 */
function voiceSettingsPopup() {
    return {
        isOpen: false,

        // Device lists
        inputDevices: [],
        outputDevices: [],

        // Selected devices
        inputDevice: '',
        outputDevice: '',

        // Volume levels
        inputVolume: 100,
        outputVolume: 100,
        inputLevel: 0,

        // Audio processing
        noiseSuppression: true,
        echoCancellation: true,

        // Audio level monitoring
        audioContext: null,
        analyser: null,
        mediaStream: null,

        /**
         * Open settings popup
         */
        async openSettings() {
            this.isOpen = true;
            await this.loadDevices();
            this.loadSavedSettings();
            this.startInputMonitoring();
        },

        /**
         * Close settings popup
         */
        closeSettings() {
            this.isOpen = false;
            this.stopInputMonitoring();
        },

        /**
         * Load available audio devices
         */
        async loadDevices() {
            try {
                // Request permission first
                await navigator.mediaDevices.getUserMedia({ audio: true });

                const devices = await navigator.mediaDevices.enumerateDevices();

                this.inputDevices = devices.filter(d => d.kind === 'audioinput');
                this.outputDevices = devices.filter(d => d.kind === 'audiooutput');

                // Set defaults if not already set
                if (!this.inputDevice && this.inputDevices.length > 0) {
                    this.inputDevice = this.inputDevices[0].deviceId;
                }
                if (!this.outputDevice && this.outputDevices.length > 0) {
                    this.outputDevice = this.outputDevices[0].deviceId;
                }
            } catch (error) {
                console.warn('Failed to enumerate audio devices:', error);
            }
        },

        /**
         * Load saved settings from localStorage
         */
        loadSavedSettings() {
            const saved = localStorage.getItem('voiceSettings');
            if (saved) {
                try {
                    const settings = JSON.parse(saved);
                    this.inputDevice = settings.inputDevice || this.inputDevice;
                    this.outputDevice = settings.outputDevice || this.outputDevice;
                    this.inputVolume = settings.inputVolume ?? 100;
                    this.outputVolume = settings.outputVolume ?? 100;
                    this.noiseSuppression = settings.noiseSuppression ?? true;
                    this.echoCancellation = settings.echoCancellation ?? true;
                } catch (e) {
                    console.warn('Failed to load voice settings:', e);
                }
            }
        },

        /**
         * Save settings to localStorage
         */
        saveSettings() {
            localStorage.setItem('voiceSettings', JSON.stringify({
                inputDevice: this.inputDevice,
                outputDevice: this.outputDevice,
                inputVolume: this.inputVolume,
                outputVolume: this.outputVolume,
                noiseSuppression: this.noiseSuppression,
                echoCancellation: this.echoCancellation
            }));
        },

        /**
         * Change input device
         */
        async changeInputDevice() {
            this.saveSettings();
            // Restart input monitoring with new device
            this.stopInputMonitoring();
            await this.startInputMonitoring();

            // Notify voice chat system
            if (window.voiceChat && typeof window.voiceChat.setInputDevice === 'function') {
                await window.voiceChat.setInputDevice(this.inputDevice);
            }
        },

        /**
         * Change output device
         */
        async changeOutputDevice() {
            this.saveSettings();

            // Notify voice chat system
            if (window.voiceChat && typeof window.voiceChat.setOutputDevice === 'function') {
                await window.voiceChat.setOutputDevice(this.outputDevice);
            }
        },

        /**
         * Change input volume
         */
        changeInputVolume() {
            this.saveSettings();

            if (window.voiceChat && typeof window.voiceChat.setInputVolume === 'function') {
                window.voiceChat.setInputVolume(this.inputVolume / 100);
            }
        },

        /**
         * Change output volume
         */
        changeOutputVolume() {
            this.saveSettings();

            if (window.voiceChat && typeof window.voiceChat.setOutputVolume === 'function') {
                window.voiceChat.setOutputVolume(this.outputVolume / 100);
            }
        },

        /**
         * Toggle noise suppression
         * Calls VoiceChat method which will recreate audio track if needed
         */
        async toggleNoiseSuppression() {
            this.noiseSuppression = !this.noiseSuppression;
            this.saveSettings();

            // Call voiceChat method if available (will recreate track during call)
            if (window.voiceChat && typeof window.voiceChat.setNoiseSuppression === 'function') {
                await window.voiceChat.setNoiseSuppression(this.noiseSuppression);
            }
        },

        /**
         * Toggle echo cancellation
         * Calls VoiceChat method which will recreate audio track if needed
         */
        async toggleEchoCancellation() {
            this.echoCancellation = !this.echoCancellation;
            this.saveSettings();

            // Call voiceChat method if available (will recreate track during call)
            if (window.voiceChat && typeof window.voiceChat.setEchoCancellation === 'function') {
                await window.voiceChat.setEchoCancellation(this.echoCancellation);
            }
        },

        /**
         * Start monitoring input level
         */
        async startInputMonitoring() {
            try {
                this.mediaStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        deviceId: this.inputDevice ? { exact: this.inputDevice } : undefined
                    }
                });

                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.audioContext.createMediaStreamSource(this.mediaStream);
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 256;

                source.connect(this.analyser);

                this.monitorLevel();
            } catch (error) {
                console.warn('Failed to start input monitoring:', error);
            }
        },

        /**
         * Monitor input level continuously
         */
        monitorLevel() {
            if (!this.analyser || !this.isOpen) return;

            const dataArray = new Uint8Array(this.analyser.frequencyBinCount);
            this.analyser.getByteFrequencyData(dataArray);

            // Calculate average volume
            const average = dataArray.reduce((sum, val) => sum + val, 0) / dataArray.length;
            this.inputLevel = Math.min(100, (average / 128) * 100);

            requestAnimationFrame(() => this.monitorLevel());
        },

        /**
         * Stop input monitoring
         */
        stopInputMonitoring() {
            if (this.mediaStream) {
                this.mediaStream.getTracks().forEach(track => track.stop());
                this.mediaStream = null;
            }
            if (this.audioContext) {
                this.audioContext.close();
                this.audioContext = null;
            }
            this.analyser = null;
            this.inputLevel = 0;
        }
    };
}
</script>
@endPushOnce
