import './bootstrap';

// CRITICAL: Import Alpine components BEFORE starting Alpine
// This ensures all x-data components are registered before Alpine.start()
import './components/lobby-join-button.js';

// Import other modules
import './gaming-status';
import './realtime-phase3';
import './live-matchmaking';
import './lobby-manager';
import './lobby-page';
import './voice-sidebar';
import './navbar';
import { Chart, registerables } from 'chart.js';

// Import utility modules
import clipboardUtils from './utils/clipboard.js';
import toastNotification from './utils/toast.js';
import countdownTimer from './components/countdown-timer.js';

// Register Chart.js components globally
Chart.register(...registerables);
window.Chart = Chart;

// Make utilities available globally
window.clipboardUtils = clipboardUtils;
window.toastNotification = toastNotification;
window.countdownTimer = countdownTimer;

/**
 * Start Alpine.js
 * This must be called AFTER all Alpine components are registered
 */
console.log('[App] Starting Alpine.js...');
window.Alpine.start();
console.log('[App] ✅ Alpine.js started');
