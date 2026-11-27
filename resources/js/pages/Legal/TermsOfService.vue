<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Scale } from '@lucide/vue';
</script>

<template>
    <Head title="Terms of service" />
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
                    <Scale class="h-4 w-4" /> Working together
                </div>
                <h1
                    class="elyx-display text-6xl leading-[0.86] tracking-[-0.07em] sm:text-8xl"
                >
                    Terms of<br /><em>service.</em>
                </h1>
                <p class="mt-6 text-sm text-black/50">
                    Last updated: November 27, 2026
                </p>
            </div>
            <div class="space-y-10 text-sm leading-7 text-black/65">
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        1. Using ELYX Studio
                    </h2>
                    <p>
                        By using this site or submitting an order request, you
                        agree to provide accurate information, use the site
                        lawfully, and respect the intellectual property of ELYX
                        Studio and its collaborators. An order request is not
                        accepted until ELYX confirms scope, availability,
                        pricing, and any payment terms with you.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        2. Products and digital goods
                    </h2>
                    <p>
                        Digital products are supplied under the license or usage
                        terms shown with the product or provided at delivery.
                        You may not resell, redistribute, or claim ownership of
                        source materials unless the applicable license expressly
                        allows it. Product descriptions and availability may
                        change, but confirmed order records preserve the details
                        agreed at the time.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        3. Websites and studio services
                    </h2>
                    <p>
                        Service packages are starting points for a scoped
                        project. Final deliverables, timelines, revisions,
                        responsibilities, hosting, third-party costs, and
                        intellectual-property transfer are confirmed in writing
                        before work begins. You are responsible for supplying
                        content and approvals on time. Delays in those materials
                        can change delivery dates.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        4. Pricing and payment
                    </h2>
                    <p>
                        Prices are shown in the currency displayed on the site
                        unless we agree otherwise in writing. This version of
                        the storefront collects order requests; payment is
                        arranged securely after review. We never ask for card
                        details by email or store payment-card data in ELYX
                        Studio.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        5. Cancellations and refunds
                    </h2>
                    <p>
                        Because products and services differ, cancellation and
                        refund terms are confirmed with the order or project
                        agreement. If a physical or digital product is
                        unavailable, we will contact you with replacement,
                        refund, or cancellation options. Custom work already
                        completed may not be refundable.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        6. Liability
                    </h2>
                    <p>
                        ELYX Studio is not responsible for outages, third-party
                        services, customer-supplied materials, or delays outside
                        our reasonable control. To the extent permitted by law,
                        liability for an order is limited to the amount paid for
                        that order. Nothing in these terms limits rights that
                        cannot legally be waived.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        7. Contact
                    </h2>
                    <p>
                        For order, project, or terms questions, email
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
