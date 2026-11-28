<template>
    <transition
        enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="translate-y-8 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in transform"
        leave-from-class="translate-y-0 opacity-100 scale-100"
        leave-to-class="translate-y-8 opacity-0 scale-95"
    >
        <div
            v-if="visible"
            class="fixed right-4 bottom-4 left-4 z-50 mx-auto max-w-xl sm:right-6 sm:bottom-6 sm:left-auto"
            role="dialog"
            aria-live="polite"
            aria-label="Cookie consent banner"
        >
            <div
                class="relative overflow-hidden rounded-2xl p-5 shadow-2xl backdrop-blur-xl"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 16px 40px -8px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="flex items-start gap-3.5">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                rgba(59, 130, 246, 0.15),
                                rgba(124, 58, 237, 0.15)
                            );
                            color: var(--gl-primary);
                        "
                    >
                        <Cookie class="h-5 w-5" :stroke-width="2.2" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <h4
                                class="text-sm font-bold tracking-tight"
                                style="color: var(--gl-text-primary)"
                            >
                                Cookie &amp; Privacy Preferences
                            </h4>
                            <button
                                type="button"
                                @click="dismiss"
                                class="rounded-lg p-1 transition-colors hover:opacity-75"
                                style="color: var(--gl-text-muted)"
                                aria-label="Close cookie banner"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p
                            class="mt-1.5 text-xs leading-relaxed"
                            style="color: var(--gl-text-secondary)"
                        >
                            ELYX Studio uses essential cookies and secure local
                            storage to keep you authenticated, safeguard your
                            account, and remember your visual theme. We never
                            sell your data or use third-party ad trackers.
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="acceptAll"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02] active:scale-[0.98]"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        var(--gl-primary),
                                        var(--gl-secondary)
                                    );
                                    box-shadow: 0 2px 10px
                                        var(--gl-primary-glow);
                                "
                            >
                                Accept All
                            </button>

                            <button
                                type="button"
                                @click="acceptEssential"
                                class="rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:opacity-90 active:scale-[0.98]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                Essential Only
                            </button>

                            <Link
                                href="/cookie-policy"
                                class="ml-auto text-xs font-medium underline underline-offset-4 transition-colors hover:opacity-80"
                                style="color: var(--gl-primary)"
                            >
                                Cookie Policy
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Cookie, X } from '@lucide/vue';
import { ref, onMounted } from 'vue';

const STORAGE_KEY = 'elyx_cookie_consent';
const visible = ref(false);

onMounted(() => {
    try {
        const consent = localStorage.getItem(STORAGE_KEY);

        if (!consent) {
            visible.value = true;
        }
    } catch {
        visible.value = false;
    }
});

function acceptAll() {
    try {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                status: 'accepted',
                analytics: true,
                timestamp: new Date().toISOString(),
            }),
        );
    } catch {
        /* storage unavailable */
    }

    visible.value = false;
}

function acceptEssential() {
    try {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                status: 'essential_only',
                analytics: false,
                timestamp: new Date().toISOString(),
            }),
        );
    } catch {
        /* storage unavailable */
    }

    visible.value = false;
}

function dismiss() {
    acceptEssential();
}
</script>
