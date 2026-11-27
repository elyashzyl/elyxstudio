<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Menu, ShoppingBag, Sparkles, X } from '@lucide/vue';
import { computed, ref } from 'vue';

const page = usePage();
const mobileOpen = ref(false);
const cartCount = computed(() => Number((page.props as any).cart?.count ?? 0));
const currentUrl = computed(() => page.url);
const user = computed(() => (page.props as any).auth?.user ?? null);
const role = computed(() => user.value?.role ?? '');
const accountHref = computed(() =>
    role.value === 'seller'
        ? '/seller/dashboard'
        : role.value === 'superadmin'
          ? '/admin/commerce'
          : role.value === 'buyer'
            ? '/account'
            : user.value
              ? '/portal'
              : '/login',
);
const accountLabel = computed(() =>
    role.value === 'seller'
        ? 'Seller studio'
        : role.value === 'superadmin'
          ? 'Admin commerce'
          : role.value === 'buyer'
            ? 'My account'
            : user.value
              ? 'EduPulse portal'
              : 'Sign in',
);

const closeMenu = () => {
    mobileOpen.value = false;
};
</script>

<template>
    <div class="elyx-site min-h-screen bg-[#f4fbff] text-[#102a43]">
        <div
            class="bg-[#102a43] px-5 py-2 text-center text-[10px] font-semibold tracking-[0.16em] text-white/80 uppercase sm:text-[11px]"
        >
            Digital products, websites, and thoughtful studio services
        </div>
        <header
            class="sticky top-0 z-40 border-b border-[#d7ebf8] bg-white/95 shadow-[0_4px_24px_rgba(16,42,67,0.06)] backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-[74px] max-w-7xl items-center justify-between px-5 sm:px-8"
            >
                <Link
                    href="/"
                    class="group flex items-center gap-3"
                    @click="closeMenu"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#27a9e8] text-white shadow-[0_8px_18px_rgba(39,169,232,0.28)] transition-transform group-hover:-rotate-6"
                    >
                        <Sparkles class="h-4 w-4" />
                    </span>
                    <span class="leading-none">
                        <span
                            class="block text-[15px] font-extrabold tracking-[0.18em] text-[#102a43] uppercase"
                            >ELYX</span
                        >
                        <span
                            class="mt-1 block text-[9px] font-semibold tracking-[0.22em] text-[#55718a] uppercase"
                            >Studio shop</span
                        >
                    </span>
                </Link>

                <nav
                    class="hidden items-center gap-8 text-[13px] font-semibold md:flex"
                >
                    <Link
                        href="/shop"
                        class="transition-colors hover:text-[#0f6ea8]"
                        :class="
                            currentUrl.startsWith('/shop')
                                ? 'text-[#0f6ea8]'
                                : 'text-[#55718a]'
                        "
                        >Shop</Link
                    >
                    <a
                        href="/#services"
                        class="text-[#55718a] transition-colors hover:text-[#0f6ea8]"
                        >Websites</a
                    >
                    <a
                        href="/#process"
                        class="text-[#55718a] transition-colors hover:text-[#0f6ea8]"
                        >How it works</a
                    >
                    <a
                        href="/#about"
                        class="text-[#55718a] transition-colors hover:text-[#0f6ea8]"
                        >About</a
                    >
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        :href="accountHref"
                        class="hidden items-center gap-2 rounded-xl px-3 py-2.5 text-[13px] font-bold text-[#55718a] transition-colors hover:bg-[#f4fbff] hover:text-[#0f6ea8] sm:flex"
                    >
                        {{ accountLabel }}
                    </Link>
                    <Link
                        href="/cart"
                        class="relative flex items-center gap-2 rounded-xl border border-[#c7e3f3] bg-[#f4fbff] px-3.5 py-2.5 text-[13px] font-bold text-[#102a43] transition-all hover:border-[#27a9e8] hover:bg-[#e8f7fe]"
                    >
                        <ShoppingBag class="h-4 w-4 text-[#0f6ea8]" />
                        <span class="hidden sm:inline">Cart</span>
                        <span
                            v-if="cartCount"
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#27a9e8] px-1 text-[10px] text-white"
                            >{{ cartCount }}</span
                        >
                    </Link>
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-[#c7e3f3] text-[#102a43] md:hidden"
                        :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>
            <div
                v-if="mobileOpen"
                class="border-t border-[#d7ebf8] bg-white px-5 py-4 md:hidden"
            >
                <nav class="grid gap-1 text-sm font-semibold">
                    <Link
                        :href="accountHref"
                        class="rounded-xl px-3 py-3 text-[#55718a] hover:bg-[#f4fbff]"
                        @click="closeMenu"
                        >{{ accountLabel }}</Link
                    >
                    <Link
                        href="/shop"
                        class="rounded-xl px-3 py-3 text-[#55718a] hover:bg-[#f4fbff]"
                        @click="closeMenu"
                        >Shop all</Link
                    >
                    <a
                        href="/#services"
                        class="rounded-xl px-3 py-3 text-[#55718a] hover:bg-[#f4fbff]"
                        @click="closeMenu"
                        >Websites</a
                    >
                    <a
                        href="/#process"
                        class="rounded-xl px-3 py-3 text-[#55718a] hover:bg-[#f4fbff]"
                        @click="closeMenu"
                        >How it works</a
                    >
                    <a
                        href="/#about"
                        class="rounded-xl px-3 py-3 text-[#55718a] hover:bg-[#f4fbff]"
                        @click="closeMenu"
                        >About ELYX</a
                    >
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-[#d7ebf8] bg-[#102a43] text-white">
            <div
                class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 md:grid-cols-[1.4fr_1fr_1fr]"
            >
                <div>
                    <div class="mb-5 flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#27a9e8] text-white"
                        >
                            <Sparkles class="h-4 w-4" />
                        </span>
                        <span
                            class="text-sm font-extrabold tracking-[0.18em] uppercase"
                            >ELYX Studio</span
                        >
                    </div>
                    <p class="max-w-sm text-sm leading-7 text-white/60">
                        Practical digital products and distinctive websites for
                        people building what comes next.
                    </p>
                    <Link
                        href="/shop"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#27a9e8] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#51b9ec]"
                        >Browse the shop <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
                <div>
                    <p
                        class="mb-4 text-[10px] font-bold tracking-[0.18em] text-[#8bd5f4] uppercase"
                    >
                        Explore
                    </p>
                    <div class="grid gap-3 text-sm text-white/65">
                        <Link
                            href="/shop"
                            class="transition-colors hover:text-white"
                            >Shop all products</Link
                        >
                        <a
                            href="/#services"
                            class="transition-colors hover:text-white"
                            >Website services</a
                        >
                        <a
                            href="/#process"
                            class="transition-colors hover:text-white"
                            >Our process</a
                        >
                        <Link
                            href="/cart"
                            class="transition-colors hover:text-white"
                            >Your cart</Link
                        >
                    </div>
                </div>
                <div>
                    <p
                        class="mb-4 text-[10px] font-bold tracking-[0.18em] text-[#8bd5f4] uppercase"
                    >
                        Need a custom build?
                    </p>
                    <p class="text-sm leading-6 text-white/60">
                        Tell us what you are making and we will help shape the
                        right next step.
                    </p>
                    <a
                        href="mailto:hello@elyx.studio"
                        class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-white transition-colors hover:text-[#8bd5f4]"
                        >hello@elyx.studio <ArrowRight class="h-3.5 w-3.5" />
                    </a>
                    <p class="mt-8 text-xs text-white/35">
                        © {{ new Date().getFullYear() }} ELYX Studio
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
