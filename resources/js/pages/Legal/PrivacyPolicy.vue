<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Shield } from '@lucide/vue';
</script>

<template>
    <Head title="Privacy policy" />
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
                    <Shield class="h-4 w-4" /> Data and privacy
                </div>
                <h1
                    class="elyx-display text-6xl leading-[0.86] tracking-[-0.07em] sm:text-8xl"
                >
                    Privacy<br /><em>policy.</em>
                </h1>
                <p class="mt-6 text-sm text-black/50">
                    Last updated: November 27, 2026
                </p>
            </div>
            <div class="space-y-10 text-sm leading-7 text-black/65">
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        1. What we collect
                    </h2>
                    <p>
                        When you browse ELYX Studio, we may receive technical
                        information needed to keep the site secure. When you
                        submit an order request, we collect your name, email
                        address, optional phone number, project note, selected
                        products, and order details. If you create an account or
                        work with us on a service project, we also process the
                        profile and project information you choose to provide.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        2. How we use it
                    </h2>
                    <p>
                        We use this information to respond to order requests,
                        deliver products or services, communicate about
                        projects, maintain accounts, improve the storefront,
                        prevent abuse, and meet legal or accounting obligations.
                        We do not sell personal information or use it for
                        unrelated advertising.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        3. Sharing
                    </h2>
                    <p>
                        We share information only with service providers that
                        help us host the application, send email, process
                        payments when a provider is enabled, deliver files, or
                        operate a project. Those providers receive only the
                        information required for their function. We may disclose
                        information when required by law or to protect the
                        security of ELYX Studio and its customers.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        4. Retention and security
                    </h2>
                    <p>
                        We keep order and project records for as long as needed
                        to provide the service, manage business records, resolve
                        disputes, and meet legal requirements. We use encrypted
                        transport, hashed account credentials, server-side
                        validation, access controls, and transaction records. No
                        online service can guarantee absolute security, so
                        please contact us promptly if you suspect unauthorized
                        access.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        5. Your choices
                    </h2>
                    <p>
                        You may ask us to access, correct, or delete personal
                        information we hold about you, subject to records we
                        must retain. You may also opt out of non-essential
                        communications. Contact us with the email used for your
                        order so we can verify and handle your request.
                    </p>
                </section>
                <section>
                    <h2 class="mb-3 text-xl font-semibold text-black">
                        6. Contact
                    </h2>
                    <p>
                        Questions about privacy can be sent to
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
