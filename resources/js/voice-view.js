/**
 * Voice Channel Main View JavaScript (Phase 6)
 *
 * Handles the full-screen voice channel experience with:
 * - Real-time user updates
 * - Speaking indicators
 * - Activity integration
 * - Modals and panels
 */

// Extend the existing voice chat functionality for the main view
document.addEventListener('DOMContentLoaded', function() {
    console.log('[VoiceView] Initializing voice channel main view');

    // Check if we're on the voice channel view page
    const voiceViewElement = document.querySelector('.voice-channel-page');
    if (!voiceViewElement) {
        return;
    }

    // Initialize keyboard shortcuts
    initKeyboardShortcuts();

    // Initialize drag and resize for text chat panel
    initTextChatResize();

    // Initialize user popover functionality
    initUserPopovers();
});

/**
 * Initialize keyboard shortcuts for the voice view
 */
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Don't trigger shortcuts when typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }

        // M - Toggle mute
        if (e.key === 'm' || e.key === 'M') {
            if (typeof window.toggleMute === 'function') {
                window.toggleMute();
            }
        }

        // D - Toggle deafen
        if (e.key === 'd' || e.key === 'D') {
            const deafenBtn = document.querySelector('.control-btn[title*="Deafen"]');
            if (deafenBtn) {
                deafenBtn.click();
            }
        }

        // Escape - Close modals/panels
        if (e.key === 'Escape') {
            // Dispatch event to close modals in Alpine
            window.dispatchEvent(new CustomEvent('close-modals'));
        }

        // T - Toggle text chat
        if (e.key === 't' || e.key === 'T') {
            const textChatBtn = document.querySelector('.voice-header-btn[title="Toggle Text Chat"]');
            if (textChatBtn) {
                textChatBtn.click();
            }
        }

        // I - Open invite modal
        if (e.key === 'i' || e.key === 'I') {
            const inviteBtn = document.querySelector('.voice-header-btn[title="Invite Friends"]');
            if (inviteBtn) {
                inviteBtn.click();
            }
        }
    });
}

/**
 * Initialize resize functionality for text chat panel
 */
function initTextChatResize() {
    const panel = document.querySelector('.voice-text-chat-panel');
    if (!panel) return;

    let isResizing = false;
    let startX;
    let startWidth;

    // Create resize handle
    const resizeHandle = document.createElement('div');
    resizeHandle.className = 'resize-handle';
    resizeHandle.style.cssText = `
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        cursor: ew-resize;
        z-index: 10;
    `;
    panel.prepend(resizeHandle);

    resizeHandle.addEventListener('mousedown', function(e) {
        isResizing = true;
        startX = e.clientX;
        startWidth = panel.offsetWidth;
        document.body.style.cursor = 'ew-resize';
        e.preventDefault();
    });

    document.addEventListener('mousemove', function(e) {
        if (!isResizing) return;

        const diff = startX - e.clientX;
        const newWidth = Math.max(280, Math.min(600, startWidth + diff));
        panel.style.width = newWidth + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (isResizing) {
            isResizing = false;
            document.body.style.cursor = '';
            // Save width preference
            localStorage.setItem('voiceTextChatWidth', panel.offsetWidth);
        }
    });

    // Restore saved width
    const savedWidth = localStorage.getItem('voiceTextChatWidth');
    if (savedWidth) {
        panel.style.width = savedWidth + 'px';
    }
}

/**
 * Initialize user popover/profile card functionality
 */
function initUserPopovers() {
    // Will be handled by Alpine.js component
    // This is a placeholder for future enhancement
}

/**
 * Format duration in seconds to human-readable string
 * @param {number} seconds - Duration in seconds
 * @returns {string} Formatted duration string
 */
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Voice View notification helper
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, info)
 */
function showVoiceViewNotification(message, type = 'info') {
    // Use the global showNotification if available
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    // Fallback notification
    const notification = document.createElement('div');
    notification.className = `voice-notification voice-notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 10000;
        padding: 14px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
        max-width: 360px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    `;

    // Set background color based on type
    const colors = {
        success: '#43b581',
        error: '#ed4245',
        info: '#667eea',
        warning: '#faa61a'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Animate in
    requestAnimationFrame(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    });

    // Remove after delay
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Make helper available globally
window.showVoiceViewNotification = showVoiceViewNotification;

/**
 * Update user card speaking state with visual feedback
 * @param {number} userId - User ID
 * @param {boolean} isSpeaking - Speaking state
 */
function updateUserSpeakingState(userId, isSpeaking) {
    const userCard = document.querySelector(`.voice-user-card[data-user-id="${userId}"]`);
    if (!userCard) return;

    const avatarWrapper = userCard.querySelector('.user-avatar-wrapper');
    const speakingRing = userCard.querySelector('.speaking-ring');

    if (isSpeaking) {
        userCard.classList.add('speaking');
        avatarWrapper?.classList.add('speaking');
        if (speakingRing) speakingRing.style.display = 'block';
    } else {
        userCard.classList.remove('speaking');
        avatarWrapper?.classList.remove('speaking');
        if (speakingRing) speakingRing.style.display = 'none';
    }
}

// Make available globally
window.updateUserSpeakingState = updateUserSpeakingState;

/**
 * Handle Steam activity integration
 * Fetch current game status from user profiles
 */
async function fetchUserActivities(userIds) {
    try {
        // This would integrate with the existing Steam API service
        // For now, return mock data
        console.log('[VoiceView] Fetching activities for users:', userIds);
        return {};
    } catch (error) {
        console.error('[VoiceView] Failed to fetch user activities:', error);
        return {};
    }
}

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatDuration,
        showVoiceViewNotification,
        updateUserSpeakingState,
        fetchUserActivities
    };
}
