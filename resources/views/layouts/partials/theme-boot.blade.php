{{--
    Theme boot: runs BEFORE any stylesheet so the first paint is already in the
    right theme (no white flash when moving between pages in dark mode).
    - The choice is kept in localStorage, so it survives new tabs and logins.
    - Keeps the template's sessionStorage config in step, so its own JS agrees.
    - Applies the sidebar size early too, so the menu does not jump.
--}}
<script>
    (function () {
        var html = document.documentElement;
        var KEY = 'ss-theme';
        var CONFIG_KEY = '__TAPLOX_CONFIG__';
        var theme = null, config = null;

        try { theme = localStorage.getItem(KEY); } catch (e) {}
        try { config = JSON.parse(sessionStorage.getItem(CONFIG_KEY) || 'null'); } catch (e) {}

        if (theme !== 'dark' && theme !== 'light') {
            theme = (config && config.theme) || 'light';
        }
        html.setAttribute('data-bs-theme', theme);
        html.style.colorScheme = theme;

        if (config) {
            if (config.theme !== theme) {
                config.theme = theme;
                try { sessionStorage.setItem(CONFIG_KEY, JSON.stringify(config)); } catch (e) {}
            }
            var size = window.innerWidth <= 1140 ? 'hidden' : ((config.menu && config.menu.size) || 'default');
            html.setAttribute('data-sidebar-size', size);
        } else if (window.innerWidth <= 1140) {
            html.setAttribute('data-sidebar-size', 'hidden');
        }

        // Remember every change (the topbar toggle lives in the template's JS) and fade it smoothly.
        new MutationObserver(function () {
            var now = html.getAttribute('data-bs-theme');
            if (now !== 'dark' && now !== 'light') return;
            try { localStorage.setItem(KEY, now); } catch (e) {}
            if (html.style.colorScheme === now) return;
            html.style.colorScheme = now;
            html.classList.add('ss-theme-switching');
            clearTimeout(window.__ssThemeTimer);
            window.__ssThemeTimer = setTimeout(function () { html.classList.remove('ss-theme-switching'); }, 350);
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', now === 'dark' ? '#0F1720' : '#ffffff');
        }).observe(html, { attributes: true, attributeFilter: ['data-bs-theme'] });
    })();
</script>
<style>
    /* painted before the main CSS arrives — the page never starts white in dark mode */
    html { background-color: #F4F6FA; }
    html[data-bs-theme="dark"] { background-color: #0F1720; color-scheme: dark; }
    html[data-bs-theme="dark"] body { background-color: #0F1720; color: #E6EDF5; }

    /* smooth fade only while the theme is being switched (never on normal page loads) */
    html.ss-theme-switching,
    html.ss-theme-switching *,
    html.ss-theme-switching *::before,
    html.ss-theme-switching *::after {
        transition: background-color .3s ease, border-color .3s ease, color .2s ease, fill .3s ease, stroke .3s ease, box-shadow .3s ease !important;
    }

    /* soft cross-fade between pages in browsers that support it (Chrome / Edge) */
    @view-transition { navigation: auto; }
    ::view-transition-old(root), ::view-transition-new(root) { animation-duration: .18s; }

    @media (prefers-reduced-motion: reduce) {
        html.ss-theme-switching * { transition: none !important; }
        @view-transition { navigation: none; }
    }
</style>
