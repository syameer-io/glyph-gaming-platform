import './bootstrap';
import './gaming-status';
import './realtime-phase3';
import './live-matchmaking';
import './lobby-manager';
import { Chart, registerables } from 'chart.js';

// Register Chart.js components globally
Chart.register(...registerables);
window.Chart = Chart;
