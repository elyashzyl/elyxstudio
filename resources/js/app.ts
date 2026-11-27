import { createInertiaApp, router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import { initTheme } from '@/composables/useTheme';
import AuthLayout from '@/layouts/AuthLayout.vue';
import CommerceLayout from '@/layouts/CommerceLayout.vue';

import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import TeacherLayout from '@/layouts/TeacherLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

import '../css/game-theme.css';
import '../css/themes.css';
import '../css/elyx-theme.css';

initTheme();

const appName =
    import.meta.env.VITE_APP_NAME ||
    (typeof document !== 'undefined'
        ? document.querySelector('meta[name="app-name"]')?.getAttribute('content')
        : null) ||
    'ELYX Studio';

// Save scroll position per pathname
function saveScroll() {
    sessionStorage.setItem(
        'scroll_pos_' + window.location.pathname,
        String(window.scrollY),
    );
}

// Restore scroll position for current pathname or hash target
function restoreScroll() {
    if (window.location.hash) {
        try {
            const target = document.querySelector(window.location.hash);

            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                return;
            }
        } catch {
            // ignore invalid selector syntax
        }

        setTimeout(() => {
            try {
                const el = document.querySelector(window.location.hash);

                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } catch {}
        }, 120);

        return;
    }

    const saved = sessionStorage.getItem(
        'scroll_pos_' + window.location.pathname,
    );

    if (saved) {
        const top = parseInt(saved, 10);
        setTimeout(() => window.scrollTo({ top, behavior: 'auto' }), 50);
        setTimeout(() => window.scrollTo({ top, behavior: 'auto' }), 180);
    }
}

// Save scroll before any Inertia navigation
router.on('start', () => saveScroll());

// Restore scroll when Inertia finishes navigation
router.on('finish', () => restoreScroll());

// Also save on page unload (browser refresh)
window.addEventListener('beforeunload', saveScroll);

// Restore on initial page load
restoreScroll();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('Storefront/'):
                return StorefrontLayout;
            case name.startsWith('Commerce/'):
                return CommerceLayout;
            case name === 'Welcome':
                return null;

            case name.startsWith('Teacher/'):
                return TeacherLayout;
            case name.startsWith('Student/'):
                return StudentLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
