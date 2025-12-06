/**
 * Navbar Component JavaScript
 * Handles dropdown toggles, mobile drawer, and keyboard navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    initNavbarDropdowns();
    initMobileDrawer();
    initKeyboardNavigation();
});

/**
 * Initialize dropdown functionality
 */
function initNavbarDropdowns() {
    const dropdowns = document.querySelectorAll('.navbar-dropdown');

    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.navbar-dropdown-trigger, .navbar-user-trigger');

        if (!trigger) return;

        // Toggle dropdown on click
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();

            // Close other dropdowns
            dropdowns.forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('open');
                }
            });

            // Toggle current dropdown
            dropdown.classList.toggle('open');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.navbar-dropdown')) {
            dropdowns.forEach(d => d.classList.remove('open'));
        }
    });
}

/**
 * Initialize mobile drawer functionality
 */
function initMobileDrawer() {
    const hamburger = document.querySelector('.navbar-hamburger');
    const drawer = document.querySelector('.mobile-drawer');
    const overlay = document.querySelector('.mobile-drawer-overlay');
    const closeBtn = document.querySelector('.mobile-drawer-close');

    if (!hamburger || !drawer) return;

    // Toggle drawer on hamburger click
    hamburger.addEventListener('click', () => {
        toggleMobileDrawer(hamburger, drawer, overlay);
    });

    // Close drawer on overlay click
    if (overlay) {
        overlay.addEventListener('click', () => {
            closeMobileDrawer(hamburger, drawer, overlay);
        });
    }

    // Close drawer on close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            closeMobileDrawer(hamburger, drawer, overlay);
        });
    }

    // Close drawer when clicking a link (after navigation)
    const drawerLinks = drawer.querySelectorAll('.mobile-drawer-link');
    drawerLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Small delay to allow navigation to start
            setTimeout(() => {
                closeMobileDrawer(hamburger, drawer, overlay);
            }, 100);
        });
    });
}

/**
 * Toggle mobile drawer open/closed
 */
function toggleMobileDrawer(hamburger, drawer, overlay) {
    const isOpen = drawer.classList.contains('open');

    if (isOpen) {
        closeMobileDrawer(hamburger, drawer, overlay);
    } else {
        openMobileDrawer(hamburger, drawer, overlay);
    }
}

/**
 * Open mobile drawer
 */
function openMobileDrawer(hamburger, drawer, overlay) {
    hamburger.classList.add('open');
    drawer.classList.add('open');
    if (overlay) overlay.classList.add('open');

    // Lock body scroll
    document.body.style.overflow = 'hidden';

    // Set focus to close button for accessibility
    const closeBtn = drawer.querySelector('.mobile-drawer-close');
    if (closeBtn) {
        setTimeout(() => closeBtn.focus(), 100);
    }
}

/**
 * Close mobile drawer
 */
function closeMobileDrawer(hamburger, drawer, overlay) {
    hamburger.classList.remove('open');
    drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('open');

    // Unlock body scroll
    document.body.style.overflow = '';

    // Return focus to hamburger button
    hamburger.focus();
}

/**
 * Initialize keyboard navigation
 */
function initKeyboardNavigation() {
    document.addEventListener('keydown', (e) => {
        // Close dropdowns and drawer on Escape
        if (e.key === 'Escape') {
            // Close all dropdowns
            const dropdowns = document.querySelectorAll('.navbar-dropdown.open');
            dropdowns.forEach(d => d.classList.remove('open'));

            // Close mobile drawer
            const hamburger = document.querySelector('.navbar-hamburger');
            const drawer = document.querySelector('.mobile-drawer');
            const overlay = document.querySelector('.mobile-drawer-overlay');

            if (drawer && drawer.classList.contains('open')) {
                closeMobileDrawer(hamburger, drawer, overlay);
            }
        }
    });

    // Handle arrow key navigation within dropdowns
    document.querySelectorAll('.navbar-dropdown').forEach(dropdown => {
        dropdown.addEventListener('keydown', (e) => {
            if (!dropdown.classList.contains('open')) return;

            const items = dropdown.querySelectorAll('.navbar-dropdown-item');
            const currentIndex = Array.from(items).indexOf(document.activeElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                items[nextIndex].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex].focus();
            }
        });
    });
}

/**
 * Utility: Close all navbar dropdowns
 * Can be called from other scripts if needed
 */
window.closeNavbarDropdowns = function() {
    document.querySelectorAll('.navbar-dropdown.open').forEach(d => {
        d.classList.remove('open');
    });
};

/**
 * Utility: Close mobile drawer
 * Can be called from other scripts if needed
 */
window.closeNavbarMobileDrawer = function() {
    const hamburger = document.querySelector('.navbar-hamburger');
    const drawer = document.querySelector('.mobile-drawer');
    const overlay = document.querySelector('.mobile-drawer-overlay');

    if (drawer && drawer.classList.contains('open')) {
        closeMobileDrawer(hamburger, drawer, overlay);
    }
};
