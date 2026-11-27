<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Minus,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Trash2,
} from '@lucide/vue';
import type { CartItem } from '@/types';

defineProps<{
    items: CartItem[];
    count: number;
    subtotal: number;
    tax: number;
    total: number;
}>();
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
</script>

<template>
    <Head title="Your cart" />
    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-20">
        <Link
            href="/shop"
            class="mb-9 inline-flex items-center gap-2 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
            ><ArrowLeft class="h-3.5 w-3.5" /> Continue shopping</Link
        >
        <div
            class="mb-10 flex flex-col justify-between gap-4 border-b border-[#d7ebf8] pb-8 sm:flex-row sm:items-end"
        >
            <div>
                <p
                    class="mb-3 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    Your selection
                </p>
                <h1
                    class="text-5xl leading-none font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-6xl"
                >
                    Your cart.
                </h1>
            </div>
            <p
                class="rounded-full bg-[#e8f7fe] px-3 py-1.5 text-xs font-bold text-[#0f6ea8]"
            >
                {{ count }} {{ count === 1 ? 'item' : 'items' }}
            </p>
        </div>
        <div
            v-if="items.length"
            class="grid gap-10 lg:grid-cols-[1fr_370px] lg:gap-16"
        >
            <div
                class="divide-y divide-[#d7ebf8] rounded-3xl border border-[#c7e3f3] bg-white px-5 sm:px-7"
            >
                <div
                    v-for="item in items"
                    :key="item.product.id"
                    class="flex gap-4 py-6 first:pt-5 sm:gap-6"
                >
                    <Link
                        :href="`/shop/${item.product.slug}`"
                        class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-[#e8f7fe] sm:h-36 sm:w-36"
                        ><img
                            v-if="item.product.image_url"
                            :src="item.product.image_url"
                            :alt="item.product.name"
                            class="h-full w-full object-cover"
                    /></Link>
                    <div class="flex min-w-0 flex-1 flex-col justify-between">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p
                                    class="text-[10px] font-bold tracking-[0.14em] text-[#0f6ea8] uppercase"
                                >
                                    {{ item.product.type }}
                                </p>
                                <Link
                                    :href="`/shop/${item.product.slug}`"
                                    class="mt-1 block truncate font-extrabold text-[#102a43] transition hover:text-[#0f6ea8]"
                                    >{{ item.product.name }}</Link
                                >
                                <p
                                    class="mt-1 line-clamp-1 text-xs text-[#55718a]"
                                >
                                    {{ item.product.tagline }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="text-[#8aa4b8] transition hover:text-red-500"
                                aria-label="Remove item"
                                @click="
                                    router.delete(
                                        `/cart/${item.product.slug}`,
                                        { preserveScroll: true },
                                    )
                                "
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="flex items-end justify-between gap-3">
                            <div
                                class="flex items-center rounded-xl border border-[#c7e3f3] bg-[#f4fbff]"
                            >
                                <button
                                    type="button"
                                    class="p-2.5 text-[#55718a] hover:text-[#0f6ea8]"
                                    aria-label="Decrease quantity"
                                    @click="
                                        router.patch(
                                            `/cart/${item.product.slug}`,
                                            {
                                                quantity: Math.max(
                                                    0,
                                                    item.quantity - 1,
                                                ),
                                            },
                                            { preserveScroll: true },
                                        )
                                    "
                                >
                                    <Minus class="h-3.5 w-3.5" /></button
                                ><span
                                    class="w-8 text-center text-xs font-extrabold text-[#102a43]"
                                    >{{ item.quantity }}</span
                                ><button
                                    type="button"
                                    class="p-2.5 text-[#55718a] hover:text-[#0f6ea8]"
                                    aria-label="Increase quantity"
                                    @click="
                                        router.patch(
                                            `/cart/${item.product.slug}`,
                                            { quantity: item.quantity + 1 },
                                            { preserveScroll: true },
                                        )
                                    "
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <p class="font-extrabold text-[#102a43]">
                                {{ money(item.line_total) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <aside
                class="h-fit rounded-3xl border border-[#b9e2f6] bg-gradient-to-br from-[#e8f7fe] to-white p-6 shadow-[0_12px_30px_rgba(39,169,232,0.1)] sm:p-8"
            >
                <h2 class="mb-6 text-lg font-extrabold text-[#102a43]">
                    Order summary
                </h2>
                <div class="space-y-3 border-b border-[#c7e3f3] pb-6 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Subtotal</span
                        ><span class="font-semibold text-[#102a43]">{{
                            money(subtotal)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#55718a]">Tax</span
                        ><span class="font-semibold text-[#102a43]">{{
                            tax ? money(tax) : 'Calculated later'
                        }}</span>
                    </div>
                </div>
                <div
                    class="flex justify-between py-6 text-xl font-extrabold text-[#102a43]"
                >
                    <span>Total</span><span>{{ money(total) }}</span>
                </div>
                <Link
                    href="/checkout"
                    class="group flex w-full items-center justify-center gap-3 rounded-xl bg-[#0f6ea8] px-5 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                    >Continue to checkout
                    <ArrowRight
                        class="h-4 w-4 transition-transform group-hover:translate-x-1"
                /></Link>
                <div
                    class="mt-5 flex items-start gap-2 border-t border-[#c7e3f3] pt-5 text-[11px] leading-5 text-[#55718a]"
                >
                    <ShieldCheck
                        class="mt-0.5 h-4 w-4 shrink-0 text-[#27a9e8]"
                    />
                    Payments are arranged securely after your order details are
                    confirmed.
                </div>
            </aside>
        </div>
        <div
            v-else
            class="rounded-3xl border border-dashed border-[#9edaf3] bg-white px-6 py-24 text-center"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8f7fe] text-[#27a9e8]"
            >
                <ShoppingBag class="h-6 w-6" />
            </div>
            <h2
                class="mt-5 text-3xl font-extrabold tracking-tight text-[#102a43]"
            >
                Your cart is quiet.
            </h2>
            <p class="mt-3 text-sm text-[#55718a]">
                Find something that makes you want to start.
            </p>
            <Link
                href="/shop"
                class="mt-7 inline-flex items-center gap-2 rounded-xl bg-[#0f6ea8] px-5 py-3 text-sm font-bold text-white"
                >Browse the shop <ArrowRight class="h-4 w-4"
            /></Link>
        </div>
    </section>
</template>
