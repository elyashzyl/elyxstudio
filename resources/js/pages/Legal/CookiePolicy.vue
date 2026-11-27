<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Cookie } from '@lucide/vue';
</script>

<template>
    <Head title="Cookie policy" />
    <div class="elyx-site min-h-screen bg-[#f6f3ed] text-[#171717]">
        <header class="border-b border-black/10 px-5 py-5 sm:px-8">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <Link
                    href="/"
                    class="text-sm font-bold tracking-[0.2em] uppercase"
                    >ELYX Studio</Link
                >
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-black/55 hover:text-black"
                    ><ArrowLeft class="h-3.5 w-3.5" /> Back home</Link
                >
            </div>
        </header>
        <main class="mx-auto max-w-3xl px-5 py-16 sm:px-8 sm:py-24">
            <div class="mb-12 border-b border-black/10 pb-10">
                <div
                    class="mb-5 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.2em] text-[#e85d3f] uppercase"
                >
                    <Cookie class="h-4 w-4" /> Browser storage
                </div>
                <h1
                    class="elyx-display text-6xl leading-[0.86] tracking-[-0.07em] sm:text-8xl"
                >
                    Cookie<br /><em>policy.</em>
                </h1>
                <p class="mt-6 text-sm text-black/50">
                    Last updated: November 27, 2026
                </p>
            </div>
            <div class="space-y-10 text-sm leading-7 text-black/65">
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        1. What cookies do
                    </h2>
                    <p>
                        Cookies and browser storage are small values saved by
                        your browser. ELYX Studio uses them to keep requests
                        secure, remember preferences, maintain a session cart,
                        and support authenticated account sessions. We do not
                        use third-party behavioral advertising cookies in the
                        storefront.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        2. Essential storage
                    </h2>
                    <div class="overflow-x-auto">
                        <table
                            class="w-full min-w-[540px] border-collapse text-left text-xs"
                        >
                            <thead>
                                <tr class="border-b border-black/10">
                                    <th
                                        class="px-3 py-3 font-semibold text-black"
                                    >
                                        Storage
                                    </th>
                                    <th
                                        class="px-3 py-3 font-semibold text-black"
                                    >
                                        Purpose
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10">
                                <tr>
                                    <td class="px-3 py-3 font-mono">
                                        elyxstudio_session
                                    </td>
                                    <td class="px-3 py-3">
                                        Maintains session and cart state.
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3 font-mono">
                                        XSRF-TOKEN
                                    </td>
                                    <td class="px-3 py-3">
                                        Helps protect form submissions against
                                        cross-site request forgery.
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3 font-mono">
                                        appearance / elyx-theme
                                    </td>
                                    <td class="px-3 py-3">
                                        Remembers interface appearance
                                        preferences.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        3. Managing cookies
                    </h2>
                    <p>
                        You can clear or block cookies in your browser settings.
                        Blocking essential cookies may prevent cart, checkout,
                        login, or other site functions from working correctly.
                        If we introduce analytics or marketing storage later,
                        this policy will be updated before those tools are
                        enabled.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        4. Contact
                    </h2>
                    <p>
                        Questions about browser storage can be sent to
                        <a
                            href="mailto:hello@elyx.studio"
                            class="font-semibold text-[#e85d3f] underline"
                            >hello@elyx.studio</a
                        >.
                    </p>
                </section>
            </div>
        </main>
    </div>
</template>
