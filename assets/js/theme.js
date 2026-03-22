/**
 * OMADE Theme System — Light / Dark Mode
 * Saves preference in localStorage and applies instantly on load.
 */

(function () {
    const STORAGE_KEY = 'omade-theme';

    function applyTheme(mode) {
        document.documentElement.setAttribute('data-theme', mode);
        localStorage.setItem(STORAGE_KEY, mode);
    }

    // Apply saved preference BEFORE any paint (no flash)
    applyTheme(localStorage.getItem(STORAGE_KEY) || 'light');

    window.setTheme = function(mode) {
        applyTheme(mode);
        syncAllToggleButtons(mode);
        // Dispatch event for components that need to react (like settings cards)
        window.dispatchEvent(new CustomEvent('themechanged', { detail: { theme: mode } }));
    };

    window.toggleTheme = function (btn) {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';

        // Animate: slide + spin
        if (btn) {
            btn.classList.add('theme-btn--spinning');
            btn.addEventListener('animationend', () => {
                btn.classList.remove('theme-btn--spinning');
            }, { once: true });
        }

        applyTheme(next);
        syncAllToggleButtons(next);
    };

    function syncAllToggleButtons(mode) {
        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            const icon = btn.querySelector('i');
            if (!icon) return;
            if (mode === 'dark') {
                icon.className = 'fas fa-sun';
                btn.title = 'Cambiar a modo claro';
            } else {
                icon.className = 'fas fa-moon';
                btn.title = 'Cambiar a modo oscuro';
            }
        });
    }

    // Sync icons once DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        syncAllToggleButtons(localStorage.getItem(STORAGE_KEY) || 'light');
    });
})();
