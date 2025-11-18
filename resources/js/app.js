import './bootstrap';
import './gaming-status';
import './realtime-phase3';
import './live-matchmaking';
import './lobby-manager';
import { Chart, registerables } from 'chart.js';

// Import utility modules
import clipboardUtils from './utils/clipboard.js';
import countdownTimer from './components/countdown-timer.js';

// Register Chart.js components globally
Chart.register(...registerables);
window.Chart = Chart;

// Make utilities available globally
window.clipboardUtils = clipboardUtils;
window.countdownTimer = countdownTimer;

/**
 * Start Alpine.js
 * This must be called AFTER all Alpine components are registered
 */
window.Alpine.start();
