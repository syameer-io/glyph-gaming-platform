import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import Alpine from 'alpinejs';

window.axios = axios;
window.Alpine = Alpine;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

/**
 * Echo Initialization with Retry Logic and Error Handling
 *
 * Provides robust WebSocket connection management with:
 * - Automatic retry on connection failures
 * - Connection state monitoring
 * - Graceful degradation when WebSocket unavailable
 */

let echoInitAttempts = 0;
const MAX_ECHO_INIT_ATTEMPTS = 3;
const ECHO_RETRY_DELAY = 2000; // 2 seconds

function initializeEcho() {
    try {
        echoInitAttempts++;

        console.log(`[Echo] Initializing WebSocket connection (attempt ${echoInitAttempts}/${MAX_ECHO_INIT_ATTEMPTS})...`);

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            }
        });

        // Monitor connection state
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;

            pusher.connection.bind('connected', () => {
                console.log('[Echo] ✅ WebSocket connected successfully');
                echoInitAttempts = 0; // Reset attempts on successful connection

                // Dispatch custom event for app to know Echo is ready
                window.dispatchEvent(new CustomEvent('echo:connected'));
            });

            pusher.connection.bind('disconnected', () => {
                console.warn('[Echo] ⚠️ WebSocket disconnected');
                window.dispatchEvent(new CustomEvent('echo:disconnected'));
            });

            pusher.connection.bind('unavailable', () => {
                console.error('[Echo] ❌ WebSocket unavailable');
                window.dispatchEvent(new CustomEvent('echo:unavailable'));
            });

            pusher.connection.bind('failed', () => {
                console.error('[Echo] ❌ WebSocket connection failed');
                handleEchoConnectionFailure();
            });

            pusher.connection.bind('error', (error) => {
                console.error('[Echo] ❌ WebSocket error:', error);
                handleEchoConnectionFailure();
            });
        }

        console.log('[Echo] ✅ Echo instance created');

    } catch (error) {
        console.error('[Echo] ❌ Failed to initialize Echo:', error);
        handleEchoConnectionFailure();
    }
}

function handleEchoConnectionFailure() {
    if (echoInitAttempts < MAX_ECHO_INIT_ATTEMPTS) {
        console.warn(`[Echo] 🔄 Retrying connection in ${ECHO_RETRY_DELAY / 1000}s...`);
        setTimeout(() => {
            initializeEcho();
        }, ECHO_RETRY_DELAY);
    } else {
        console.error('[Echo] ❌ Max connection attempts reached. Real-time features will be disabled.');
        window.dispatchEvent(new CustomEvent('echo:failed'));

        // Set window.Echo to null so app code can detect and handle gracefully
        window.Echo = null;
    }
}

// Initialize Echo
initializeEcho();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

/**
 * Note: Alpine.start() is called in app.js after all components are registered
 */
