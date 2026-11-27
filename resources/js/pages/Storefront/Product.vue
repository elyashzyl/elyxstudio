<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    Minus,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Sparkles,
} from '@lucide/vue';
import { ref } from 'vue';
import type { StorefrontProduct } from '@/types';

const props = defineProps<{
    product: StorefrontProduct;
    relatedProducts: StorefrontProduct[];
}>();
const quantity = ref(1);
const money = (value: string | number) =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 0 })}`;
const available = () => props.product.available_stock ?? 99;
</script>

<template>
    <Head :title="product.name" />
    <section class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-16">
        <Link
            href="/shop"
            class="mb-9 inline-flex items-center gap-2 text-xs font-bold text-[#55718a] transition hover:text-[#0f6ea8]"
            ><ArrowLeft class="h-3.5 w-3.5" /> Back to shop</Link
        >
        <div class="grid gap-10 lg:grid-cols-[1.02fr_0.98fr] lg:gap-16">
            <div
                class="relative aspect-square overflow-hidden rounded-3xl border border-[#c7e3f3] bg-[#e8f7fe] shadow-[0_18px_44px_rgba(16,42,67,0.08)]"
            >
                <img
                    v-if="product.image_url"
                    :src="product.image_url"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#102a43]/35 via-transparent to-transparent"
                ></div>
                <div
                    class="absolute inset-x-5 top-5 flex items-center justify-between"
                >
                    <span
                        class="rounded-full bg-white/95 px-4 py-2 text-[10px] font-bold tracking-[0.14em] text-[#0f6ea8] uppercase"
                        >{{ product.type }}</span
                    ><span
                        v-if="product.is_featured"
                        class="rounded-full bg-[#27a9e8] px-4 py-2 text-[10px] font-bold text-white"
                        >Studio pick</span
                    >
                </div>
                <div
                    class="absolute right-5 bottom-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/95 text-[#0f6ea8] shadow-lg"
                >
                    <Sparkles class="h-5 w-5" />
                </div>
            </div>
            <div class="flex flex-col justify-center">
                <p
                    class="mb-4 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                >
                    {{ product.category }}
                </p>
                <h1
                    class="max-w-xl text-5xl leading-[0.95] font-extrabold tracking-[-0.06em] text-[#102a43] sm:text-7xl"
                >
                    {{ product.name }}
                </h1>
                <p class="mt-6 max-w-lg text-lg leading-7 text-[#55718a]">
                    {{ product.tagline }}
                </p>
                <div class="mt-7 flex items-end gap-3">
                    <span class="text-3xl font-extrabold text-[#102a43]">{{
                        money(product.price)
                    }}</span
                    ><span
                        v-if="product.compare_at_price"
                        class="pb-1 text-sm text-[#8aa4b8] line-through"
                        >{{ money(product.compare_at_price) }}</span
                    ><span
                        v-if="product.billing_period"
                        class="pb-1 text-xs font-semibold text-[#55718a]"
                        >/ {{ product.billing_period }}</span
                    >
                </div>
                <p class="mt-7 max-w-lg text-sm leading-7 text-[#55718a]">
                    {{ product.description }}
                </p>
                <div
                    v-if="product.features?.length"
                    class="mt-7 grid gap-3 border-t border-[#d7ebf8] pt-6 sm:grid-cols-2"
                >
                    <div
                        v-for="feature in product.features"
                        :key="feature"
                        class="flex items-start gap-2 text-sm font-medium text-[#102a43]"
                    >
                        <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#27a9e8]" />
                        {{ feature }}
                    </div>
                </div>
                <div class="mt-9 flex flex-wrap gap-3">
                    <div
                        v-if="product.type !== 'service'"
                        class="flex items-center rounded-xl border border-[#c7e3f3] bg-white"
                    >
                        <button
                            type="button"
                            class="p-3 text-[#55718a] transition hover:text-[#0f6ea8]"
                            aria-label="Decrease quantity"
                            @click="quantity = Math.max(1, quantity - 1)"
                        >
                            <Minus class="h-4 w-4" /></button
                        ><span
                            class="w-8 text-center text-sm font-extrabold text-[#102a43]"
                            >{{ quantity }}</span
                        ><button
                            type="button"
                            class="p-3 text-[#55718a] transition hover:text-[#0f6ea8] disabled:opacity-40"
                            aria-label="Increase quantity"
                            @click="
                                quantity = Math.min(quantity + 1, available())
                            "
                            :disabled="
                                product.available_stock !== null &&
                                product.available_stock !== undefined &&
                                quantity >= product.available_stock
                            "
                        >
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-3 rounded-xl bg-[#0f6ea8] px-6 py-3.5 text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                        @click="
                            router.post(
                                `/cart/${product.slug}`,
                                { quantity },
                                { preserveScroll: true },
                            )
                        "
                    >
                        <ShoppingBag class="h-4 w-4" />{{
                            product.type === 'service'
                                ? 'Start a project'
                                : 'Add to cart'
                        }}
                    </button>
                </div>
                <p
                    v-if="product.stock !== null"
                    class="mt-4 flex items-center gap-2 text-xs font-semibold text-[#55718a]"
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="
                            (product.available_stock ?? 0) > 0
                                ? 'bg-emerald-500'
                                : 'bg-red-500'
                        "
                    ></span
                    >{{ product.available_stock ?? product.stock }} available
                </p>
                <div
                    class="mt-8 grid gap-3 border-t border-[#d7ebf8] pt-6 text-xs font-semibold text-[#55718a] sm:grid-cols-2"
                >
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="h-4 w-4 text-[#27a9e8]" /> Secure
                        order request
                    </div>
                    <div class="flex items-center gap-2">
                        <Sparkles class="h-4 w-4 text-[#27a9e8]" /> Human
                        support from ELYX
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section
        v-if="relatedProducts.length"
        class="border-t border-[#d7ebf8] bg-[#f4fbff] px-5 py-16 sm:px-8 sm:py-24"
    >
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <p
                        class="mb-2 text-[10px] font-bold tracking-[0.18em] text-[#0f6ea8] uppercase"
                    >
                        Keep exploring
                    </p>
                    <h2
                        class="text-3xl font-extrabold tracking-[-0.04em] text-[#102a43] sm:text-4xl"
                    >
                        You might also like.
                    </h2>
                </div>
                <Link
                    href="/shop"
                    class="hidden items-center gap-2 text-sm font-bold text-[#0f6ea8] sm:flex"
                    >View all <ArrowRight class="h-4 w-4"
                /></Link>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <Link
                    v-for="related in relatedProducts"
                    :key="related.id"
                    :href="`/shop/${related.slug}`"
                    class="group overflow-hidden rounded-3xl border border-[#c7e3f3] bg-white p-3 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                    ><div
                        class="mb-4 aspect-[4/3] overflow-hidden rounded-2xl bg-[#e8f7fe]"
                    >
                        <img
                            v-if="related.image_url"
                            :src="related.image_url"
                            :alt="related.name"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                    </div>
                    <div class="flex justify-between gap-3 px-2 pb-2">
                        <span class="font-bold text-[#102a43]">{{
                            related.name
                        }}</span
                        ><span class="text-sm font-extrabold text-[#0f6ea8]">{{
                            money(related.price)
                        }}</span>
                    </div></Link
                >
            </div>
        </div>
    </section>
</template>
