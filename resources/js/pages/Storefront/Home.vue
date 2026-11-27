<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Check,
    MoveUpRight,
    Plus,
    Sparkles,
    Star,
} from '@lucide/vue';
import type { StorefrontProduct } from '@/types';

defineProps<{
    featuredProducts: StorefrontProduct[];
    services: StorefrontProduct[];
}>();

const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
const add = (product: StorefrontProduct) =>
    router.post(
        `/cart/${product.slug}`,
        { quantity: 1 },
        { preserveScroll: true },
    );
</script>

<template>
    <Head title="Digital products & websites" />

    <section
        class="relative overflow-hidden border-b border-[#d7ebf8] bg-white"
    >
        <div
            class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:py-24"
        >
            <div class="relative z-10">
                <div
                    class="mb-7 inline-flex items-center gap-2 rounded-full border border-[#b9e2f6] bg-[#f4fbff] px-3.5 py-2 text-[10px] font-bold tracking-[0.16em] text-[#0f6ea8] uppercase"
                >
                    <Sparkles class="h-3.5 w-3.5" /> Crafted for curious
                    builders
                </div>
                <h1
                    class="max-w-3xl text-[clamp(3.1rem,7vw,6.8rem)] leading-[0.92] font-extrabold tracking-[-0.065em] text-[#102a43]"
                >
                    Build something<br /><span class="text-[#27a9e8]"
                        >worth using.</span
                    >
                </h1>
                <p
                    class="mt-7 max-w-xl text-base leading-7 text-[#55718a] sm:text-lg"
                >
                    Digital products, launch-ready websites, and practical
                    studio services for people turning good ideas into real
                    momentum.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <Link
                        href="/shop"
                        class="group inline-flex items-center justify-center gap-3 rounded-xl bg-[#0f6ea8] px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_24px_rgba(15,110,168,0.22)] transition hover:bg-[#0b527e]"
                    >
                        Explore the shop
                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </Link>
                    <a
                        href="#services"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#c7e3f3] bg-white px-5 py-3.5 text-sm font-bold text-[#102a43] transition hover:border-[#27a9e8] hover:bg-[#f4fbff]"
                    >
                        Explore websites
                        <ArrowUpRight class="h-4 w-4 text-[#0f6ea8]" />
                    </a>
                </div>
                <div
                    class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs font-semibold text-[#55718a]"
                >
                    <span class="inline-flex items-center gap-2"
                        ><Check class="h-4 w-4 text-[#27a9e8]" /> Clear, useful
                        design</span
                    >
                    <span class="inline-flex items-center gap-2"
                        ><Check class="h-4 w-4 text-[#27a9e8]" /> Secure
                        checkout flow</span
                    >
                    <span class="inline-flex items-center gap-2"
                        ><Check class="h-4 w-4 text-[#27a9e8]" /> Human
                        support</span
                    >
                </div>
            </div>
            <div
                class="relative min-h-[410px] overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#dff5ff] via-[#aee5fa] to-[#43b8eb] p-5 shadow-[0_24px_60px_rgba(39,169,232,0.2)] sm:min-h-[520px] sm:p-7"
            >
                <div
                    class="absolute -top-20 -right-16 h-64 w-64 rounded-full border border-white/45"
                ></div>
                <div
                    class="absolute -right-5 -bottom-24 h-72 w-72 rounded-full border border-white/35"
                ></div>
                <div class="relative flex h-full flex-col justify-between">
                    <div
                        class="flex items-center justify-between text-[10px] font-bold tracking-[0.18em] text-[#0b527e] uppercase"
                    >
                        <span>ELYX / 2026</span><span>Studio edit</span>
                    </div>
                    <div class="relative mx-auto w-full max-w-[350px]">
                        <div
                            class="absolute top-8 left-1/2 h-56 w-44 -translate-x-1/2 rotate-[-7deg] rounded-[1.25rem] bg-white/85 p-5 shadow-2xl backdrop-blur-sm sm:h-72 sm:w-56"
                        >
                            <div class="flex h-full flex-col justify-between">
                                <div
                                    class="flex items-center justify-between text-[9px] font-bold tracking-[0.16em] text-[#0f6ea8] uppercase"
                                >
                                    <span>Field guide</span><span>01</span>
                                </div>
                                <div>
                                    <div
                                        class="mb-4 h-1.5 w-16 rounded-full bg-[#27a9e8]"
                                    ></div>
                                    <p
                                        class="text-4xl leading-[0.9] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-5xl"
                                    >
                                        Make<br />room.
                                    </p>
                                </div>
                                <p
                                    class="text-[10px] font-semibold text-[#55718a]"
                                >
                                    A better way to begin.
                                </p>
                            </div>
                        </div>
                        <div
                            class="relative mx-auto mt-16 aspect-[4/5] w-44 rotate-[8deg] overflow-hidden rounded-[1.25rem] border-8 border-white bg-[#0f6ea8] shadow-2xl sm:w-56"
                        >
                            <img
                                v-if="featuredProducts[0]?.image_url"
                                :src="featuredProducts[0].image_url"
                                :alt="featuredProducts[0].name"
                                class="h-full w-full object-cover opacity-80 mix-blend-screen"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-[#102a43]/80 via-transparent to-transparent"
                            ></div>
                            <div class="absolute inset-x-4 bottom-5 text-white">
                                <p
                                    class="text-[9px] font-bold tracking-[0.16em] uppercase"
                                >
                                    ELYX object 01
                                </p>
                                <p
                                    class="mt-2 text-xl leading-none font-extrabold"
                                >
                                    Useful<br />can be beautiful.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex items-end justify-between text-xs font-bold text-[#0b527e]"
                    >
                        <span>Products / services / ideas</span
                        ><span
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-[#0f6ea8] shadow-lg"
                            ><ArrowUpRight class="h-4 w-4"
                        /></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-[#d7ebf8] bg-[#f4fbff] px-5 py-5 sm:px-8">
        <div
            class="mx-auto grid max-w-7xl gap-4 text-center text-xs font-bold text-[#55718a] sm:grid-cols-3 sm:text-left"
        >
            <div
                class="flex items-center justify-center gap-3 sm:justify-start"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#27a9e8] shadow-sm"
                    ><Star class="h-4 w-4 fill-current" /></span
                ><span>Curated tools for better work</span>
            </div>
            <div
                class="flex items-center justify-center gap-3 sm:justify-start"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#27a9e8] shadow-sm"
                    ><Check class="h-4 w-4" /></span
                ><span>Simple ordering, thoughtful delivery</span>
            </div>
            <div
                class="flex items-center justify-center gap-3 sm:justify-start"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#27a9e8] shadow-sm"
                    ><Sparkles class="h-4 w-4" /></span
                ><span>Made with care in the studio</span>
            </div>
        </div>
    </section>

    <section
        id="services"
        class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28"
    >
        <div
            class="mb-10 flex flex-col justify-between gap-5 sm:flex-row sm:items-end"
        >
            <div>
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Ways we can help
                </p>
                <h2
                    class="max-w-xl text-4xl leading-tight font-extrabold tracking-[-0.045em] text-[#102a43] sm:text-5xl"
                >
                    A clearer path from idea to launch.
                </h2>
            </div>
            <p class="max-w-sm text-sm leading-6 text-[#55718a]">
                Choose a ready-made resource or bring us the bigger brief.
                Either way, the next step should feel easy.
            </p>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <Link
                v-for="(service, index) in services"
                :key="service.id"
                :href="`/shop/${service.slug}`"
                class="group relative min-h-[330px] overflow-hidden rounded-3xl border border-[#c7e3f3] bg-white p-6 shadow-[0_10px_30px_rgba(16,42,67,0.05)] transition duration-300 hover:-translate-y-1 hover:border-[#72c9ef] hover:shadow-[0_18px_40px_rgba(39,169,232,0.15)]"
            >
                <img
                    v-if="service.image_url"
                    :src="service.image_url"
                    :alt="service.name"
                    class="absolute inset-0 h-full w-full object-cover opacity-10 transition duration-500 group-hover:scale-105 group-hover:opacity-20"
                />
                <div class="relative flex h-full flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#e8f7fe] text-xs font-extrabold text-[#0f6ea8]"
                            >0{{ index + 1 }}</span
                        ><MoveUpRight
                            class="h-5 w-5 text-[#27a9e8] transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"
                        />
                    </div>
                    <div>
                        <p
                            class="mb-2 text-[10px] font-bold tracking-[0.16em] text-[#0f6ea8] uppercase"
                        >
                            {{ service.category }}
                        </p>
                        <h3
                            class="max-w-[15rem] text-2xl leading-tight font-extrabold tracking-[-0.035em] text-[#102a43]"
                        >
                            {{ service.name }}
                        </h3>
                        <p
                            class="mt-3 max-w-xs text-sm leading-6 text-[#55718a]"
                        >
                            {{ service.tagline }}
                        </p>
                        <span
                            class="mt-5 inline-flex items-center gap-2 text-xs font-bold text-[#0f6ea8]"
                            >View service <ArrowRight class="h-3.5 w-3.5"
                        /></span>
                    </div>
                </div>
            </Link>
        </div>
    </section>

    <section
        id="process"
        class="bg-[#102a43] px-5 py-20 text-white sm:px-8 sm:py-28"
    >
        <div class="mx-auto max-w-7xl">
            <div class="mb-14 max-w-2xl">
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#8bd5f4] uppercase"
                >
                    The ELYX standard
                </p>
                <h2
                    class="text-4xl leading-tight font-extrabold tracking-[-0.05em] sm:text-6xl"
                >
                    Useful first.<br /><span class="text-[#75cff2]"
                        >Beautiful always.</span
                    >
                </h2>
            </div>
            <div
                class="grid gap-8 border-t border-white/15 pt-8 md:grid-cols-3"
            >
                <div
                    v-for="(step, index) in [
                        {
                            title: 'Start with the signal',
                            text: 'We ask the right questions, find the sharpest angle, and make the complex feel clear.',
                        },
                        {
                            title: 'Shape the experience',
                            text: 'Strategy, design, and technology work together so every detail has a job.',
                        },
                        {
                            title: 'Make it real',
                            text: 'We ship thoughtful work that holds up in the real world, not just in a presentation.',
                        },
                    ]"
                    :key="step.title"
                    class="relative"
                >
                    <span class="mb-10 block text-sm font-bold text-[#75cff2]"
                        >0{{ index + 1 }}</span
                    >
                    <h3 class="mb-3 text-lg font-bold">{{ step.title }}</h3>
                    <p class="max-w-xs text-sm leading-6 text-white/55">
                        {{ step.text }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28">
        <div class="mb-9 flex items-end justify-between">
            <div>
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Popular from the studio
                </p>
                <h2
                    class="text-4xl font-extrabold tracking-[-0.05em] text-[#102a43] sm:text-5xl"
                >
                    Shop the good stuff.
                </h2>
            </div>
            <Link
                href="/shop"
                class="hidden items-center gap-2 text-sm font-bold text-[#0f6ea8] sm:flex"
                >View all <ArrowRight class="h-4 w-4"
            /></Link>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="product in featuredProducts"
                :key="product.id"
                class="group"
            >
                <Link
                    :href="`/shop/${product.slug}`"
                    class="relative mb-4 block aspect-[4/4.5] overflow-hidden rounded-3xl border border-[#c7e3f3] bg-[#e8f7fe]"
                    ><img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-[#102a43]/45 via-transparent to-transparent opacity-70"
                    ></div>
                    <div class="absolute inset-x-4 top-4 flex justify-between">
                        <span
                            class="rounded-full bg-white/90 px-3 py-1.5 text-[10px] font-bold tracking-[0.12em] text-[#0f6ea8] uppercase"
                            >{{ product.type }}</span
                        ><span
                            v-if="product.is_featured"
                            class="rounded-full bg-[#27a9e8] px-3 py-1.5 text-[10px] font-bold text-white"
                            >Featured</span
                        >
                    </div>
                    <span
                        class="absolute right-4 bottom-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#0f6ea8] opacity-0 shadow-lg transition group-hover:opacity-100"
                        ><ArrowUpRight class="h-4 w-4" /></span
                ></Link>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-[#102a43]">
                            {{ product.name }}
                        </h3>
                        <p class="mt-1 text-xs leading-5 text-[#55718a]">
                            {{ product.tagline }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#c7e3f3] text-[#0f6ea8] transition hover:bg-[#0f6ea8] hover:text-white"
                        aria-label="Add to cart"
                        @click="add(product)"
                    >
                        <Plus class="h-4 w-4" />
                    </button>
                </div>
                <p class="mt-3 text-sm font-extrabold text-[#102a43]">
                    {{ money(product.price)
                    }}<span
                        v-if="product.billing_period"
                        class="ml-1 text-xs font-medium text-[#55718a]"
                        >/ {{ product.billing_period }}</span
                    >
                </p>
            </div>
        </div>
        <Link
            href="/shop"
            class="mt-8 inline-flex items-center gap-2 text-sm font-bold text-[#0f6ea8] sm:hidden"
            >View all products <ArrowRight class="h-4 w-4"
        /></Link>
    </section>

    <section id="about" class="px-5 pb-24 sm:px-8 sm:pb-32">
        <div
            class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 rounded-3xl bg-gradient-to-br from-[#27a9e8] to-[#0f6ea8] px-7 py-12 text-white shadow-[0_18px_44px_rgba(15,110,168,0.22)] sm:px-14 sm:py-16 md:flex-row md:items-end"
        >
            <div>
                <p
                    class="mb-5 text-[10px] font-bold tracking-[0.18em] text-white/75 uppercase"
                >
                    Have a thing in mind?
                </p>
                <h2
                    class="max-w-2xl text-4xl leading-tight font-extrabold tracking-[-0.05em] sm:text-6xl"
                >
                    Let's make it<br /><span class="text-[#dff5ff]"
                        >matter.</span
                    >
                </h2>
            </div>
            <a
                href="mailto:hello@elyx.studio"
                class="inline-flex items-center gap-3 rounded-xl bg-white px-5 py-3.5 text-sm font-bold text-[#0f6ea8] transition hover:bg-[#e8f7fe]"
                >Start a conversation <ArrowUpRight class="h-4 w-4"
            /></a>
        </div>
    </section>
</template>
