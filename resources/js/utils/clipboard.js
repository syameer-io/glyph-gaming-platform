/**
 * Clipboard Utility
 *
 * Provides a cross-browser compatible clipboard functionality
 * with modern Clipboard API and legacy fallback support.
 */

/**
 * Copy text to clipboard
 *
 * @param {string} text - The text to copy
 * @returns {Promise<boolean>} - Resolves to true on success, false on failure
 */
export async function copyToClipboard(text) {
    if (!text) {
        return Promise.reject(new Error('No text provided'));
    }

    try {
        // Modern Clipboard API (preferred method)
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }

        // Fallback for older browsers
        return copyToClipboardLegacy(text);
    } catch (error) {
        console.error('Clipboard copy failed:', error);

        // Try legacy method as final fallback
        try {
            return copyToClipboardLegacy(text);
        } catch (fallbackError) {
            console.error('Legacy clipboard copy also failed:', fallbackError);
            return false;
        }
    }
}

/**
 * Legacy clipboard copy using document.execCommand
 *
 * @param {string} text - The text to copy
 * @returns {boolean} - True on success, false on failure
 */
function copyToClipboardLegacy(text) {
    const textarea = document.createElement('textarea');

    // Style to make it invisible
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.top = '-9999px';
    textarea.style.left = '-9999px';
    textarea.style.opacity = '0';
    textarea.setAttribute('readonly', '');

    document.body.appendChild(textarea);

    try {
        // Select the text
        textarea.select();
        textarea.setSelectionRange(0, text.length);

        // Copy to clipboard
        const successful = document.execCommand('copy');

        // Clean up
        document.body.removeChild(textarea);

        if (!successful) {
            throw new Error('execCommand failed');
        }

        return true;
    } catch (error) {
        // Clean up even on error
        if (document.body.contains(textarea)) {
            document.body.removeChild(textarea);
        }

        throw error;
    }
}

/**
 * Check if clipboard functionality is available
 *
 * @returns {boolean} - True if clipboard is supported
 */
export function isClipboardSupported() {
    return (
        (navigator.clipboard && navigator.clipboard.writeText) ||
        document.queryCommandSupported('copy')
    );
}

/**
 * Read text from clipboard (requires user permission)
 *
 * @returns {Promise<string>} - The clipboard text
 */
export async function readFromClipboard() {
    if (!navigator.clipboard || !navigator.clipboard.readText) {
        throw new Error('Clipboard read not supported');
    }

    try {
        const text = await navigator.clipboard.readText();
        return text;
    } catch (error) {
        console.error('Failed to read clipboard:', error);
        throw error;
    }
}

// Make available globally for non-module scripts
if (typeof window !== 'undefined') {
    window.clipboardUtils = {
        copy: copyToClipboard,
        isSupported: isClipboardSupported,
        read: readFromClipboard
    };
}

export default {
    copy: copyToClipboard,
    isSupported: isClipboardSupported,
    read: readFromClipboard
};
