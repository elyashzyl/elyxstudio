import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initTheme } from '@/composables/useTheme';
import AuthLayout from '@/layouts/AuthLayout.vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

import '../css/themes.css';
import '../css/elyx-theme.css';

initTheme();

const appName =
    import.meta.env.VITE_APP_NAME ||
    (typeof document !== 'undefined'
        ? document.querySelector('meta[name="app-name"]')?.getAttribute('content')
        : null) ||
    'ELYX Studio';

function saveScroll() {
    sessionStorage.setItem(`scroll_pos_${window.location.pathname}`, String(window.scrollY));
}

function restoreScroll() {
    if (window.location.hash) {
        setTimeout(() => {
            try {
                document.querySelector(window.location.hash)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch {
                // Ignore invalid hash selectors.
            }
        }, 120);
        return;
    }

    const saved = sessionStorage.getItem(`scroll_pos_${window.location.pathname}`);
    if (saved) {
        const top = Number.parseInt(saved, 10);
        setTimeout(() => window.scrollTo({ top, behavior: 'auto' }), 50);
    }
}

router.on('start', saveScroll);
router.on('finish', restoreScroll);
window.addEventListener('beforeunload', saveScroll);
restoreScroll();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('Storefront/')) return StorefrontLayout;
        if (name.startsWith('Commerce/')) return CommerceLayout;
        if (name.startsWith('auth/')) return AuthLayout;

        return undefined;
    },
    progress: { color: '#27a9e8' },
});

initializeTheme();
initializeFlashToast();
