/**
 * Theme Switcher Module
 * Handles light/dark theme toggling with instant switching, localStorage persistence,
 * and server-side persistence for logged-in users.
 */

const ThemeSwitcher = {
    STORAGE_KEY: 'glyph-theme',
    THEMES: ['dark', 'light'],
    currentTheme: 'dark',

    /**
     * Initialize theme from storage or server-rendered attribute
     */
    init() {
        // Theme should already be set by inline script in <head>
        // This just ensures consistency and sets up event listeners
        this.currentTheme = this.getTheme();

        // Sync localStorage with current theme
        localStorage.setItem(this.STORAGE_KEY, this.currentTheme);

        // Set up smooth transitions after initial load
        this.setupTransitions();

        // Log initialization in development
        if (process.env.NODE_ENV === 'development') {
            console.log('[ThemeSwitcher] Initialized with theme:', this.currentTheme);
        }
    },

    /**
     * Get current theme from DOM
     * @returns {string} Current theme ('dark' or 'light')
     */
    getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    },

    /**
     * Set theme with instant switch (no page reload)
     * @param {string} theme - Theme to apply ('dark' or 'light')
     */
    setTheme(theme) {
        if (!this.THEMES.includes(theme)) {
            console.error(`[ThemeSwitcher] Invalid theme: ${theme}`);
            return;
        }

        // Enable smooth transitions
        document.documentElement.classList.add('theme-transition');

        // Apply immediately to DOM
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;

        // Store in localStorage (for guests and FOUC prevention)
        localStorage.setItem(this.STORAGE_KEY, theme);

        // Save to server if user is logged in
        this.saveToServer(theme);

        // Dispatch custom event for any listeners
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme }
        }));

        // Remove transition class after animation completes
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transition');
        }, 300);
    },

    /**
     * Toggle between dark and light themes
     * @returns {string} The new theme
     */
    toggle() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        return newTheme;
    },

    /**
     * Save theme preference to server via AJAX
     * @param {string} theme - Theme to save
     */
    async saveToServer(theme) {
        // Check if user is authenticated by looking for the CSRF token and route availability
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            // User is likely not authenticated or CSRF token is missing
            return;
        }

        try {
            const response = await fetch('/settings/appearance', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ theme })
            });

            if (!response.ok) {
                // User might not be logged in - this is expected for guests
                if (response.status !== 401 && response.status !== 419) {
                    console.warn('[ThemeSwitcher] Failed to save theme preference to server');
                }
            }
        } catch (error) {
            // Network error or user not logged in - not critical
            if (process.env.NODE_ENV === 'development') {
                console.log('[ThemeSwitcher] Could not save to server (user may be guest):', error.message);
            }
        }
    },

    /**
     * Add smooth transition for theme changes
     * Only activates after initial page load to prevent FOUC
     */
    setupTransitions() {
        // Wait for page to fully load before enabling transitions
        if (document.readyState === 'complete') {
            this.enableTransitions();
        } else {
            window.addEventListener('load', () => this.enableTransitions());
        }
    },

    enableTransitions() {
        // Add a small delay to ensure all styles are applied
        setTimeout(() => {
            document.body.style.setProperty('--theme-transition-enabled', '1');
        }, 100);
    },

    /**
     * Get the stored theme from localStorage
     * @returns {string|null} Stored theme or null
     */
    getStoredTheme() {
        return localStorage.getItem(this.STORAGE_KEY);
    },

    /**
     * Check if current theme is dark
     * @returns {boolean}
     */
    isDark() {
        return this.currentTheme === 'dark';
    },

    /**
     * Check if current theme is light
     * @returns {boolean}
     */
    isLight() {
        return this.currentTheme === 'light';
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeSwitcher.init());
} else {
    ThemeSwitcher.init();
}

// Make available globally
window.ThemeSwitcher = ThemeSwitcher;

// Export for module usage
export default ThemeSwitcher;
