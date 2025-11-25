/**
 * Global Toast Notification Utility
 *
 * Provides a centralized, cross-browser compatible toast notification system.
 * Used throughout the application for user feedback.
 *
 * Features:
 * - Multiple notification types (success, info, warning, error)
 * - Configurable duration
 * - Auto-stacking of multiple notifications
 * - Smooth animations
 * - Mobile-responsive
 * - Accessible (ARIA labels)
 *
 * @author Glyph Development Team
 * @version 1.0.0
 */

/**
 * Icon mapping for notification types
 */
const TOAST_ICONS = {
    success: 'check-circle',
    info: 'info-circle',
    warning: 'exclamation-triangle',
    error: 'times-circle',
};

/**
 * Default colors for notification types (Tailwind-compatible)
 */
const TOAST_COLORS = {
    success: {
        bg: 'bg-green-600',
        icon: 'text-green-400',
        text: 'text-white',
    },
    info: {
        bg: 'bg-blue-600',
        icon: 'text-blue-400',
        text: 'text-white',
    },
    warning: {
        bg: 'bg-yellow-600',
        icon: 'text-yellow-400',
        text: 'text-white',
    },
    error: {
        bg: 'bg-red-600',
        icon: 'text-red-400',
        text: 'text-white',
    },
};

/**
 * Get or create the toast container element
 * @returns {HTMLElement} Toast container element
 */
function getToastContainer() {
    let container = document.querySelector('.toast-container');

    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
    }

    return container;
}

/**
 * Create and show a toast notification
 *
 * @param {string} message - The notification message
 * @param {string} type - Notification type: 'success', 'info', 'warning', 'error'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @returns {HTMLElement} The created toast element
 */
export function showGlobalToast(message, type = 'info', duration = 3000) {
    if (!message) {
        console.warn('[Toast] Cannot show toast with empty message');
        return null;
    }

    // Validate type
    if (!TOAST_ICONS[type]) {
        console.warn(`[Toast] Invalid type "${type}", defaulting to "info"`);
        type = 'info';
    }

    console.log(`[Toast] Showing ${type} notification:`, message);

    // Get container
    const container = getToastContainer();

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');

    // Get color scheme
    const colors = TOAST_COLORS[type];
    const icon = TOAST_ICONS[type];

    // Create toast HTML structure
    toast.innerHTML = `
        <div class="toast-content ${colors.bg} ${colors.text}">
            <div class="toast-icon ${colors.icon}">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="toast-message">
                ${escapeHtml(message)}
            </div>
            <button class="toast-close ${colors.text}" aria-label="Close notification">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Add close button handler
    const closeButton = toast.querySelector('.toast-close');
    closeButton.addEventListener('click', () => {
        dismissToast(toast);
    });

    // Add to container
    container.appendChild(toast);

    // Trigger entrance animation (next frame)
    requestAnimationFrame(() => {
        toast.classList.add('toast-show');
    });

    // Auto-dismiss after duration
    const timeoutId = setTimeout(() => {
        dismissToast(toast);
    }, duration);

    // Store timeout ID for potential cancellation
    toast.dataset.timeoutId = timeoutId;

    return toast;
}

/**
 * Dismiss a toast notification
 * @param {HTMLElement} toast - Toast element to dismiss
 */
function dismissToast(toast) {
    if (!toast || !toast.parentElement) {
        return;
    }

    // Clear timeout if exists
    if (toast.dataset.timeoutId) {
        clearTimeout(parseInt(toast.dataset.timeoutId));
    }

    // Remove show class to trigger exit animation
    toast.classList.remove('toast-show');
    toast.classList.add('toast-hide');

    // Remove from DOM after animation
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 300); // Match CSS transition duration
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show success notification
 * @param {string} message - Success message
 * @param {number} duration - Duration in milliseconds
 */
export function showSuccess(message, duration = 3000) {
    return showGlobalToast(message, 'success', duration);
}

/**
 * Show info notification
 * @param {string} message - Info message
 * @param {number} duration - Duration in milliseconds
 */
export function showInfo(message, duration = 3000) {
    return showGlobalToast(message, 'info', duration);
}

/**
 * Show warning notification
 * @param {string} message - Warning message
 * @param {number} duration - Duration in milliseconds
 */
export function showWarning(message, duration = 3000) {
    return showGlobalToast(message, 'warning', duration);
}

/**
 * Show error notification
 * @param {string} message - Error message
 * @param {number} duration - Duration in milliseconds (default: 5000 for errors)
 */
export function showError(message, duration = 5000) {
    return showGlobalToast(message, 'error', duration);
}

/**
 * Clear all toast notifications
 */
export function clearAllToasts() {
    const container = document.querySelector('.toast-container');
    if (container) {
        const toasts = container.querySelectorAll('.toast');
        toasts.forEach(toast => dismissToast(toast));
    }
}

// Make available globally for non-module scripts
if (typeof window !== 'undefined') {
    window.showGlobalToast = showGlobalToast;
    window.toastNotification = {
        show: showGlobalToast,
        success: showSuccess,
        info: showInfo,
        warning: showWarning,
        error: showError,
        clear: clearAllToasts,
    };
}

// Default export
export default {
    show: showGlobalToast,
    success: showSuccess,
    info: showInfo,
    warning: showWarning,
    error: showError,
    clear: clearAllToasts,
};

console.log('[Toast] Global toast notification system loaded');
