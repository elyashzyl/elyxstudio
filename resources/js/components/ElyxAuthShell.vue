<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Check, Sparkles } from '@lucide/vue';

defineProps<{
    eyebrow: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <div
        class="elyx-auth-page flex min-h-screen flex-col overflow-x-hidden px-5 py-6 text-[#102a43] sm:px-8 sm:py-8 lg:h-dvh lg:min-h-0 lg:overflow-hidden lg:py-5"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none fixed -top-40 -left-40 h-96 w-96 rounded-full bg-[#b9e8fb]/45 blur-3xl"
        ></div>
        <div
            aria-hidden="true"
            class="pointer-events-none fixed -right-40 -bottom-40 h-[30rem] w-[30rem] rounded-full bg-[#8bd5f4]/20 blur-3xl"
        ></div>

        <header
            class="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between"
        >
            <Link href="/" class="group flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#27a9e8] text-white shadow-[0_8px_18px_rgba(39,169,232,0.25)] transition-transform group-hover:-rotate-6"
                >
                    <Sparkles class="h-4 w-4" />
                </span>
                <span class="leading-none">
                    <strong
                        class="block text-sm font-extrabold tracking-[0.18em] uppercase"
                        >ELYX</strong
                    >
                    <small
                        class="mt-1 block text-[9px] font-semibold tracking-[0.2em] text-[#55718a] uppercase"
                        >Studio shop</small
                    >
                </span>
            </Link>
            <Link
                href="/"
                class="rounded-xl px-3 py-2 text-sm font-bold text-[#55718a] transition hover:bg-white/70 hover:text-[#0f6ea8]"
                >Back to store</Link
            >
        </header>

        <main
            class="relative z-10 mx-auto grid w-full max-w-7xl items-center gap-10 py-12 sm:py-16 lg:min-h-0 lg:flex-1 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20 lg:py-5"
        >
            <section
                class="relative hidden min-h-[30rem] overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#dff5ff] via-[#aee5fa] to-[#43b8eb] p-8 shadow-[0_24px_60px_rgba(39,169,232,0.18)] lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:p-10"
                aria-label="ELYX Studio introduction"
            >
                <div
                    aria-hidden="true"
                    class="absolute -top-24 -right-24 h-72 w-72 rounded-full border border-white/45"
                ></div>
                <div
                    aria-hidden="true"
                    class="absolute -right-20 -bottom-24 h-80 w-80 rounded-full border border-white/35"
                ></div>
                <div
                    aria-hidden="true"
                    class="absolute top-1/2 -left-32 h-64 w-64 rounded-full border border-white/25"
                ></div>

                <div
                    class="relative flex items-center justify-between text-[10px] font-bold tracking-[0.18em] text-[#0b527e] uppercase"
                >
                    <span>ELYX / STUDIO</span>
                    <span>2026 EDIT</span>
                </div>

                <div class="relative mt-auto">
                    <div
                        class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/75 text-[#0f6ea8] shadow-lg backdrop-blur-sm"
                    >
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <p
                        class="max-w-md text-[clamp(2.8rem,5vw,5rem)] leading-[0.9] font-extrabold tracking-[-0.065em] text-[#102a43]"
                    >
                        Build something<br /><span class="text-white"
                            >worth using.</span
                        >
                    </p>
                    <p
                        class="mt-7 max-w-sm text-sm leading-6 font-semibold text-[#0b527e]"
                    >
                        Practical digital products, launch-ready websites, and
                        thoughtful studio services for people moving good ideas
                        forward.
                    </p>
                </div>

                <div class="relative mt-10 grid grid-cols-2 gap-3">
                    <div
                        class="rounded-2xl border border-white/45 bg-white/40 p-4 backdrop-blur-sm"
                    >
                        <Check class="mb-5 h-4 w-4 text-[#0f6ea8]" />
                        <p class="text-xs font-bold text-[#102a43]">
                            Curated tools
                        </p>
                        <p class="mt-1 text-[11px] text-[#0b527e]">
                            Made for momentum.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-white/45 bg-[#0f6ea8]/90 p-4 text-white shadow-lg"
                    >
                        <ArrowUpRight class="mb-5 h-4 w-4 text-[#8bd5f4]" />
                        <p class="text-xs font-bold">Studio services</p>
                        <p class="mt-1 text-[11px] text-white/70">
                            Designed to ship.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-[31rem] lg:min-h-0">
                <div class="mb-8 text-center sm:mb-9 lg:mb-4">
                    <p
                        class="text-[10px] font-bold tracking-[0.2em] text-[#0f6ea8] uppercase"
                    >
                        {{ eyebrow }}
                    </p>
                    <h1
                        class="mt-3 text-4xl leading-[0.95] font-extrabold tracking-[-0.055em] text-[#102a43] sm:text-5xl lg:mt-2 lg:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="mx-auto mt-4 max-w-md text-sm leading-6 text-[#55718a] lg:mt-2 lg:text-xs lg:leading-5"
                    >
                        {{ description }}
                    </p>
                </div>

                <div
                    class="relative overflow-hidden rounded-[1.5rem] border border-[#d7ebf8] bg-white/95 p-6 shadow-[0_24px_70px_rgba(16,42,67,0.1)] backdrop-blur-sm sm:p-8 lg:p-5"
                >
                    <div
                        aria-hidden="true"
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#8bd5f4] via-[#27a9e8] to-[#0f6ea8]"
                    ></div>
                    <slot name="status" />
                    <slot />
                </div>

                <div class="mt-6 text-center text-sm text-[#55718a] lg:mt-4">
                    <slot name="footer" />
                </div>

                <p class="mt-7 text-center text-[11px] text-[#8aa4b8] lg:mt-4">
                    Secure access to your ELYX Studio workspace.
                </p>
            </section>
        </main>
    </div>
</template>

<style scoped>
.elyx-auth-page {
    color-scheme: light;
    background:
        radial-gradient(
            circle at 10% 0%,
            rgba(139, 213, 244, 0.28),
            transparent 28rem
        ),
        radial-gradient(
            circle at 90% 100%,
            rgba(39, 169, 232, 0.12),
            transparent 30rem
        ),
        #f4fbff;
}
@media (max-height: 800px) and (min-width: 1024px) {
    .elyx-auth-page {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
}
</style>
