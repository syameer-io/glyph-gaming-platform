/**
 * Countdown Timer Component for Alpine.js
 *
 * Provides real-time countdown functionality for lobby expiration times.
 * Updates every second and shows visual indicators when time is running low.
 */

/**
 * Format countdown time remaining from a timestamp
 *
 * @param {string|Date} expiresAt - The expiration timestamp
 * @returns {Object} - Countdown data object
 */
export function getCountdownData(expiresAt) {
    if (!expiresAt) {
        return {
            isExpired: false,
            hasExpiration: false,
            totalSeconds: null,
            minutes: null,
            seconds: null,
            hours: null,
            formatted: 'No expiration',
            isUrgent: false, // < 5 minutes
            isCritical: false // < 1 minute
        };
    }

    const now = new Date();
    const expires = new Date(expiresAt);
    const diff = expires - now;

    if (diff <= 0) {
        return {
            isExpired: true,
            hasExpiration: true,
            totalSeconds: 0,
            minutes: 0,
            seconds: 0,
            hours: 0,
            formatted: 'Expired',
            isUrgent: true,
            isCritical: true
        };
    }

    const totalSeconds = Math.floor(diff / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    // Format the display string
    let formatted;
    if (hours > 0) {
        formatted = `${hours}h ${minutes}m remaining`;
    } else if (minutes > 0) {
        formatted = `${minutes}m ${seconds}s remaining`;
    } else {
        formatted = `${seconds}s remaining`;
    }

    return {
        isExpired: false,
        hasExpiration: true,
        totalSeconds,
        minutes: minutes + (hours * 60), // Total minutes
        seconds,
        hours,
        formatted,
        isUrgent: (totalSeconds <= 300), // 5 minutes
        isCritical: (totalSeconds <= 60) // 1 minute
    };
}

/**
 * Get CSS color for countdown based on time remaining
 *
 * @param {Object} countdownData - Data from getCountdownData()
 * @returns {string} - CSS color value
 */
export function getCountdownColor(countdownData) {
    if (!countdownData.hasExpiration) {
        return '#10b981'; // Green for persistent
    }

    if (countdownData.isExpired) {
        return '#71717a'; // Gray for expired
    }

    if (countdownData.isCritical) {
        return '#ef4444'; // Red for < 1 minute
    }

    if (countdownData.isUrgent) {
        return '#f59e0b'; // Orange for < 5 minutes
    }

    return '#71717a'; // Default gray
}

/**
 * Get icon SVG based on countdown state
 *
 * @param {Object} countdownData - Data from getCountdownData()
 * @returns {string} - SVG icon name
 */
export function getCountdownIcon(countdownData) {
    if (!countdownData.hasExpiration) {
        return 'check-circle'; // Persistent lobby
    }

    if (countdownData.isExpired) {
        return 'x-circle'; // Expired
    }

    if (countdownData.isCritical) {
        return 'exclamation-circle'; // Critical
    }

    return 'clock'; // Normal countdown
}

/**
 * Alpine.js countdown component factory
 *
 * Usage in Alpine component:
 * x-data="{ countdown: countdownTimer('2024-11-16T10:30:00Z') }"
 * x-text="countdown.formatted"
 *
 * @param {string|Date} expiresAt - The expiration timestamp
 * @returns {Object} - Alpine.js reactive data object
 */
export function countdownTimer(expiresAt) {
    return {
        expiresAt,
        countdown: getCountdownData(expiresAt),
        interval: null,

        init() {
            // Initial calculation
            this.updateCountdown();

            // Update every second
            this.interval = setInterval(() => {
                this.updateCountdown();

                // Stop updating if expired
                if (this.countdown.isExpired) {
                    this.stopTimer();
                }
            }, 1000);
        },

        updateCountdown() {
            this.countdown = getCountdownData(this.expiresAt);
        },

        stopTimer() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },

        destroy() {
            this.stopTimer();
        },

        // Computed properties
        get color() {
            return getCountdownColor(this.countdown);
        },

        get icon() {
            return getCountdownIcon(this.countdown);
        },

        get formatted() {
            return this.countdown.formatted;
        },

        get isExpired() {
            return this.countdown.isExpired;
        },

        get isUrgent() {
            return this.countdown.isUrgent;
        },

        get isCritical() {
            return this.countdown.isCritical;
        }
    };
}

/**
 * Simple countdown formatting (lightweight, no reactivity)
 * Use this when you just need formatting without Alpine.js component
 *
 * @param {string|Date} expiresAt - The expiration timestamp
 * @returns {string} - Formatted countdown string
 */
export function formatCountdown(expiresAt) {
    const data = getCountdownData(expiresAt);
    return data.formatted;
}

/**
 * Get minutes remaining (for conditional logic)
 *
 * @param {string|Date} expiresAt - The expiration timestamp
 * @returns {number|null} - Minutes remaining or null if no expiration
 */
export function getMinutesRemaining(expiresAt) {
    const data = getCountdownData(expiresAt);
    return data.hasExpiration ? data.minutes : null;
}

// Make available globally for non-module scripts
if (typeof window !== 'undefined') {
    window.countdownTimer = {
        component: countdownTimer,
        format: formatCountdown,
        getMinutes: getMinutesRemaining,
        getData: getCountdownData,
        getColor: getCountdownColor,
        getIcon: getCountdownIcon
    };
}

export default {
    component: countdownTimer,
    format: formatCountdown,
    getMinutes: getMinutesRemaining,
    getData: getCountdownData,
    getColor: getCountdownColor,
    getIcon: getCountdownIcon
};
